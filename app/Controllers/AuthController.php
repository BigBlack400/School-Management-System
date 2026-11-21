<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\PasswordHasher;
use App\Core\View;

final class AuthController
{
    public static function home(): void
    {
        if (Auth::user() !== null) {
            self::redirect('/dashboard');
        }
        (new PublicSiteController())->home();
    }

    public static function showLogin(): void
    {
        if (Auth::user() !== null) {
            self::redirect('/dashboard');
        }
        View::render('auth/login', ['csrf' => Csrf::token(), 'error' => $_SESSION['flash_error'] ?? null]);
        unset($_SESSION['flash_error']);
    }

    public static function login(): void
    {
        Csrf::validateRequest();
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || $password === '' || strlen($password) > 4096) {
            self::failLogin();
        }

        $db = Database::connection();
        $throttleKey = hash('sha256', 'login|' . $email);
        $throttle = $db->prepare('SELECT failure_count, locked_until FROM login_throttles WHERE throttle_key = :key');
        $throttle->execute(['key' => $throttleKey]);
        $attempt = $throttle->fetch();
        if ($attempt && $attempt['locked_until'] !== null && strtotime($attempt['locked_until']) > time()) {
            self::failLogin();
        }
        if ($attempt && $attempt['locked_until'] !== null) {
            $db->prepare('DELETE FROM login_throttles WHERE throttle_key = :key')->execute(['key' => $throttleKey]);
            $attempt = null;
        }

        $statement = $db->prepare(
            'SELECT id, school_id, password_hash, status, auth_version
             FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();
        $dummyHash = '$argon2id$v=19$m=65536,t=4,p=1$WExVZVhhNi9id1Q5Sm55ag$KFSu5/ozNgwcsH9peY9CCjD7hA9vzRzfluQZz7IakvU';
        $passwordHash = $user ? $user['password_hash'] : $dummyHash;
        $passwordIsValid = PasswordHasher::verify($password, $passwordHash);
        $valid = $user && $passwordIsValid;
        if (!$valid) {
            $failureCount = (int) ($attempt['failure_count'] ?? 0) + 1;
            $lockedUntil = $failureCount >= 5 ? date('Y-m-d H:i:s', time() + 900) : null;
            $upsert = $db->prepare(
                'INSERT INTO login_throttles (throttle_key, failure_count, locked_until, updated_at)
                 VALUES (:key, :failures, :locked_until, CURRENT_TIMESTAMP)
                 ON DUPLICATE KEY UPDATE failure_count = :updated_failures,
                   locked_until = :updated_locked_until, updated_at = CURRENT_TIMESTAMP'
            );
            $upsert->execute([
                'key' => $throttleKey,
                'failures' => $failureCount,
                'locked_until' => $lockedUntil,
                'updated_failures' => $failureCount,
                'updated_locked_until' => $lockedUntil,
            ]);
            if ($user) {
                AuditLogger::record((int) $user['id'], 'auth.login_failed', 'user', (int) $user['id']);
            }
            self::failLogin();
        }

        $db->prepare('DELETE FROM login_throttles WHERE throttle_key = :key')->execute(['key' => $throttleKey]);
        $db->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id')->execute(['id' => $user['id']]);
        Auth::establishSession($user);
        AuditLogger::record((int) $user['id'], 'auth.login_succeeded', 'user', (int) $user['id']);
        self::redirect('/dashboard');
    }

    public static function logout(): void
    {
        Csrf::validateRequest();
        $user = Auth::user();
        if ($user !== null) {
            AuditLogger::record((int) $user['id'], 'auth.logout', 'user', (int) $user['id']);
        }
        Auth::logout();
        self::redirect('/login');
    }

