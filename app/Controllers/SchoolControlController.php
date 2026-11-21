<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

final class SchoolControlController
{
    public function index(): void
    {
        $user = Auth::requireRole(['school_administrator', 'super_administrator', 'head_teacher']);
        $db = Database::connection();
        $isSuper = in_array('super_administrator', $user['roles'], true);
        $schoolId = $user['school_id'];

        $one = static function (string $sql, array $params = []) use ($db): int {
            $statement = $db->prepare($sql);
            $statement->execute($params);
            return (int) $statement->fetchColumn();
        };

        $params = $isSuper ? [] : ['school_id' => $schoolId];
        $stats = [
            'users' => $one("SELECT COUNT(*) FROM users u WHERE " . ($isSuper ? '1 = 1' : 'u.school_id = :school_id'), $params),
            'students' => $one("SELECT COUNT(*) FROM students s WHERE " . ($isSuper ? '1 = 1' : 's.school_id = :school_id'), $params),
            'teachers' => $one(
                "SELECT COUNT(DISTINCT u.id)
                 FROM users u
                 INNER JOIN user_roles ur ON ur.user_id = u.id
                 INNER JOIN roles r ON r.id = ur.role_id
                 WHERE r.name = 'teacher'
                   AND (ur.expires_at IS NULL OR ur.expires_at > CURRENT_TIMESTAMP)
                   AND " . ($isSuper ? '1 = 1' : 'u.school_id = :school_id'),
                $params
            ),
            'guardians' => $one("SELECT COUNT(*) FROM guardians g INNER JOIN users u ON u.id = g.user_id WHERE " . ($isSuper ? '1 = 1' : 'u.school_id = :school_id'), $params),
            'classes' => $one("SELECT COUNT(*) FROM classes c WHERE " . ($isSuper ? '1 = 1' : 'c.school_id = :school_id'), $params),
            'sections' => $one("SELECT COUNT(*) FROM sections sec INNER JOIN classes c ON c.id = sec.class_id WHERE " . ($isSuper ? '1 = 1' : 'c.school_id = :school_id'), $params),
            'subjects' => $one("SELECT COUNT(*) FROM subjects sub WHERE " . ($isSuper ? '1 = 1' : 'sub.school_id = :school_id'), $params),
            'exams' => $one("SELECT COUNT(*) FROM exams e INNER JOIN sections sec ON sec.id = e.section_id INNER JOIN classes c ON c.id = sec.class_id WHERE " . ($isSuper ? '1 = 1' : 'c.school_id = :school_id'), $params),
            'grades' => $one("SELECT COUNT(*) FROM grades g INNER JOIN students s ON s.id = g.student_id WHERE " . ($isSuper ? '1 = 1' : 's.school_id = :school_id'), $params),
            'invoices' => $one("SELECT COUNT(*) FROM fee_invoices i INNER JOIN students s ON s.id = i.student_id WHERE " . ($isSuper ? '1 = 1' : 's.school_id = :school_id'), $params),
            'payments' => $one("SELECT COUNT(*) FROM fee_payments p INNER JOIN fee_invoices i ON i.id = p.invoice_id INNER JOIN students s ON s.id = i.student_id WHERE " . ($isSuper ? '1 = 1' : 's.school_id = :school_id'), $params),
            'income' => $one("SELECT COALESCE(SUM(i.paid_amount),0) FROM fee_invoices i INNER JOIN students s ON s.id = i.student_id WHERE " . ($isSuper ? '1 = 1' : 's.school_id = :school_id'), $params),
            'audit' => $one("SELECT COUNT(*) FROM audit_logs al LEFT JOIN users u ON u.id = al.user_id WHERE " . ($isSuper ? '1 = 1' : 'u.school_id = :school_id'), $params),
        ];

        $recentAudit = $db->prepare(
            'SELECT al.id, al.action, al.entity_type, al.entity_id, al.ip_address, al.created_at,
                    u.first_name, u.last_name
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE ' . ($isSuper ? '1 = 1' : 'u.school_id = :school_id') . '
             ORDER BY al.created_at DESC, al.id DESC
             LIMIT 25'
        );
        $recentAudit->execute($params);

        $recentUsers = $db->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.status, u.created_at,
                    GROUP_CONCAT(DISTINCT r.label ORDER BY r.label SEPARATOR ", ") AS roles
             FROM users u
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r ON r.id = ur.role_id
             WHERE ' . ($isSuper ? '1 = 1' : 'u.school_id = :school_id') . '
             GROUP BY u.id, u.first_name, u.last_name, u.email, u.status, u.created_at
             ORDER BY u.created_at DESC
             LIMIT 25'
        );
        $recentUsers->execute($params);

        $recentStudents = $db->prepare(
            'SELECT s.id, s.admission_number, s.first_name, s.last_name, s.status,
                    c.name AS class_name, sec.name AS section_name
             FROM students s
             LEFT JOIN sections sec ON sec.id = s.section_id
             LEFT JOIN classes c ON c.id = sec.class_id
             WHERE ' . ($isSuper ? '1 = 1' : 's.school_id = :school_id') . '
             ORDER BY s.created_at DESC
             LIMIT 25'
        );
        $recentStudents->execute($params);

        View::render('school/control', [
            'user' => $user,
            'stats' => $stats,
            'audit' => $recentAudit->fetchAll(),
            'users' => $recentUsers->fetchAll(),
            'students' => $recentStudents->fetchAll(),
        ]);
    }
}
