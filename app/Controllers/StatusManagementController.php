<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;

final class StatusManagementController
{
    public function index(): void
    {
        $user = Auth::requireRole(['school_administrator', 'super_administrator', 'head_teacher']);
        $db = Database::connection();
        $isSuper = in_array('super_administrator', $user['roles'], true);

        $students = $db->prepare(
            'SELECT s.id, s.admission_number, s.first_name, s.last_name, s.status,
                    c.name AS class_name, sec.name AS section_name
             FROM students s
             LEFT JOIN sections sec ON sec.id = s.section_id
             LEFT JOIN classes c ON c.id = sec.class_id
             WHERE ' . ($isSuper ? '1 = 1' : 's.school_id = :school_id') . '
             ORDER BY s.status, s.last_name, s.first_name
             LIMIT 500'
        );
        $studentParams = $isSuper ? [] : ['school_id' => $user['school_id']];
        $students->execute($studentParams);

        $staff = $db->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.status,
                    GROUP_CONCAT(DISTINCT r.label ORDER BY r.label SEPARATOR ", ") AS roles
             FROM users u
             INNER JOIN user_roles ur ON ur.user_id = u.id
             INNER JOIN roles r ON r.id = ur.role_id
             WHERE r.name IN ("teacher", "school_administrator")
               AND ' . ($isSuper ? '1 = 1' : 'u.school_id = :school_id') . '
               AND u.id <> :actor_id
             GROUP BY u.id, u.first_name, u.last_name, u.email, u.status
             ORDER BY u.status, u.last_name, u.first_name
             LIMIT 500'
        );
        $staffParams = $isSuper
            ? ['actor_id' => $user['id']]
            : ['actor_id' => $user['id'], 'school_id' => $user['school_id']];
        $staff->execute($staffParams);

