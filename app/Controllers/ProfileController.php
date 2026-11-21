<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\PasswordHasher;
use App\Core\View;

final class ProfileController
{
    public function edit(): void
    {
        $user = Auth::user();
        if ($user === null) {
            header('Location: /login');
            exit;
        }
        View::render('profile/edit', ['user' => $user, 'csrf' => Csrf::token(), 'message' => $_SESSION['flash_success'] ?? null]);
        unset($_SESSION['flash_success']);
    }

    public function update(): void
    {
        $user = Auth::user();
        if ($user === null) {
            header('Location: /login');
            exit;
        }
        Csrf::validateRequest();
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($firstName === '' || $lastName === '' || mb_strlen($firstName) > 100 || mb_strlen($lastName) > 100 || mb_strlen($phone) > 32 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254) {
            http_response_code(422);
            View::render('profile/edit', [
                'user' => $user,
                'csrf' => Csrf::token(),
                'error' => 'Enter valid names, a valid email, and a phone number of up to 32 characters.',
            ]);
            return;
        }

        $passwordRequested = $currentPassword !== '' || $newPassword !== '' || $confirmPassword !== '';
        $db = Database::connection();
        $db->beginTransaction();
        $passwordChanged = false;
        try {
            $duplicate = $db->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
            $duplicate->execute(['email' => $email, 'id' => $user['id']]);
            if ($duplicate->fetchColumn()) {
                throw new \RuntimeException('That email address is already in use.');
            }

            $credentialQuery = $db->prepare('SELECT password_hash, auth_version FROM users WHERE id = :id LIMIT 1');
            $credentialQuery->execute(['id' => $user['id']]);
            $credentials = $credentialQuery->fetch();
            if ($passwordRequested) {
                if (!$credentials || !PasswordHasher::verify($currentPassword, $credentials['password_hash'])) {
                    throw new \RuntimeException('The current password is incorrect.');
                }
                if (mb_strlen($newPassword) < 14 || mb_strlen($newPassword) > 4096 || !hash_equals($newPassword, $confirmPassword)) {
                    throw new \RuntimeException('Use a new password of at least 14 characters and confirm it exactly.');
                }
                $passwordChanged = true;
            }

            $sql = 'UPDATE users SET first_name = :first_name, last_name = :last_name, phone = :phone,
                    email = :email, updated_at = CURRENT_TIMESTAMP';
            $params = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone !== '' ? $phone : null,
                'email' => $email,
            ];
            if ($passwordChanged) {
                $sql .= ', password_hash = :password_hash, auth_version = auth_version + 1';
                $params['password_hash'] = PasswordHasher::hash($newPassword);
            }
            $sql .= ' WHERE id = :id';
            $params['id'] = $user['id'];

            $update = $db->prepare($sql);
            $update->execute($params);

            if ($passwordChanged) {
                $authVersion = $db->prepare('SELECT auth_version FROM users WHERE id = :id');
                $authVersion->execute(['id' => $user['id']]);
                $_SESSION['auth_version'] = (int) $authVersion->fetchColumn();
                Auth::clearCurrentUser();
            } else {
                Auth::clearCurrentUser();
            }
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            http_response_code(422);
            View::render('profile/edit', [
                'user' => $user,
                'csrf' => Csrf::token(),
                'error' => $exception instanceof \RuntimeException ? $exception->getMessage() : 'The profile could not be updated.',
            ]);
            return;
        }

        AuditLogger::record((int) $user['id'], $passwordChanged ? 'profile.credentials_updated' : 'profile.updated', 'user', (int) $user['id'], [
            'email_changed' => $email !== $user['email'],
            'password_changed' => $passwordChanged,
        ]);
        $_SESSION['flash_success'] = $passwordRequested
            ? 'Email and password settings updated.'
            : 'Profile updated.';
        header('Location: /profile', true, 303);
        exit;
    }
}
