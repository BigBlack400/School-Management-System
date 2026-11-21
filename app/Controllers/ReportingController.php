<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

final class ReportingController
{
    public function index(): void
    {
        $user = Auth::requireRole(['head_teacher', 'super_administrator']);
        $db = Database::connection();
        $isSuper = in_array('super_administrator', $user['roles'], true);
        $params = $isSuper ? [] : ['school_id' => $user['school_id']];

        $value = static function (string $sql, array $params = []) use ($db): mixed {
            $statement = $db->prepare($sql);
            $statement->execute($params);
            return $statement->fetchColumn();
        };

        $schoolFilter = $isSuper ? '1 = 1' : 's.school_id = :school_id';
        $classFilter = $isSuper ? '1 = 1' : 'c.school_id = :school_id';
        $invoiceFilter = $isSuper ? '1 = 1' : 'i.school_id = :school_id';

        $reports = [
            'gpa_average' => $value(
                'SELECT COALESCE(AVG(gp.gpa_points),0)
                 FROM grades g
                 INNER JOIN exams e ON e.id=g.exam_id
                 INNER JOIN subjects sub ON sub.id=e.subject_id
                 INNER JOIN sections sec ON sec.id=e.section_id
                 INNER JOIN classes c ON c.id=sec.class_id
                 INNER JOIN grade_scales gp ON gp.school_id=c.school_id
                    AND ROUND((g.marks_obtained/e.max_marks)*100,2) BETWEEN gp.min_percent AND gp.max_percent
                 WHERE ' . $classFilter,
                $params
            ),
            'failing_grades' => $value(
                'SELECT COUNT(*) FROM grades g
                 INNER JOIN exams e ON e.id=g.exam_id
                 INNER JOIN sections sec ON sec.id=e.section_id
                 INNER JOIN classes c ON c.id=sec.class_id
                 WHERE c.school_id IS NOT NULL AND ' . $classFilter . ' AND g.letter_grade="F"',
                $params
            ),
            'grades_recorded' => $value(
                'SELECT COUNT(*) FROM grades g
                 INNER JOIN students s ON s.id=g.student_id
                 WHERE ' . $schoolFilter,
                $params
            ),
            'income_collected' => $value(
                'SELECT COALESCE(SUM(i.paid_amount),0)
                 FROM fee_invoices i
                 INNER JOIN students s ON s.id=i.student_id
                 WHERE ' . $schoolFilter,
                $params
            ),
            'outstanding_fees' => $value(
                'SELECT COALESCE(SUM(i.total_amount-i.paid_amount),0)
                 FROM fee_invoices i
                 INNER JOIN students s ON s.id=i.student_id
                 WHERE i.status IN ("issued","partially_paid","overdue") AND ' . $schoolFilter,
                $params
            ),
            'expenses' => $value(
                'SELECT COALESCE(SUM(se.amount),0)
                 FROM school_expenses se
                 WHERE ' . ($isSuper ? '1 = 1' : 'se.school_id = :school_id'),
                $params
            ),
            'present' => $value(
                'SELECT COUNT(*) FROM attendance a
                 INNER JOIN students s ON s.id=a.student_id
                 WHERE a.attendance_date=CURRENT_DATE AND a.status="present" AND ' . $schoolFilter,
                $params
            ),
            'absent' => $value(
                'SELECT COUNT(*) FROM attendance a
                 INNER JOIN students s ON s.id=a.student_id
                 WHERE a.attendance_date=CURRENT_DATE AND a.status="absent" AND ' . $schoolFilter,
                $params
            ),
            'late' => $value(
                'SELECT COUNT(*) FROM attendance a
                 INNER JOIN students s ON s.id=a.student_id
                 WHERE a.attendance_date=CURRENT_DATE AND a.status="late" AND ' . $schoolFilter,
                $params
            ),
            'audit_events' => $value(
                'SELECT COUNT(*) FROM audit_logs al
                 LEFT JOIN users u ON u.id=al.user_id
                 WHERE ' . ($isSuper ? '1 = 1' : 'u.school_id = :school_id'),
                $params
            ),
        ];

        View::render('reports/index', [
            'user' => $user,
            'reports' => $reports,
        ]);
    }
}
