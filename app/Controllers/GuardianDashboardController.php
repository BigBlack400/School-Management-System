<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

final class GuardianDashboardController
{
    public function index(): void
    {
        $user = Auth::requireRole(['parent_guardian']);
        $db = Database::connection();
        $childrenQuery = $db->prepare(
            'SELECT s.id, s.admission_number, s.first_name, s.last_name,
                    sec.name AS section_name, c.name AS class_name
             FROM guardians g
             INNER JOIN student_guardians sg ON sg.guardian_id = g.id
             INNER JOIN students s ON s.id = sg.student_id
             LEFT JOIN sections sec ON sec.id = s.section_id
             LEFT JOIN classes c ON c.id = sec.class_id
             WHERE g.user_id = :user_id AND s.status = "active"
             ORDER BY s.last_name, s.first_name LIMIT 50'
        );
        $childrenQuery->execute(['user_id' => $user['id']]);
        $children = $childrenQuery->fetchAll();

        $attendanceQuery = $db->prepare(
            "SELECT status, COUNT(*) AS total FROM attendance
             WHERE student_id = :student_id AND attendance_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
             GROUP BY status"
        );
        $gradesQuery = $db->prepare(
            'SELECT e.name AS exam_name, sub.name AS subject_name, g.marks_obtained,
                    e.max_marks, g.letter_grade
             FROM grades g
             INNER JOIN exams e ON e.id = g.exam_id
             INNER JOIN subjects sub ON sub.id = e.subject_id
             WHERE g.student_id = :student_id ORDER BY e.exam_date DESC LIMIT 5'
        );
        $invoiceQuery = $db->prepare(
            'SELECT id, invoice_number, due_date, currency,
                    CAST(total_amount - paid_amount AS DECIMAL(12,2)) AS balance, status
             FROM fee_invoices WHERE student_id = :student_id
               AND status IN ("issued", "partially_paid", "overdue")
             ORDER BY due_date ASC LIMIT 5'
        );

        foreach ($children as &$child) {
            $attendanceQuery->execute(['student_id' => $child['id']]);
            $child['attendance'] = $attendanceQuery->fetchAll();
            $gradesQuery->execute(['student_id' => $child['id']]);
            $child['grades'] = $gradesQuery->fetchAll();
            $invoiceQuery->execute(['student_id' => $child['id']]);
            $child['invoices'] = $invoiceQuery->fetchAll();
        }
        unset($child);

        View::render('guardian/dashboard', ['user' => $user, 'children' => $children]);
    }
}
