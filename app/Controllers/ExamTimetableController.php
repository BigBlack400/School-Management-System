<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;
use DateTimeImmutable;

final class ExamTimetableController
{
    public function index(): void
    {
        $user = Auth::requirePermission('academics.manage');
        $db = Database::connection();
        $isSuper = in_array('super_administrator', $user['roles'], true);
        $params = $isSuper ? [] : ['school_id' => $user['school_id']];

        $sections = $db->prepare(
            'SELECT sec.id, sec.name AS section_name, c.name AS class_name
             FROM sections sec INNER JOIN classes c ON c.id = sec.class_id
             WHERE sec.status = "active" AND c.status = "active"
               AND ' . ($isSuper ? '1 = 1' : 'c.school_id = :school_id') . '
             ORDER BY c.grade_level, c.name, sec.name'
        );
        $sections->execute($params);

        $subjects = $db->prepare(
            'SELECT id, code, name FROM subjects
             WHERE status = "active"
               AND ' . ($isSuper ? '1 = 1' : 'school_id = :school_id') . '
             ORDER BY name'
        );
        $subjects->execute($params);

        $terms = $db->prepare(
            'SELECT t.id, t.name, ay.name AS academic_year
             FROM terms t INNER JOIN academic_years ay ON ay.id = t.academic_year_id
             WHERE ' . ($isSuper ? '1 = 1' : 'ay.school_id = :school_id') . '
             ORDER BY ay.starts_on DESC, t.starts_on'
        );
        $terms->execute($params);

        $timetable = $db->prepare(
            'SELECT e.id, e.name, e.exam_date, e.starts_at, e.ends_at, e.room,
                    e.max_marks, e.weight_percent,
                    sub.name AS subject_name, sub.code AS subject_code,
                    c.name AS class_name, sec.name AS section_name
             FROM exams e
             INNER JOIN subjects sub ON sub.id = e.subject_id
             INNER JOIN sections sec ON sec.id = e.section_id
             INNER JOIN classes c ON c.id = sec.class_id
             WHERE ' . ($isSuper ? '1 = 1' : 'c.school_id = :school_id') . '
             ORDER BY e.exam_date, e.starts_at, c.grade_level, c.name, sec.name, sub.name'
        );
        $timetable->execute($params);

        View::render('exams/timetable', [
            'user' => $user,
            'csrf' => Csrf::token(),
            'sections' => $sections->fetchAll(),
            'subjects' => $subjects->fetchAll(),
            'terms' => $terms->fetchAll(),
            'timetable' => $timetable->fetchAll(),
            'message' => $_SESSION['flash_success'] ?? null,
            'error' => $_SESSION['flash_error'] ?? null,
        ]);
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function store(): void
    {
        $user = Auth::requirePermission('academics.manage');
        Csrf::validateRequest();

        $name = trim((string) ($_POST['name'] ?? ''));
        $sectionId = filter_var($_POST['section_id'] ?? null, FILTER_VALIDATE_INT);
        $subjectId = filter_var($_POST['subject_id'] ?? null, FILTER_VALIDATE_INT);
        $termId = filter_var($_POST['term_id'] ?? null, FILTER_VALIDATE_INT);
        $examDate = trim((string) ($_POST['exam_date'] ?? ''));
        $startsAt = trim((string) ($_POST['starts_at'] ?? ''));
        $endsAt = trim((string) ($_POST['ends_at'] ?? ''));
        $room = trim((string) ($_POST['room'] ?? ''));
        $maxMarks = trim((string) ($_POST['max_marks'] ?? ''));
        $weight = trim((string) ($_POST['weight_percent'] ?? '100'));

        if ($name === '' || mb_strlen($name) > 160 || mb_strlen($room) > 80 ||
            !$sectionId || !$subjectId || !$termId ||
            !$this->validDate($examDate) || !$this->validTime($startsAt) ||
            !$this->validTime($endsAt) || strcmp($endsAt, $startsAt) <= 0 ||
            !is_numeric($maxMarks) || (float) $maxMarks <= 0 ||
            !is_numeric($weight) || (float) $weight <= 0 || (float) $weight > 100) {
            $this->fail('Enter valid exam, section, subject, term, date, time, room, marks, and weight.');
        }

        $db = Database::connection();
        $isSuper = in_array('super_administrator', $user['roles'], true);
        $db->beginTransaction();
        try {
            $scopeParams = ['section_id' => (int) $sectionId];
            if (!$isSuper) {
                $scopeParams['school_id'] = $user['school_id'];
            }
            $section = $db->prepare(
                'SELECT sec.id
                 FROM sections sec INNER JOIN classes c ON c.id = sec.class_id
                 WHERE sec.id = :section_id
                   AND ' . ($isSuper ? '1 = 1' : 'c.school_id = :school_id') . '
                 LIMIT 1'
            );
            $section->execute($scopeParams);
            if (!$section->fetchColumn()) {
                throw new \RuntimeException('The selected section is outside your school scope.');
            }

            $subjectScope = $db->prepare(
                'SELECT id FROM subjects
                 WHERE id = :id
                   AND ' . ($isSuper ? '1 = 1' : 'school_id = :school_id') . '
                 LIMIT 1'
            );
            $subjectParams = ['id' => (int) $subjectId];
            if (!$isSuper) {
                $subjectParams['school_id'] = $user['school_id'];
            }
            $subjectScope->execute($subjectParams);
            if (!$subjectScope->fetchColumn()) {
                throw new \RuntimeException('The selected subject is outside your school scope.');
            }

            $termScope = $db->prepare(
                'SELECT t.id FROM terms t INNER JOIN academic_years ay ON ay.id = t.academic_year_id
                 WHERE t.id = :id
                   AND ' . ($isSuper ? '1 = 1' : 'ay.school_id = :school_id') . '
                 LIMIT 1'
            );
            $termParams = ['id' => (int) $termId];
            if (!$isSuper) {
                $termParams['school_id'] = $user['school_id'];
            }
            $termScope->execute($termParams);
            if (!$termScope->fetchColumn()) {
                throw new \RuntimeException('The selected term is outside your school scope.');
            }

            $conflict = $db->prepare(
                'SELECT id FROM exams
                 WHERE section_id = :section_id
                   AND exam_date = :exam_date
                   AND ((:starts_at < ends_at AND :ends_at > starts_at))
                 LIMIT 1'
            );
            $conflict->execute([
                'section_id' => (int) $sectionId,
                'exam_date' => $examDate,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);
            if ($conflict->fetchColumn()) {
                throw new \RuntimeException('This section already has an exam scheduled in that time slot.');
            }

            $insert = $db->prepare(
                'INSERT INTO exams
                    (section_id, subject_id, term_id, name, exam_date, starts_at, ends_at, room,
                     max_marks, weight_percent, created_by, created_at)
                 VALUES (:section_id, :subject_id, :term_id, :name, :exam_date, :starts_at, :ends_at,
                         :room, :max_marks, :weight_percent, :created_by, CURRENT_TIMESTAMP)'
            );
            $insert->execute([
                'section_id' => (int) $sectionId,
                'subject_id' => (int) $subjectId,
                'term_id' => (int) $termId,
                'name' => $name,
                'exam_date' => $examDate,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'room' => $room !== '' ? $room : null,
                'max_marks' => number_format((float) $maxMarks, 2, '.', ''),
                'weight_percent' => number_format((float) $weight, 2, '.', ''),
                'created_by' => $user['id'],
            ]);
            $examId = (int) $db->lastInsertId();
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->fail($exception instanceof \RuntimeException
                ? $exception->getMessage()
                : 'The examination timetable entry could not be created.');
        }

        AuditLogger::record((int) $user['id'], 'exams.timetable_created', 'exam', $examId, [
            'exam_date' => $examDate,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'room' => $room,
        ]);
        $_SESSION['flash_success'] = 'Examination timetable entry created.';
        header('Location: /exams/timetable', true, 303);
        exit;
    }

    private function validDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function validTime(string $value): bool
    {
        $time = DateTimeImmutable::createFromFormat('!H:i', $value);
        return $time !== false && $time->format('H:i') === $value;
    }

    private function fail(string $message): never
    {
        $_SESSION['flash_error'] = $message;
        header('Location: /exams/timetable', true, 303);
        exit;
    }
}
