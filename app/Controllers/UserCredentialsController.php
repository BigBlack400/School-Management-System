<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\PasswordHasher;
use App\Core\View;

final class UserCredentialsController
{
    public function index(): void
    {
        $user = Auth::requireRole(['school_administrator', 'super_administrator', 'head_teacher', 'it_support']);
        $db = Database::connection();
        $isSuper = in_array('super_administrator', $user['roles'], true);
        $isIT = in_array('it_support', $user['roles'], true);
        $scopeSql = $isSuper
            ? '1 = 1'
            : ($isIT
                ? 'u.school_id = :school_id
                   AND EXISTS (SELECT 1 FROM user_roles ir INNER JOIN roles rr ON rr.id = ir.role_id
                               WHERE ir.user_id = u.id
                                 AND rr.name IN ("teacher", "administrative_staff", "it_support")
                                 AND (ir.expires_at IS NULL OR ir.expires_at > CURRENT_TIMESTAMP))'
                : 'u.school_id = :school_id');
        $query = $db->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.status,
                    GROUP_CONCAT(DISTINCT r.label ORDER BY r.label SEPARATOR ", ") AS roles
             FROM users u
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r ON r.id = ur.role_id
             WHERE ' . $scopeSql . '
             GROUP BY u.id, u.first_name, u.last_name, u.email, u.status
             ORDER BY u.last_name, u.first_name
             LIMIT 500'
        );
        $params = $isSuper ? [] : ['school_id' => $user['school_id']];
        $query->execute($params);
        $selectedId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        $selected = null;
        if ($selectedId !== false && $selectedId !== null) {
            $selectedQuery = $db->prepare(
                'SELECT u.id, u.first_name, u.last_name, u.email, u.status
                 FROM users u
                 WHERE u.id = :id AND ' . $scopeSql . '
                 LIMIT 1'
            );
            $selectedParams = ['id' => (int) $selectedId];
            if (!$isSuper) {
                $selectedParams['school_id'] = $user['school_id'];
            }
            $selectedQuery->execute($selectedParams);
            $selected = $selectedQuery->fetch() ?: null;
        }

        View::render('users/credentials', [
            'user' => $user,
            'csrf' => Csrf::token(),
            'accounts' => $query->fetchAll(),
            'selected' => $selected,
            'message' => $_SESSION['flash_success'] ?? null,
            'error' => $_SESSION['flash_error'] ?? null,
        ]);
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function update(): void
    {
        $user = Auth::requireRole(['school_administrator', 'super_administrator', 'head_teacher', 'it_support']);
        Csrf::validateRequest();

        $accountId = filter_var($_POST['account_id'] ?? null, FILTER_VALIDATE_INT);
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        if (!$accountId || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254) {
            $_SESSION['flash_error'] = 'Select a valid account and enter a valid email.';
            header('Location: /users/credentials', true, 303);
            exit;
        }
        if ($newPassword !== '' && (
            mb_strlen($newPassword) < 14 ||
            mb_strlen($newPassword) > 4096 ||
            !hash_equals($newPassword, $confirmPassword)
        )) {
            $_SESSION['flash_error'] = 'Use a new password of at least 14 characters and confirm it exactly.';
            header('Location: /users/credentials?id=' . (int) $accountId, true, 303);
            exit;
        }

        $db = Database::connection();
        $isSuper = in_array('super_administrator', $user['roles'], true);
        $isIT = in_array('it_support', $user['roles'], true);
        $db->beginTransaction();
        try {
            $targetQuery = $db->prepare(
                'SELECT u.id FROM users u
                 WHERE u.id = :id
                   AND ' . ($isSuper
                    ? '1 = 1'
                    : ($isIT
                        ? 'u.school_id = :school_id
                           AND EXISTS (SELECT 1 FROM user_roles ir INNER JOIN roles rr ON rr.id = ir.role_id
                                       WHERE ir.user_id = u.id
                                         AND rr.name IN ("teacher", "administrative_staff", "it_support")
                                         AND (ir.expires_at IS NULL OR ir.expires_at > CURRENT_TIMESTAMP))'
                        : 'u.school_id = :school_id')) . '
                 LIMIT 1'
            );
            $targetParams = ['id' => (int) $accountId];
            if (!$isSuper) {
                $targetParams['school_id'] = $user['school_id'];
            }
            $targetQuery->execute($targetParams);
            if (!$targetQuery->fetchColumn()) {
                throw new \RuntimeException('The account is outside your authorized school scope.');
            }

            $duplicate = $db->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
            $duplicate->execute(['email' => $email, 'id' => (int) $accountId]);
            if ($duplicate->fetchColumn()) {
                throw new \RuntimeException('That email address is already in use.');
            }

            $sql = 'UPDATE users SET email = :email, updated_at = CURRENT_TIMESTAMP';
            $params = ['email' => $email, 'id' => (int) $accountId];
            if ($newPassword !== '') {
                $sql .= ', password_hash = :password_hash, auth_version = auth_version + 1';
                $params['password_hash'] = PasswordHasher::hash($newPassword);
            }
            $sql .= ' WHERE id = :id';
            $db->prepare($sql)->execute($params);
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['flash_error'] = $exception instanceof \RuntimeException
                ? $exception->getMessage()
                : 'The account credentials could not be updated.';
            header('Location: /users/credentials?id=' . (int) $accountId, true, 303);
            exit;
        }

        AuditLogger::record((int) $user['id'], 'users.credentials_updated', 'user', (int) $accountId, [
            'email_updated' => true,
            'password_updated' => $newPassword !== '',
        ]);
        $_SESSION['flash_success'] = 'Account credentials updated.';
        header('Location: /users/credentials?id=' . (int) $accountId, true, 303);
        exit;
    }
}