    public static function dashboard(): void
    {
        $user = Auth::user();
        if ($user === null) {
            self::redirect('/login');
        }
        if (in_array('student', $user['roles'], true)) {
            self::redirect('/student/dashboard');
        }
        if (in_array('parent_guardian', $user['roles'], true)) {
            self::redirect('/guardian/dashboard');
        }
        if (in_array('teacher', $user['roles'], true)) {
            self::redirect('/teacher/workspace');
        }
        if (in_array('it_support', $user['roles'], true)) {
            self::redirect('/it-support');
        }
        if (array_intersect(['super_administrator', 'school_administrator', 'head_teacher', 'teacher'], $user['roles'])) {
            $stats = [];
            $db = Database::connection();
            $schoolId = $user['school_id'];
            $isSuper = in_array('super_administrator', $user['roles'], true);

            $count = static function (string $sql, array $params = []) use ($db): mixed {
                $statement = $db->prepare($sql);
                $statement->execute($params);
                return $statement->fetchColumn();
            };

            $scope = $isSuper ? 's.id IS NOT NULL' : 's.id = :school_id';
            $schoolParams = $isSuper ? [] : ['school_id' => $schoolId];

            $stats['students'] = $count(
                "SELECT COUNT(*) FROM students s WHERE s.status = 'active' AND $scope",
                $schoolParams
            );
            $stats['teachers'] = $count(
                "SELECT COUNT(DISTINCT u.id)
                 FROM users u
                 INNER JOIN user_roles ur ON ur.user_id = u.id
                 INNER JOIN roles r ON r.id = ur.role_id
                 WHERE r.name = 'teacher' AND u.status = 'active'
                   AND (ur.expires_at IS NULL OR ur.expires_at > CURRENT_TIMESTAMP)
                   AND (" . ($isSuper ? 'u.school_id IS NOT NULL' : 'u.school_id = :school_id') . ')',
                $schoolParams
            );
            $stats['classes'] = $count(
                "SELECT COUNT(*) FROM classes c WHERE c.status = 'active'
                 AND (" . ($isSuper ? 'c.school_id IS NOT NULL' : 'c.school_id = :school_id') . ')',
                $schoolParams
            );
            $stats['today_present'] = $count(
                "SELECT COUNT(*) FROM attendance a
                 INNER JOIN students s ON s.id = a.student_id
                 WHERE a.attendance_date = CURRENT_DATE AND a.status = 'present'
                   AND (" . ($isSuper ? 's.school_id IS NOT NULL' : 's.school_id = :school_id') . ')',
                $schoolParams
            );
            $stats['unpaid_invoices'] = $count(
                "SELECT COUNT(*) FROM fee_invoices i
                 INNER JOIN students s ON s.id = i.student_id
                 WHERE i.status IN ('issued','partially_paid','overdue')
                   AND (" . ($isSuper ? 's.school_id IS NOT NULL' : 's.school_id = :school_id') . ')',
                $schoolParams
            );
            $stats['unpaid_amount'] = $count(
                "SELECT COALESCE(SUM(i.total_amount-i.paid_amount),0) FROM fee_invoices i
                 INNER JOIN students s ON s.id = i.student_id
                 WHERE i.status IN ('issued','partially_paid','overdue')
                   AND (" . ($isSuper ? 's.school_id IS NOT NULL' : 's.school_id = :school_id') . ')',
                $schoolParams
            );
            $stats['collected_income'] = $count(
                "SELECT COALESCE(SUM(i.paid_amount),0) FROM fee_invoices i
                 INNER JOIN students s ON s.id = i.student_id
                 WHERE (" . ($isSuper ? 's.school_id IS NOT NULL' : 's.school_id = :school_id') . ')',
                $schoolParams
            );
            $stats['collected_this_month'] = $count(
                "SELECT COALESCE(SUM(p.amount),0) FROM fee_payments p
                 INNER JOIN fee_invoices i ON i.id = p.invoice_id
                 INNER JOIN students s ON s.id = i.student_id
                 WHERE p.paid_at >= DATE_FORMAT(CURRENT_TIMESTAMP, '%Y-%m-01')
                   AND p.paid_at < DATE_ADD(DATE_FORMAT(CURRENT_TIMESTAMP, '%Y-%m-01'), INTERVAL 1 MONTH)
                   AND (" . ($isSuper ? 's.school_id IS NOT NULL' : 's.school_id = :school_id') . ')',
                $schoolParams
            );
            $stats['payments_this_month'] = $count(
                "SELECT COUNT(*) FROM fee_payments p
                 INNER JOIN fee_invoices i ON i.id = p.invoice_id
                 INNER JOIN students s ON s.id = i.student_id
                 WHERE p.paid_at >= DATE_FORMAT(CURRENT_TIMESTAMP, '%Y-%m-01')
                   AND p.paid_at < DATE_ADD(DATE_FORMAT(CURRENT_TIMESTAMP, '%Y-%m-01'), INTERVAL 1 MONTH)
                   AND (" . ($isSuper ? 's.school_id IS NOT NULL' : 's.school_id = :school_id') . ')',
                $schoolParams
            );
            $stats['paid_invoices'] = $count(
                "SELECT COUNT(*) FROM fee_invoices i
                 INNER JOIN students s ON s.id = i.student_id
                 WHERE i.status = 'paid'
                   AND (" . ($isSuper ? 's.school_id IS NOT NULL' : 's.school_id = :school_id') . ')',
                $schoolParams
            );
            $stats['audit_events'] = $count(
                "SELECT COUNT(*) FROM audit_logs al
                 LEFT JOIN users u ON u.id = al.user_id
                 WHERE " . ($isSuper ? '1=1' : 'u.school_id = :school_id'),
                $schoolParams
            );
            $stats['users'] = $count(
                "SELECT COUNT(*) FROM users u WHERE " . ($isSuper ? '1=1' : 'u.school_id = :school_id'),
                $schoolParams
            );
            $stats['guardians'] = $count(
                "SELECT COUNT(*) FROM guardians g INNER JOIN users u ON u.id = g.user_id
                 WHERE " . ($isSuper ? '1=1' : 'u.school_id = :school_id'),
                $schoolParams
            );
            $stats['sections'] = $count(
                "SELECT COUNT(*) FROM sections sec INNER JOIN classes c ON c.id = sec.class_id
                 WHERE " . ($isSuper ? '1=1' : 'c.school_id = :school_id'),
                $schoolParams
            );
            $stats['subjects'] = $count(
                "SELECT COUNT(*) FROM subjects sub WHERE " . ($isSuper ? '1=1' : 'sub.school_id = :school_id'),
                $schoolParams
            );
            $stats['exams'] = $count(
                "SELECT COUNT(*) FROM exams e INNER JOIN sections sec ON sec.id=e.section_id INNER JOIN classes c ON c.id=sec.class_id
                 WHERE " . ($isSuper ? '1=1' : 'c.school_id = :school_id'),
                $schoolParams
            );
            $stats['grades'] = $count(
                "SELECT COUNT(*) FROM grades g INNER JOIN students s ON s.id=g.student_id
                 WHERE " . ($isSuper ? '1=1' : 's.school_id = :school_id'),
                $schoolParams
            );
            $stats['invoices'] = $count(
                "SELECT COUNT(*) FROM fee_invoices i INNER JOIN students s ON s.id=i.student_id
                 WHERE " . ($isSuper ? '1=1' : 's.school_id = :school_id'),
                $schoolParams
            );
            $stats['payments'] = $count(
                "SELECT COUNT(*) FROM fee_payments p INNER JOIN fee_invoices i ON i.id=p.invoice_id INNER JOIN students s ON s.id=i.student_id
                 WHERE " . ($isSuper ? '1=1' : 's.school_id = :school_id'),
                $schoolParams
            );
            $stats['absent_today'] = $count(
                "SELECT COUNT(*) FROM attendance a INNER JOIN students s ON s.id=a.student_id
                 WHERE a.attendance_date=CURRENT_DATE AND a.status='absent'
                   AND " . ($isSuper ? '1=1' : 's.school_id = :school_id'),
                $schoolParams
            );
            $stats['late_today'] = $count(
                "SELECT COUNT(*) FROM attendance a INNER JOIN students s ON s.id=a.student_id
                 WHERE a.attendance_date=CURRENT_DATE AND a.status='late'
                   AND " . ($isSuper ? '1=1' : 's.school_id = :school_id'),
                $schoolParams
            );
            $stats['suspended_students'] = $count(
                "SELECT COUNT(*) FROM students s WHERE s.status='suspended'
                   AND " . ($isSuper ? '1=1' : 's.school_id = :school_id'),
                $schoolParams
            );
            $stats['expelled_students'] = $count(
                "SELECT COUNT(*) FROM students s WHERE s.status='expelled'
                   AND " . ($isSuper ? '1=1' : 's.school_id = :school_id'),
                $schoolParams
            );

            View::render('dashboard/index', ['user' => $user, 'stats' => $stats]);
            return;
        }
        View::render('dashboard/index', ['user' => $user, 'stats' => []]);
    }

    private static function failLogin(): never
    {
        $_SESSION['flash_error'] = 'Email or password is incorrect.';
        self::redirect('/login');
    }

    private static function redirect(string $location): never
    {
        header('Location: ' . $location, true, 303);
        exit;
    }
}
