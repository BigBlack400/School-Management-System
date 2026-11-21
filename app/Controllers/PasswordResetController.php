<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Environment;
use App\Core\PasswordHasher;
use App\Core\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

final class PasswordResetController
{
    public function showRequest(): void
    {
        View::render('auth/forgot-password', ['csrf' => Csrf::token()]);
    }

    public function request(): void
    {
        Csrf::validateRequest();
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 254) {
            $mailerDsn = Environment::get('MAILER_DSN');
            $appUrl = rtrim(Environment::get('APP_URL'), '/');
            $appScheme = parse_url($appUrl, PHP_URL_SCHEME);
            if ($mailerDsn === '' || !filter_var($appUrl, FILTER_VALIDATE_URL)
                || (Environment::get('APP_ENV', 'production') === 'production' && $appScheme !== 'https')) {
                http_response_code(503);
                exit('Password recovery is not configured. Contact the school administrator.');
            }

            if (!$this->rateLimited($email)) {
                $query = Database::connection()->prepare('SELECT id, email, first_name FROM users WHERE email = :email AND status = "active" LIMIT 1');
                $query->execute(['email' => $email]);
                $user = $query->fetch();
                if ($user) {
                    $token = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $token);
                    $db = Database::connection();
                    $db->beginTransaction();
                    try {
                        $db->prepare('DELETE FROM password_reset_tokens WHERE user_id = :user_id')->execute(['user_id' => $user['id']]);
                        $db->prepare(
                            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
                             VALUES (:user_id, :token_hash, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 30 MINUTE))'
                        )->execute(['user_id' => $user['id'], 'token_hash' => $tokenHash]);
                        $db->commit();
                    } catch (\Throwable $exception) {
                        if ($db->inTransaction()) {
                            $db->rollBack();
                        }
                        throw $exception;
                    }

                    $notification = Database::connection()->prepare(
                        'INSERT INTO notification_logs (user_id, channel, event_type, status)
                         VALUES (:user_id, "email", "password_reset", "queued")'
                    );
                    $notification->execute(['user_id' => $user['id']]);
                    $notificationId = (int) Database::connection()->lastInsertId();
                    try {
                        $transport = Transport::fromDsn($mailerDsn);
                        $mailer = new Mailer($transport);
                        $resetUrl = $appUrl . '/password/reset?token=' . rawurlencode($token);
                        $mailer->send((new Email())
                            ->from(Environment::get('MAIL_FROM', 'no-reply@school.example.edu'))
                            ->to($user['email'])
                            ->subject('Reset your school account password')
                            ->text("Hello {$user['first_name']},\n\nUse the one-time link below within 30 minutes to reset your password:\n{$resetUrl}\n\nIf you did not request this, you can ignore this message."));
                        Database::connection()->prepare(
                            'UPDATE notification_logs SET status = "sent", sent_at = CURRENT_TIMESTAMP WHERE id = :id'
                        )->execute(['id' => $notificationId]);
                    } catch (TransportExceptionInterface|\InvalidArgumentException $exception) {
                        error_log('Password reset email delivery failed for user ' . (int) $user['id'] . '. Check configured mail transport.');
                        Database::connection()->prepare(
                            'UPDATE notification_logs SET status = "failed", error_summary = :error WHERE id = :id'
                        )->execute([
                            'error' => 'Mail transport delivery failed; see server mailer logs.',
                            'id' => $notificationId,
                        ]);
                    }
                }
            }
        }

        View::render('auth/forgot-password-sent', [], false);
    }

    public function showReset(): void
    {
        $token = (string) ($_GET['token'] ?? '');
        if (!$this->tokenIsValid($token)) {
            http_response_code(400);
            View::render('auth/reset-invalid', [], false);
            return;
        }
        View::render('auth/reset-password', ['csrf' => Csrf::token(), 'token' => $token]);
    }

    public function reset(): void
    {
        Csrf::validateRequest();
        $token = (string) ($_POST['token'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');
        if (strlen($password) < 14 || strlen($password) > 4096 || !hash_equals($password, $confirmation)) {
            http_response_code(422);
            View::render('auth/reset-password', [
                'csrf' => Csrf::token(),
                'token' => $token,
                'error' => 'Use a password of at least 14 characters and enter it identically in both fields.',
            ]);
            return;
        }
        if (!preg_match('/\A[a-f0-9]{64}\z/', $token)) {
            http_response_code(400);
            View::render('auth/reset-invalid', [], false);
            return;
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $query = $db->prepare(
                'SELECT id, user_id FROM password_reset_tokens
                 WHERE token_hash = :token_hash AND used_at IS NULL AND expires_at > CURRENT_TIMESTAMP
                 LIMIT 1 FOR UPDATE'
            );
            $query->execute(['token_hash' => hash('sha256', $token)]);
            $reset = $query->fetch();
            if (!$reset) {
                $db->rollBack();
                http_response_code(400);
                View::render('auth/reset-invalid', [], false);
                return;
            }

            $db->prepare(
                'UPDATE users SET password_hash = :password_hash, auth_version = auth_version + 1,
                    updated_at = CURRENT_TIMESTAMP WHERE id = :user_id'
            )->execute([
                'password_hash' => PasswordHasher::hash($password),
                'user_id' => $reset['user_id'],
            ]);
            $db->prepare('UPDATE password_reset_tokens SET used_at = CURRENT_TIMESTAMP WHERE id = :id')
                ->execute(['id' => $reset['id']]);
            $db->prepare('DELETE FROM password_reset_tokens WHERE user_id = :user_id AND id <> :current_id')
                ->execute(['user_id' => $reset['user_id'], 'current_id' => $reset['id']]);
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }

        AuditLogger::record(null, 'auth.password_reset', 'user', (int) $reset['user_id']);
        View::render('auth/reset-complete', [], false);
    }

    private function tokenIsValid(string $token): bool
    {
        if (!preg_match('/\A[a-f0-9]{64}\z/', $token)) {
            return false;
        }
        $query = Database::connection()->prepare(
            'SELECT 1 FROM password_reset_tokens
             WHERE token_hash = :token_hash AND used_at IS NULL AND expires_at > CURRENT_TIMESTAMP LIMIT 1'
        );
        $query->execute(['token_hash' => hash('sha256', $token)]);
        return (bool) $query->fetchColumn();
    }

    private function rateLimited(string $email): bool
    {
        $key = hash('sha256', 'password-reset|' . $email);
        $db = Database::connection();
        $query = $db->prepare('SELECT locked_until FROM login_throttles WHERE throttle_key = :key');
        $query->execute(['key' => $key]);
        $lockedUntil = $query->fetchColumn();
        if ($lockedUntil && strtotime((string) $lockedUntil) > time()) {
            return true;
        }
        if ($lockedUntil) {
            $db->prepare('DELETE FROM login_throttles WHERE throttle_key = :key')->execute(['key' => $key]);
        }
        $db->prepare(
            'INSERT INTO login_throttles (throttle_key, failure_count, locked_until, updated_at)
             VALUES (:key, 1, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 15 MINUTE), CURRENT_TIMESTAMP)
             ON DUPLICATE KEY UPDATE failure_count = failure_count + 1,
                 locked_until = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 15 MINUTE),
                 updated_at = CURRENT_TIMESTAMP'
        )->execute(['key' => $key]);
        return false;
    }
}
