<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;
use App\Services\FeeInvoiceService;
use DateTimeImmutable;

final class ClassFeeController
{
    public function index(): void
    {
        $user = Auth::requirePermission('fees.manage');
        $db = Database::connection();
        $isSuper = in_array('super_administrator', $user['roles'], true);

        $classes = $db->prepare(
            'SELECT c.id, c.name, c.grade_level, ay.name AS academic_year
             FROM classes c
             INNER JOIN academic_years ay ON ay.id = c.academic_year_id
             WHERE c.status = "active"
               AND (:is_super = 1 OR c.school_id = :school_id)
             ORDER BY c.grade_level, c.name'
        );
        $classes->execute([
            'is_super' => $isSuper ? 1 : 0,
            'school_id' => $user['school_id'],
        ]);

        $fees = $db->prepare(
            'SELECT id, name, amount, currency, frequency
             FROM fee_structures
             WHERE status = "active"
               AND (:is_super = 1 OR school_id = :school_id)
             ORDER BY name'
        );
        $fees->execute([
            'is_super' => $isSuper ? 1 : 0,
            'school_id' => $user['school_id'],
        ]);

        $assignments = $db->prepare(
            'SELECT fca.id, fca.due_date, fca.is_mandatory,
                    fs.id AS fee_structure_id, fs.name AS fee_name, fs.amount, fs.currency,
                    c.id AS class_id, c.name AS class_name, c.grade_level
             FROM fee_class_assignments fca
             INNER JOIN fee_structures fs ON fs.id = fca.fee_structure_id
             INNER JOIN classes c ON c.id = fca.class_id
             WHERE (:is_super = 1 OR c.school_id = :school_id)
             ORDER BY c.grade_level, c.name, fs.name'
        );
        $assignments->execute([
            'is_super' => $isSuper ? 1 : 0,
            'school_id' => $user['school_id'],
        ]);

        View::render('fees/class-fees', [
            'user' => $user,
            'csrf' => Csrf::token(),
            'classes' => $classes->fetchAll(),
            'fees' => $fees->fetchAll(),
            'assignments' => $assignments->fetchAll(),
            'message' => $_SESSION['flash_success'] ?? null,
            'error' => $_SESSION['flash_error'] ?? null,
        ]);
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function store(): void
    {
        $user = Auth::requirePermission('fees.manage');
        Csrf::validateRequest();

        $action = (string) ($_POST['action'] ?? '');
        $feeStructureId = filter_var($_POST['fee_structure_id'] ?? null, FILTER_VALIDATE_INT);
        $classIds = $_POST['class_ids'] ?? [];
        $dueDate = trim((string) ($_POST['due_date'] ?? ''));
        $newFeeName = trim((string) ($_POST['fee_name'] ?? ''));
        $newFeeAmount = trim((string) ($_POST['fee_amount'] ?? ''));
        $frequency = (string) ($_POST['frequency'] ?? 'one_time');
        $mandatory = (($_POST['is_mandatory'] ?? '') === '1');

        if (!is_array($classIds) || count($classIds) === 0 || count($classIds) > 100) {
            $this->fail('Select at least one class or grade level.');
        }
        if (!in_array($frequency, ['one_time', 'monthly', 'termly', 'annual'], true)) {
            $this->fail('Invalid fee frequency.');
        }
        if ($dueDate !== '' && !$this->validDate($dueDate)) {
            $this->fail('Enter a valid due date.');
        }

        $db = Database::connection();
        $isSuper = in_array('super_administrator', $user['roles'], true);
        $db->beginTransaction();
        try {
            if ($action === 'create') {
                if ($newFeeName === '' || mb_strlen($newFeeName) > 160 ||
                    !preg_match('/\A(?:\d+(?:\.\d{1,2})?|\d*\.\d{1,2})\z/', $newFeeAmount) ||
                    (float) $newFeeAmount < 0) {
                    throw new \RuntimeException('Enter a valid fee name and amount.');
                }
                $school = $db->prepare('SELECT id, currency FROM schools WHERE id = :id LIMIT 1');
                $school->execute(['id' => $user['school_id']]);
                $schoolRow = $school->fetch();
                if (!$isSuper && !$schoolRow) {
                    throw new \RuntimeException('School scope is not configured.');
                }
                if ($isSuper) {
                    $schoolRow = ['id' => $user['school_id'] ?: 1, 'currency' => 'GHS'];
                }

                $academicYear = $db->prepare(
                    'SELECT id FROM academic_years
                     WHERE school_id = :school_id
                     ORDER BY is_current DESC, starts_on DESC
                     LIMIT 1'
                );
                $academicYear->execute(['school_id' => $schoolRow['id']]);
                $academicYearId = $academicYear->fetchColumn();

                $insertFee = $db->prepare(
                    'INSERT INTO fee_structures
                        (school_id, branch_id, academic_year_id, name, amount, currency, frequency, status, created_at)
                     VALUES (:school_id, :branch_id, :academic_year_id, :name, :amount, :currency, :frequency, "active", CURRENT_TIMESTAMP)'
                );
                $insertFee->execute([
                    'school_id' => $schoolRow['id'],
                    'branch_id' => $user['branch_id'],
                    'academic_year_id' => $academicYearId ?: null,
                    'name' => $newFeeName,
                    'amount' => number_format((float) $newFeeAmount, 2, '.', ''),
                    'currency' => $schoolRow['currency'] ?? 'GHS',
                    'frequency' => $frequency,
                ]);
                $feeStructureId = (int) $db->lastInsertId();
            } elseif ($action === 'assign' || $action === 'generate') {
                if (!$feeStructureId) {
                    throw new \RuntimeException('Select a fee structure.');
                }
            } else {
                throw new \RuntimeException('Invalid class fee action.');
            }

            $assignedCount = 0;
            $generatedCount = 0;
            foreach ($classIds as $classIdValue) {
                $classId = filter_var($classIdValue, FILTER_VALIDATE_INT);
                if (!$classId) {
                    throw new \RuntimeException('A selected class is invalid.');
                }
                $classQuery = $db->prepare(
                    'SELECT c.id, c.school_id, c.grade_level
                     FROM classes c
                     WHERE c.id = :id AND c.status = "active"
                       AND (:is_super = 1 OR c.school_id = :school_id)
                     LIMIT 1'
                );
                $classQuery->execute([
                    'id' => (int) $classId,
                    'is_super' => $isSuper ? 1 : 0,
                    'school_id' => $user['school_id'],
                ]);
                $classRow = $classQuery->fetch();
                if (!$classRow) {
                    throw new \RuntimeException('One selected class is outside your school scope.');
                }

                $assignment = $db->prepare(
                    'INSERT INTO fee_class_assignments
                        (fee_structure_id, class_id, due_date, is_mandatory, assigned_by, created_at)
                     VALUES (:fee_structure_id, :class_id, :due_date, :mandatory, :assigned_by, CURRENT_TIMESTAMP)
                     ON DUPLICATE KEY UPDATE due_date = VALUES(due_date),
                         is_mandatory = VALUES(is_mandatory),
                         assigned_by = VALUES(assigned_by)'
                );
                $assignment->execute([
                    'fee_structure_id' => (int) $feeStructureId,
                    'class_id' => (int) $classId,
                    'due_date' => $dueDate !== '' ? $dueDate : null,
                    'mandatory' => $mandatory ? 1 : 0,
                    'assigned_by' => $user['id'],
                ]);
                $assignedCount++;

                if ($action === 'generate') {
                    $effectiveDueDate = $dueDate !== '' ? $dueDate : date('Y-m-d', strtotime('+30 days'));
                    $students = $db->prepare(
                        'SELECT s.id
                         FROM students s
                         INNER JOIN sections sec ON sec.id = s.section_id
                         WHERE s.status = "active" AND sec.class_id = :class_id
                           AND (:is_super = 1 OR s.school_id = :school_id)'
                    );
                    $students->execute([
                        'class_id' => (int) $classId,
                        'is_super' => $isSuper ? 1 : 0,
                        'school_id' => $user['school_id'],
                    ]);
                    foreach ($students->fetchAll() as $student) {
                        $existing = $db->prepare(
                            'SELECT 1
                             FROM fee_invoices i
                             INNER JOIN fee_invoice_items fii ON fii.invoice_id = i.id
                             WHERE i.student_id = :student_id
                               AND fii.fee_structure_id = :fee_structure_id
                               AND i.status <> "void"
                             LIMIT 1'
                        );
                        $existing->execute([
                            'student_id' => (int) $student['id'],
                            'fee_structure_id' => (int) $feeStructureId,
                        ]);
                        if ($existing->fetchColumn()) {
                            continue;
                        }

                        $invoiceId = (new FeeInvoiceService())->create(
                            (int) $student['id'],
                            (int) $feeStructureId,
                            $effectiveDueDate,
                            (int) $user['id'],
                            $user['school_id'] === null ? null : (int) $user['school_id'],
                            $user['branch_id'] === null ? null : (int) $user['branch_id'],
                            $isSuper
                        );
                        $generatedCount++;
                    }
                }
            }
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->fail($exception instanceof \RuntimeException
                ? $exception->getMessage()
                : 'The class fee operation could not be completed.');
        }

        AuditLogger::record((int) $user['id'], 'fees.class_assignment', 'fee_structure', (int) $feeStructureId, [
            'action' => $action,
            'class_count' => $assignedCount,
            'invoice_count' => $generatedCount,
        ]);
        $_SESSION['flash_success'] = $action === 'generate'
            ? "Fee assigned to $assignedCount class(es); $generatedCount invoice(s) generated."
            : "Fee assigned to $assignedCount class(es).";
        header('Location: /fees/class-fees', true, 303);
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
        header('Location: /fees/class-fees', true, 303);
        exit;
    }
}
