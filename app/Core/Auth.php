<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    /** @var array<string, mixed>|null */
    private static ?array $currentUser = null;

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }
        $userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
        if ($userId === false || $userId === null) {
            return null;
        }
        if (time() - (int) ($_SESSION['last_activity'] ?? time()) > 1800) {
            self::logout();
            return null;
        }

        $statement = Database::connection()->prepare(
            'SELECT id, school_id, branch_id, first_name, last_name, email, phone, status, auth_version
             FROM users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();
        if (!$user || (int) $user['auth_version'] !== (int) ($_SESSION['auth_version'] ?? -1)) {
            self::logout();
            return null;
        }

        $roles = Database::connection()->prepare(
            'SELECT r.name FROM roles r
             INNER JOIN user_roles ur ON ur.role_id = r.id
             WHERE ur.user_id = :user_id AND (ur.expires_at IS NULL OR ur.expires_at > CURRENT_TIMESTAMP)'
        );
        $roles->execute(['user_id' => $userId]);
        $user['roles'] = array_column($roles->fetchAll(), 'name');
        self::$currentUser = $user;
        $_SESSION['last_activity'] = time();
        return self::$currentUser;
    }

    /** @param list<string> $roles */
    public static function requireRole(array $roles): array
    {
        $user = self::user();
        if ($user === null) {
            header('Location: /login');
            exit;
        }
        if (!array_intersect($roles, $user['roles'])) {
            http_response_code(403);
            exit('You do not have permission to access this page.');
        }
        return $user;
    }

    public static function can(string $permission): bool
    {
        $user = self::user();
        if ($user === null) {
            return false;
        }

        $statement = Database::connection()->prepare(
            'SELECT 1 FROM user_roles ur
             INNER JOIN role_permissions rp ON rp.role_id = ur.role_id
             INNER JOIN permissions p ON p.id = rp.permission_id
             WHERE ur.user_id = :user_id AND p.name = :permission
               AND (ur.expires_at IS NULL OR ur.expires_at > CURRENT_TIMESTAMP)
             LIMIT 1'
        );
        $statement->execute(['user_id' => $user['id'], 'permission' => $permission]);
        return (bool) $statement->fetchColumn();
    }

    public static function requirePermission(string $permission): array
    {
        $user = self::user();
        if ($user === null) {
            header('Location: /login');
            exit;
        }
        if (!self::can($permission)) {
            http_response_code(403);
            exit('You do not have permission to access this page.');
        }
        return $user;
    }

    /** @param array<string, mixed> $user */
    public static function establishSession(array $user): void
    {
        session_regenerate_id(true);
        unset($_SESSION['_csrf']);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['auth_version'] = (int) $user['auth_version'];
        $_SESSION['authenticated_at'] = time();
        $_SESSION['last_activity'] = time();
        self::$currentUser = null;
    }

    public static function logout(): void
    {
        self::$currentUser = null;
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }

    public static function clearCurrentUser(): void
    {
        self::$currentUser = null;
    }

    private function __construct()
    {
    }
}
