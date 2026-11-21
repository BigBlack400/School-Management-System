<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

final class ITSupportDashboardController
{
    public function index(): void
    {
        $user = Auth::requireRole(['it_support']);
        $db = Database::connection();
        $isSuper = in_array('super_administrator', $user['roles'], true);
        $scope = $isSuper ? '1 = 1' : 'u.school_id = :school_id';
        $params = $isSuper ? [] : ['school_id' => $user['school_id']];

        $count = static function (string $sql, array $params = []) use ($db): int {
            $statement = $db->prepare($sql);
            $statement->execute($params);
            return (int) $statement->fetchColumn();
        };

        $stats = [
            'accounts' => $count("SELECT COUNT(*) FROM users u WHERE $scope", $params),
            'active_accounts' => $count("SELECT COUNT(*) FROM users u WHERE u.status = 'active' AND $scope", $params),
            'suspended_accounts' => $count("SELECT COUNT(*) FROM users u WHERE u.status IN ('inactive','suspended','dismissed') AND $scope", $params),
            'audit_events' => $count("SELECT COUNT(*) FROM audit_logs al LEFT JOIN users u ON u.id = al.user_id WHERE " . ($isSuper ? '1 = 1' : 'u.school_id = :school_id'), $params),
            'failed_notifications' => $count("SELECT COUNT(*) FROM notification_logs nl LEFT JOIN users u ON u.id = nl.user_id WHERE nl.status = 'failed' AND " . ($isSuper ? '1 = 1' : 'u.school_id = :school_id'), $params),
            'locked_accounts' => $count("SELECT COUNT(*) FROM login_throttles WHERE locked_until IS NOT NULL AND locked_until > CURRENT_TIMESTAMP", []),
        ];

        $recentUsers = $db->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.status, u.last_login_at,
                    GROUP_CONCAT(DISTINCT r.label ORDER BY r.label SEPARATOR ", ") AS roles
             FROM users u
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r ON r.id = ur.role_id
             WHERE ' . ($isSuper ? '1 = 1' : 'u.school_id = :school_id') . '
             GROUP BY u.id, u.first_name, u.last_name, u.email, u.status, u.last_login_at
             ORDER BY u.updated_at DESC
             LIMIT 30'
        );
        $recentUsers->execute($params);

        $recentAudit = $db->prepare(
            'SELECT al.id, al.action, al.entity_type, al.entity_id, al.ip_address, al.created_at,
                    u.first_name, u.last_name
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE ' . ($isSuper ? '1 = 1' : 'u.school_id = :school_id') . '
             ORDER BY al.created_at DESC, al.id DESC
             LIMIT 30'
        );
        $recentAudit->execute($params);

        $notifications = $db->prepare(
            'SELECT nl.id, nl.channel, nl.event_type, nl.status, nl.error_summary, nl.created_at,
                    u.email
             FROM notification_logs nl
             LEFT JOIN users u ON u.id = nl.user_id
             WHERE ' . ($isSuper ? '1 = 1' : 'u.school_id = :school_id') . '
             ORDER BY nl.created_at DESC
             LIMIT 30'
        );
        $notifications->execute($params);

        $locks = $db->prepare(
            'SELECT throttle_key, failure_count, locked_until, updated_at
             FROM login_throttles
             WHERE locked_until IS NOT NULL AND locked_until > CURRENT_TIMESTAMP
             ORDER BY locked_until DESC
             LIMIT 30'
        );
        $locks->execute();

        View::render('it/dashboard', [
            'user' => $user,
            'stats' => $stats,
            'accounts' => $recentUsers->fetchAll(),
            'audit' => $recentAudit->fetchAll(),
            'notifications' => $notifications->fetchAll(),
            'locks' => $locks->fetchAll(),
        ]);
    }
}
