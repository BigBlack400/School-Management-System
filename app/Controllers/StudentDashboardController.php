<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

final class StudentDashboardController
{
    public function index(): void
    {
        $user = Auth::requireRole(['student']);
        $db = Database::connection();
        $studentQuery = $db->prepare(
            'SELECT s.id, s.admission_number, s.first_name, s.last_name, s.date_of_birth,
                    sec.name AS section_name, c.name AS class_name
             FROM students s
             LEFT JOIN sections sec ON sec.id = s.section_id
             LEFT JOIN classes c ON c.id = sec.class_id
             WHERE s.user_id = :user_id AND s.status = "active"
             LIMIT 1'
        );
        $studentQuery->execute(['user_id' => $user['id']]);
        $student = $studentQuery->fetch();
        if (!$student) {
            http_response_code(404);
            echo 'No active student profile is linked to this account.';
            return;
        }

        $attendance = $db->prepare(
            "SELECT status, COUNT(*) AS total FROM attendance
             WHERE student_id = :student_id AND attendance_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
             GROUP BY status"
        );
        $attendance->execute(['student_id' => $student['id']]);
        $attendanceSummary = $attendance->fetchAll();

        $grades = $db->prepare(
            'SELECT e.name AS exam_name, sub.name AS subject_name, g.marks_obtained, e.max_marks,
                    g.letter_grade, e.exam_date
             FROM grades g
             INNER JOIN exams e ON e.id = g.exam_id
             INNER JOIN subjects sub ON sub.id = e.subject_id
             WHERE g.student_id = :student_id
             ORDER BY e.exam_date DESC LIMIT 8'
        );
        $grades->execute(['student_id' => $student['id']]);

        $fees = $db->prepare(
            'SELECT id, invoice_number, due_date, total_amount, paid_amount,
                    CAST(total_amount - paid_amount AS DECIMAL(12,2)) AS balance, status
             FROM fee_invoices
             WHERE student_id = :student_id AND status IN ("issued", "partially_paid", "overdue")
             ORDER BY due_date ASC LIMIT 5'
        );
        $fees->execute(['student_id' => $student['id']]);

        View::render('student/dashboard', [
            'user' => $user,
            'student' => $student,
            'attendanceSummary' => $attendanceSummary,
            'grades' => $grades->fetchAll(),
            'invoices' => $fees->fetchAll(),
        ]);
    }
}