        View::render('status/manage', [
            'user' => $user,
            'csrf' => Csrf::token(),
            'students' => $students->fetchAll(),
            'staff' => $staff->fetchAll(),
            'message' => $_SESSION['flash_success'] ?? null,
            'error' => $_SESSION['flash_error'] ?? null,
        ]);
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function update(): void
    {
        $user = Auth::requireRole(['school_administrator', 'super_administrator', 'head_teacher']);
        Csrf::validateRequest();

        $type = (string) ($_POST['type'] ?? '');
        $recordId = filter_var($_POST['record_id'] ?? null, FILTER_VALIDATE_INT);
        $action = (string) ($_POST['action'] ?? '');
        $reason = trim((string) ($_POST['reason'] ?? ''));

        if (!$recordId || mb_strlen($reason) < 3 || mb_strlen($reason) > 1000) {
            $_SESSION['flash_error'] = 'Select a valid record and provide a reason of 3 to 1000 characters.';
            header('Location: /status/manage', true, 303);
            exit;
        }

        $isSuper = in_array('super_administrator', $user['roles'], true);
        $db = Database::connection();
        $db->beginTransaction();
        try {
            if ($type === 'student') {
                $allowed = ['active', 'suspended', 'expelled', 'withdrawn'];
                if (!in_array($action, $allowed, true)) {
                    throw new \RuntimeException('Invalid student status.');
                }
                $query = $db->prepare(
                    'SELECT s.id, s.status, s.section_id, s.user_id
                     FROM students s
                     WHERE s.id = :id AND ' . ($isSuper ? '1 = 1' : 's.school_id = :school_id') . ' LIMIT 1'
                );
                $params = ['id' => (int) $recordId];
                if (!$isSuper) {
                    $params['school_id'] = $user['school_id'];
                }
                $query->execute($params);
                $student = $query->fetch();
                if (!$student) {
                    throw new \RuntimeException('The student is outside your authorized school scope.');
                }
                if ($action === 'active' && $student['status'] === 'expelled') {
                    throw new \RuntimeException('An expelled student must be handled through a formal re-admission workflow.');
                }

                $update = $db->prepare(
                    'UPDATE students SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
                );
                $update->execute(['status' => $action, 'id' => (int) $recordId]);

                if ($student['user_id'] ?? null) {
                    $studentAccountStatus = $action === 'active' ? 'active' : 'inactive';
                    $accountUpdate = $db->prepare(
                        'UPDATE users u
                         JOIN students s ON s.user_id = u.id
                         SET u.status = :status, u.auth_version = u.auth_version + 1
                         WHERE s.id = :student_id'
                    );
                    $accountUpdate->execute(['status' => $studentAccountStatus, 'student_id' => (int) $recordId]);
                }

                $history = $db->prepare(
                    'INSERT INTO student_history
                        (student_id, from_section_id, to_section_id, event_type, effective_date, notes, recorded_by, created_at)
                     VALUES (:student_id, :section_id, :section_id, "status_changed", CURRENT_DATE,
                             :notes, :recorded_by, CURRENT_TIMESTAMP)'
                );
                $history->execute([
                    'student_id' => (int) $recordId,
                    'section_id' => $student['section_id'],
                    'notes' => 'Status changed from ' . $student['status'] . ' to ' . $action . ': ' . $reason,
                    'recorded_by' => $user['id'],
                ]);

                AuditLogger::record((int) $user['id'], 'students.status_changed', 'student', (int) $recordId, [
                    'from_status' => $student['status'],
                    'to_status' => $action,
                    'reason' => $reason,
                ]);
            } elseif ($type === 'staff') {
                $allowed = ['active', 'suspended', 'dismissed', 'inactive'];
                if (!in_array($action, $allowed, true)) {
                    throw new \RuntimeException('Invalid staff status.');
                }
                $query = $db->prepare(
                    'SELECT u.id, u.status
                     FROM users u
                     INNER JOIN user_roles ur ON ur.user_id = u.id
                     INNER JOIN roles r ON r.id = ur.role_id
                     WHERE u.id = :id
                       AND r.name IN ("teacher", "school_administrator")
                       AND u.id <> :actor_id
                       AND ' . ($isSuper ? '1 = 1' : 'u.school_id = :school_id') . '
                     LIMIT 1'
                );
                $params = ['id' => (int) $recordId, 'actor_id' => $user['id']];
                if (!$isSuper) {
                    $params['school_id'] = $user['school_id'];
                }
                $query->execute($params);
                $staff = $query->fetch();
                if (!$staff) {
                    throw new \RuntimeException('The staff account is outside your authorized scope.');
                }

                $update = $db->prepare(
                    'UPDATE users SET status = :status, auth_version = auth_version + 1,
                            updated_at = CURRENT_TIMESTAMP WHERE id = :id'
                );
                $update->execute(['status' => $action, 'id' => (int) $recordId]);

                if ($action === 'active') {
                    $db->prepare(
                        'UPDATE user_roles SET expires_at = NULL WHERE user_id = :id'
                    )->execute(['id' => (int) $recordId]);
                } elseif (in_array($action, ['dismissed', 'inactive', 'suspended'], true)) {
                    $db->prepare(
                        'UPDATE user_roles SET expires_at = COALESCE(expires_at, CURRENT_TIMESTAMP)
                         WHERE user_id = :id'
                    )->execute(['id' => (int) $recordId]);
                    $db->prepare(
                        'UPDATE teacher_allocations SET status = "inactive"
                         WHERE teacher_id = :id'
                    )->execute(['id' => (int) $recordId]);
                }

                AuditLogger::record((int) $user['id'], 'staff.status_changed', 'user', (int) $recordId, [
                    'from_status' => $staff['status'],
                    'to_status' => $action,
                    'reason' => $reason,
                ]);
            } else {
                throw new \RuntimeException('Invalid status-management record type.');
            }
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['flash_error'] = $exception instanceof \RuntimeException
                ? $exception->getMessage()
                : 'The status change could not be completed.';
            header('Location: /status/manage', true, 303);
            exit;
        }

        $_SESSION['flash_success'] = 'Status updated and recorded in the audit history.';
        header('Location: /status/manage', true, 303);
        exit;
    }
}
