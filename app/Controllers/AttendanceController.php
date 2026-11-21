<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;
use DateTimeImmutable;
use PDO;

final class AttendanceController
{
    public function index(): void
    {
        $user = Auth::requirePermission('attendance.manage');
        $date = $this->validatedDate((string) ($_GET['date'] ?? date('Y-m-d')));
        $sections = $this->accessibleSections($user);
        $sectionId = filter_var($_GET['section_id'] ?? null, FILTER_VALIDATE_INT);
        $students = [];
        if ($sectionId !== false && $sectionId !== null) {
            $this->assertSectionAccess($user, (int) $sectionId);
            $query = Database::connection()->prepare(
                'SELECT s.id, s.admission_number, s.first_name, s.last_name, a.status, a.remarks
                 FROM students s
                 LEFT JOIN attendance a ON a.student_id = s.id AND a.attendance_date = :date
                 WHERE s.section_id = :section_id AND s.status = "active"
                 ORDER BY s.last_name, s.first_name'
            );
            $query->execute(['date' => $date, 'section_id' => $sectionId]);
            $students = $query->fetchAll();
        }
        View::render('attendance/index', [
            'user' => $user,
            'csrf' => Csrf::token(),
            'date' => $date,
            'sections' => $sections,
            'selectedSection' => $sectionId ?: null,
            'students' => $students,
        ]);
    }

    public function store(): void
    {
        $user = Auth::requirePermission('attendance.manage');
        Csrf::validateRequest();
        $sectionId = filter_var($_POST['section_id'] ?? null, FILTER_VALIDATE_INT);
        $date = $this->validatedDate((string) ($_POST['attendance_date'] ?? ''));
        $records = $_POST['attendance'] ?? null;
        if ($sectionId === false || $sectionId === null || !is_array($records) || count($records) > 500) {
            http_response_code(422);
            exit('Invalid attendance submission.');
        }
        $this->assertSectionAccess($user, (int) $sectionId);

        $db = Database::connection();
        $validStatuses = ['present', 'absent', 'late', 'excused'];
        $studentLookup = $db->prepare('SELECT id FROM students WHERE id = :id AND section_id = :section_id AND status = "active"');
        $upsert = $db->prepare(
            'INSERT INTO attendance (student_id, attendance_date, status, remarks, marked_by, created_at, updated_at)
             VALUES (:student_id, :date, :status, :remarks, :marked_by, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
             ON DUPLICATE KEY UPDATE status = :updated_status, remarks = :updated_remarks,
               marked_by = :updated_by, updated_at = CURRENT_TIMESTAMP'
        );

        $db->beginTransaction();
        try {
            foreach ($records as $studentId => $record) {
                $id = filter_var($studentId, FILTER_VALIDATE_INT);
                if ($id === false || !is_array($record)) {
                    throw new \InvalidArgumentException('Invalid attendance record.');
                }
                $status = (string) ($record['status'] ?? '');
                $remarks = trim((string) ($record['remarks'] ?? ''));
                if (!in_array($status, $validStatuses, true) || mb_strlen($remarks) > 500) {
                    throw new \InvalidArgumentException('Invalid attendance status or remarks.');
                }
                $studentLookup->execute(['id' => $id, 'section_id' => $sectionId]);
                if (!$studentLookup->fetchColumn()) {
                    throw new \InvalidArgumentException('A submitted student does not belong to this section.');
                }
                $values = [
                    'student_id' => $id,
                    'date' => $date,
                    'status' => $status,
                    'remarks' => $remarks !== '' ? $remarks : null,
                    'marked_by' => $user['id'],
                    'updated_status' => $status,
                    'updated_remarks' => $remarks !== '' ? $remarks : null,
                    'updated_by' => $user['id'],
                ];
                $upsert->execute($values);
            }
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }

        AuditLogger::record((int) $user['id'], 'attendance.recorded', 'section', (int) $sectionId, [
            'date' => $date,
            'record_count' => count($records),
        ]);
        header('Location: /attendance?section_id=' . (int) $sectionId . '&date=' . rawurlencode($date), true, 303);
        exit;
    }

    /** @param array<string, mixed> $user */
    private function accessibleSections(array $user): array
    {
        $sql = 'SELECT sec.id, sec.name, c.name AS class_name
                FROM sections sec INNER JOIN classes c ON c.id = sec.class_id
                WHERE (:is_super = 1 OR c.school_id = :school_id)
                  AND (:branch_unrestricted = 1 OR c.branch_id = :section_branch_id) AND c.status = "active"';
        $params = [
            'is_super' => in_array('super_administrator', $user['roles'], true) ? 1 : 0,
            'school_id' => $user['school_id'],
            'branch_unrestricted' => $user['branch_id'] === null ? 1 : 0,
            'section_branch_id' => $user['branch_id'],
        ];
        if (in_array('teacher', $user['roles'], true) && !in_array('school_administrator', $user['roles'], true) && !in_array('super_administrator', $user['roles'], true) && !in_array('head_teacher', $user['roles'], true)) {
            $sql .= ' AND EXISTS (SELECT 1 FROM teacher_allocations ta WHERE ta.section_id = sec.id AND ta.teacher_id = :teacher_id AND ta.status = "active")';
            $params['teacher_id'] = $user['id'];
        }
        $sql .= ' ORDER BY c.name, sec.name';
        $query = Database::connection()->prepare($sql);
        $query->execute($params);
        return $query->fetchAll();
    }

    /** @param array<string, mixed> $user */
    private function assertSectionAccess(array $user, int $sectionId): void
    {
        $query = Database::connection()->prepare(
            'SELECT sec.id FROM sections sec
             INNER JOIN classes c ON c.id = sec.class_id
             WHERE sec.id = :section_id AND (:is_super_scope = 1 OR c.school_id = :school_id)
               AND (:branch_unrestricted = 1 OR c.branch_id = :section_branch_id) AND c.status = "active"
               AND (:is_super_role = 1 OR EXISTS (
                    SELECT 1 FROM user_roles ur JOIN roles r ON r.id = ur.role_id
                    WHERE ur.user_id = :role_user_id AND r.name IN ("school_administrator", "super_administrator", "head_teacher")
                      AND (ur.expires_at IS NULL OR ur.expires_at > CURRENT_TIMESTAMP)
               ) OR EXISTS (
                    SELECT 1 FROM teacher_allocations ta
                    WHERE ta.section_id = sec.id AND ta.teacher_id = :allocation_user_id AND ta.status = "active"
               ))'
        );
        $query->execute([
            'section_id' => $sectionId,
            'school_id' => $user['school_id'],
            'branch_unrestricted' => $user['branch_id'] === null ? 1 : 0,
            'section_branch_id' => $user['branch_id'],
            'is_super_scope' => in_array('super_administrator', $user['roles'], true) ? 1 : 0,
            'is_super_role' => in_array('super_administrator', $user['roles'], true) || in_array('head_teacher', $user['roles'], true) ? 1 : 0,
            'role_user_id' => $user['id'],
            'allocation_user_id' => $user['id'],
        ]);
        if (!$query->fetchColumn()) {
            http_response_code(403);
            exit('You cannot manage attendance for this section.');
        }
    }

    private function validatedDate(string $value): string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value || $date > new DateTimeImmutable('today')) {
            http_response_code(422);
            exit('A valid attendance date not later than today is required.');
        }
        return $value;
    }
}
