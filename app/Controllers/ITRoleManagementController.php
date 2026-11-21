<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;

final class ITRoleManagementController
{
    public function index(): void
    {
        $user = Auth::requireRole(['it_support']);
        $db = Database::connection();

        $accounts = $db->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.status,
                    GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ", ") AS role_names,
                    GROUP_CONCAT(DISTINCT r.label ORDER BY r.label SEPARATOR ", ") AS role_labels
             FROM users u
             INNER JOIN user_roles ur ON ur.user_id = u.id
             INNER JOIN roles r ON r.id = ur.role_id
             WHERE u.school_id = :school_id
               AND r.name IN ("teacher", "administrative_staff", "it_support")
               AND (ur.expires_at IS NULL OR ur.expires_at > CURRENT_TIMESTAMP)
             GROUP BY u.id, u.first_name, u.last_name, u.email, u.status
             ORDER BY u.last_name, u.first_name'
        );
        $accounts->execute(['school_id' => $user['school_id']]);

        $roles = $db->prepare(
            'SELECT r.id, r.name, r.label, r.description,
                    GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ", ") AS permissions
             FROM roles r
             LEFT JOIN role_permissions rp ON rp.role_id = r.id
             LEFT JOIN permissions p ON p.id = rp.permission_id
             WHERE r.name IN ("teacher", "administrative_staff", "it_support")
             GROUP BY r.id, r.name, r.label, r.description
             ORDER BY r.label'
        );
        $roles->execute();

        View::render('it/roles', [
            'user' => $user,
            'csrf' => Csrf::token(),
            'accounts' => $accounts->fetchAll(),
            'roles' => $roles->fetchAll(),
            'message' => $_SESSION['flash_success'] ?? null,
            'error' => $_SESSION['flash_error'] ?? null,
        ]);
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function update(): void
    {
        $user = Auth::requireRole(['it_support']);
        Csrf::validateRequest();

        $accountId = filter_var($_POST['account_id'] ?? null, FILTER_VALIDATE_INT);
        $roleName = trim((string) ($_POST['role'] ?? ''));
        $allowedRoles = ['teacher', 'administrative_staff', 'it_support'];
        if (!$accountId || !in_array($roleName, $allowedRoles, true)) {
            $_SESSION['flash_error'] = 'Select an authorized staff account and role.';
            header('Location: /it-support/roles', true, 303);
            exit;
        }
        if ((int) $accountId === (int) $user['id']) {
            $_SESSION['flash_error'] = 'An account cannot change its own role through this console.';
            header('Location: /it-support/roles', true, 303);
            exit;
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $target = $db->prepare(
                'SELECT u.id,
                        GROUP_CONCAT(DISTINCT r.name SEPARATOR ",") AS role_names
                 FROM users u
                 INNER JOIN user_roles ur ON ur.user_id = u.id
                 INNER JOIN roles r ON r.id = ur.role_id
                 WHERE u.id = :id
                   AND u.school_id = :school_id
                   AND r.name IN ("teacher", "administrative_staff", "it_support")
                   AND (ur.expires_at IS NULL OR ur.expires_at > CURRENT_TIMESTAMP)
                 GROUP BY u.id
                 LIMIT 1'
            );
            $target->execute(['id' => (int) $accountId, 'school_id' => $user['school_id']]);
            $targetUser = $target->fetch();
            if (!$targetUser) {
                throw new \RuntimeException('The target account is outside the authorized staff scope.');
            }

            $before = (string) $targetUser['role_names'];
            $db->prepare(
                'DELETE FROM user_roles
                 WHERE user_id = :id
                   AND role_id IN (
                       SELECT id FROM roles
                       WHERE name IN ("teacher", "administrative_staff", "it_support")
                   )'
            )->execute(['id' => (int) $accountId]);

            $db->prepare(
                'INSERT INTO user_roles (user_id, role_id, assigned_by, expires_at, created_at)
                 SELECT :id, r.id, :assigned_by, NULL, CURRENT_TIMESTAMP
                 FROM roles r WHERE r.name = :role_name
                 LIMIT 1'
            )->execute([
                'id' => (int) $accountId,
                'assigned_by' => $user['id'],
                'role_name' => $roleName,
            ]);

            $db->prepare(
                'UPDATE users SET auth_version = auth_version + 1,
                        updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            )->execute(['id' => (int) $accountId]);

            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['flash_error'] = $exception instanceof \RuntimeException
                ? $exception->getMessage()
                : 'The staff role could not be changed.';
            header('Location: /it-support/roles', true, 303);
            exit;
        }

        AuditLogger::record((int) $user['id'], 'it.staff_role_changed', 'user', (int) $accountId, [
            'before_roles' => $before,
            'assigned_role' => $roleName,
        ]);
        $_SESSION['flash_success'] = 'Staff role updated and account sessions invalidated.';
        header('Location: /it-support/roles', true, 303);
        exit;
    }
}
