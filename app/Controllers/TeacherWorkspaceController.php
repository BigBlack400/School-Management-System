<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;
use DateTimeImmutable;

final class TeacherWorkspaceController
{
    public function index(): void
    {
        $user = Auth::requireRole(['teacher']);
        $db = Database::connection();

        $allocations = $db->prepare(
            'SELECT ta.id, ta.section_id, ta.subject_id,
                    c.name AS class_name, c.grade_level,
                    sec.name AS section_name,
                    sub.name AS subject_name, sub.code AS subject_code
             FROM teacher_allocations ta
             INNER JOIN sections sec ON sec.id = ta.section_id
             INNER JOIN classes c ON c.id = sec.class_id
             INNER JOIN subjects sub ON sub.id = ta.subject_id
             WHERE ta.teacher_id = :teacher_id
               AND ta.status = "active"
               AND c.status = "active" AND sec.status = "active"
             ORDER BY c.grade_level, c.name, sec.name, sub.name'
        );
        $allocations->execute(['teacher_id' => $user['id']]);
        $allocationRows = $allocations->fetchAll();

        $allocationIds = array_map(static fn (array $row): int => (int) $row['id'], $allocationRows);
        $students = [];
        $exams = [];
        $assignments = [];

        if ($allocationIds) {
            $placeholders = implode(',', array_fill(0, count($allocationIds), '?'));
            $studentQuery = $db->prepare(
                'SELECT s.id, s.admission_number, s.first_name, s.last_name,
                        s.section_id, sec.name AS section_name,
                        c.name AS class_name
                 FROM students s
                 INNER JOIN sections sec ON sec.id = s.section_id
                 INNER JOIN classes c ON c.id = sec.class_id
                 WHERE s.status = "active" AND s.section_id IN (
                    SELECT ta.section_id FROM teacher_allocations ta
                    WHERE ta.teacher_id = ? AND ta.status = "active"
                 )
                 ORDER BY c.grade_level, c.name, sec.name, s.last_name, s.first_name'
            );
            $studentQuery->execute([(int) $user['id']]);
            $students = $studentQuery->fetchAll();

            $examQuery = $db->prepare(
                'SELECT e.id, e.name, e.exam_date, e.max_marks,
                        e.section_id, e.subject_id,
                        sec.name AS section_name, c.name AS class_name,
                        sub.name AS subject_name
                 FROM exams e
                 INNER JOIN sections sec ON sec.id = e.section_id
                 INNER JOIN classes c ON c.id = sec.class_id
                 INNER JOIN subjects sub ON sub.id = e.subject_id
                 WHERE EXISTS (
                    SELECT 1 FROM teacher_allocations ta
                    WHERE ta.teacher_id = :teacher_id AND ta.status = "active"
                      AND ta.section_id = e.section_id AND ta.subject_id = e.subject_id
                 )
                 ORDER BY e.exam_date DESC, c.grade_level, c.name, sec.name'
            );
            $examQuery->execute(['teacher_id' => $user['id']]);
            $exams = $examQuery->fetchAll();

            $assignmentQuery = $db->prepare(
                'SELECT a.id, a.title, a.instructions, a.due_date, a.max_marks, a.status,
                        sec.name AS section_name, c.name AS class_name,
                        sub.name AS subject_name
                 FROM assignments a
                 INNER JOIN sections sec ON sec.id = a.section_id
                 INNER JOIN classes c ON c.id = sec.class_id
                 INNER JOIN subjects sub ON sub.id = a.subject_id
                 WHERE a.teacher_id = :teacher_id
                 ORDER BY a.due_date DESC, a.created_at DESC'
            );
            $assignmentQuery->execute(['teacher_id' => $user['id']]);
            $assignments = $assignmentQuery->fetchAll();
        }

        View::render('teacher/workspace', [
            'user' => $user,
            'csrf' => Csrf::token(),
            'allocations' => $allocationRows,
            'students' => $students,
            'exams' => $exams,
            'assignments' => $assignments,
            'message' => $_SESSION['flash_success'] ?? null,
            'error' => $_SESSION['flash_error'] ?? null,
        ]);
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function saveMarks(): void
    {
        $user = Auth::requireRole(['teacher']);
        Csrf::validateRequest();

        $examId = filter_var($_POST['exam_id'] ?? null, FILTER_VALIDATE_INT);
        $marks = $_POST['marks'] ?? null;
        $comments = $_POST['comments'] ?? [];
        if (!$examId || !is_array($marks)) {
            $this->fail('Select an exam and submit valid marks.');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $exam = $db->prepare(
                'SELECT e.id, e.section_id, e.subject_id, e.max_marks
                 FROM exams e
                 WHERE e.id = :exam_id
                   AND EXISTS (
                       SELECT 1 FROM teacher_allocations ta
                       WHERE ta.teacher_id = :teacher_id
                         AND ta.status = "active"
                         AND ta.section_id = e.section_id
                         AND ta.subject_id = e.subject_id
                   )
                 LIMIT 1'
            );
            $exam->execute(['exam_id' => (int) $examId, 'teacher_id' => $user['id']]);
            $examRow = $exam->fetch();
            if (!$examRow) {
                throw new \RuntimeException('This exam is outside your subject allocation.');
            }
            if (count($marks) > 500) {
                throw new \RuntimeException('Too many student marks submitted.');
            }

            $upsert = $db->prepare(
                'INSERT INTO grades
                    (exam_id, student_id, marks_obtained, letter_grade, teacher_comment, entered_by, entered_at)
                 VALUES (:exam_id, :student_id, :marks_obtained, :letter_grade, :comment, :entered_by, CURRENT_TIMESTAMP)
                 ON DUPLICATE KEY UPDATE marks_obtained = :updated_marks,
                     letter_grade = :updated_grade,
                     teacher_comment = :updated_comment,
                     entered_by = :updated_entered_by,
                     updated_at = CURRENT_TIMESTAMP'
            );
            $saved = 0;
            foreach ($marks as $studentIdValue => $markValue) {
                $studentId = filter_var($studentIdValue, FILTER_VALIDATE_INT);
                if (!$studentId || $markValue === '' || $markValue === null) {
                    continue;
                }
                if (!is_numeric($markValue)) {
                    throw new \RuntimeException('All submitted marks must be numeric.');
                }
                $marksObtained = (float) $markValue;
                if ($marksObtained < 0 || $marksObtained > (float) $examRow['max_marks']) {
                    throw new \RuntimeException('Marks must be between zero and the exam maximum.');
                }

                $studentCheck = $db->prepare(
                    'SELECT id FROM students
                     WHERE id = :student_id
                       AND section_id = :section_id
                       AND status = "active"
                     LIMIT 1'
                );
                $studentCheck->execute([
                    'student_id' => (int) $studentId,
                    'section_id' => (int) $examRow['section_id'],
                ]);
                if (!$studentCheck->fetchColumn()) {
                    throw new \RuntimeException('A submitted student is outside this exam section.');
                }

                $percentage = $marksObtained / (float) $examRow['max_marks'] * 100;
                $grade = $percentage >= 90 ? 'A' : ($percentage >= 80 ? 'B' : ($percentage >= 70 ? 'C' : ($percentage >= 60 ? 'D' : 'F')));
                $comment = trim((string) ($comments[$studentId] ?? ''));
                if (mb_strlen($comment) > 1000) {
                    throw new \RuntimeException('A student comment exceeds 1000 characters.');
                }
                $upsert->execute([
                    'exam_id' => (int) $examId,
                    'student_id' => (int) $studentId,
                    'marks_obtained' => number_format($marksObtained, 2, '.', ''),
                    'letter_grade' => $grade,
                    'comment' => $comment !== '' ? $comment : null,
                    'entered_by' => $user['id'],
                    'updated_marks' => number_format($marksObtained, 2, '.', ''),
                    'updated_grade' => $grade,
                    'updated_comment' => $comment !== '' ? $comment : null,
                    'updated_entered_by' => $user['id'],
                ]);
                $saved++;
            }
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->fail($exception instanceof \RuntimeException
                ? $exception->getMessage()
                : 'The marks could not be saved.');
        }

        AuditLogger::record((int) $user['id'], 'grades.marks_entered', 'exam', (int) $examId, [
            'student_mark_count' => $saved,
        ]);
        $_SESSION['flash_success'] = "$saved student mark(s) saved.";
        header('Location: /teacher/workspace', true, 303);
        exit;
    }

    public function createAssignment(): void
    {
        $user = Auth::requireRole(['teacher']);
        Csrf::validateRequest();

        $allocationId = filter_var($_POST['allocation_id'] ?? null, FILTER_VALIDATE_INT);
        $title = trim((string) ($_POST['title'] ?? ''));
        $instructions = trim((string) ($_POST['instructions'] ?? ''));
        $dueDate = trim((string) ($_POST['due_date'] ?? ''));
        $maxMarks = trim((string) ($_POST['max_marks'] ?? '100'));

        if (!$allocationId || $title === '' || mb_strlen($title) > 160 ||
            mb_strlen($instructions) > 10000 ||
            ($dueDate !== '' && !$this->validDate($dueDate)) ||
            !is_numeric($maxMarks) || (float) $maxMarks <= 0) {
            $this->fail('Enter valid allocation, title, due date, instructions, and marks.');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $allocation = $db->prepare(
                'SELECT ta.section_id, ta.subject_id
                 FROM teacher_allocations ta
                 WHERE ta.id = :id
                   AND ta.teacher_id = :teacher_id
                   AND ta.status = "active"
                 LIMIT 1'
            );
            $allocation->execute(['id' => (int) $allocationId, 'teacher_id' => $user['id']]);
            $row = $allocation->fetch();
            if (!$row) {
                throw new \RuntimeException('This class/subject is outside your active allocation.');
            }

            $insert = $db->prepare(
                'INSERT INTO assignments
                    (section_id, subject_id, teacher_id, title, instructions, due_date, max_marks, status, created_at)
                 VALUES (:section_id, :subject_id, :teacher_id, :title, :instructions, :due_date,
                         :max_marks, "published", CURRENT_TIMESTAMP)'
            );
            $insert->execute([
                'section_id' => (int) $row['section_id'],
                'subject_id' => (int) $row['subject_id'],
                'teacher_id' => $user['id'],
                'title' => $title,
                'instructions' => $instructions !== '' ? $instructions : null,
                'due_date' => $dueDate !== '' ? $dueDate : null,
                'max_marks' => number_format((float) $maxMarks, 2, '.', ''),
            ]);
            $assignmentId = (int) $db->lastInsertId();
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->fail($exception instanceof \RuntimeException
                ? $exception->getMessage()
                : 'The assignment could not be created.');
        }

        AuditLogger::record((int) $user['id'], 'assignments.created', 'assignment', $assignmentId, [
            'allocation_id' => (int) $allocationId,
            'title' => $title,
        ]);
        $_SESSION['flash_success'] = 'Assignment published to the allocated class.';
        header('Location: /teacher/workspace', true, 303);
        exit;
    }

    private function validDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function fail(string $message): never
    {
        $_SESSION['flash_error'] = $message;
        header('Location: /teacher/workspace', true, 303);
        exit;
    }
}
