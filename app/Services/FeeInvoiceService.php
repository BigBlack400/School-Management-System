<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use DateTimeImmutable;
use RuntimeException;

final class FeeInvoiceService
{
    public function create(
        int $studentId,
        int $feeStructureId,
        string $dueDate,
        int $createdBy,
        ?int $schoolId,
        ?int $branchId,
        bool $isSuperAdmin
    ): int
    {
        $due = DateTimeImmutable::createFromFormat('!Y-m-d', $dueDate);
        if (!$due || $due->format('Y-m-d') !== $dueDate) {
            throw new RuntimeException('A valid invoice due date is required.');
        }

        $db = Database::connection();
        $ownsTransaction = !$db->inTransaction();
        if ($ownsTransaction) {
            $db->beginTransaction();
        }
        try {
            $query = $db->prepare(
                'SELECT fs.id, fs.name, fs.amount, fs.currency, s.school_id
                 FROM fee_structures fs
                 INNER JOIN students s ON s.school_id = fs.school_id
                 WHERE s.id = :student_id AND fs.id = :fee_structure_id
                   AND fs.status = "active" AND s.status = "active"
                   AND (fs.branch_id IS NULL OR fs.branch_id = s.branch_id)
                   AND (:is_super_admin = 1 OR (
                       s.school_id = :allowed_school_id AND
                       (:branch_unrestricted = 1 OR s.branch_id = :allowed_branch)
                   ))
                 LIMIT 1 FOR UPDATE'
            );
            $query->execute([
                'student_id' => $studentId,
                'fee_structure_id' => $feeStructureId,
                'is_super_admin' => $isSuperAdmin ? 1 : 0,
                'allowed_school_id' => $schoolId,
                'branch_unrestricted' => $branchId === null ? 1 : 0,
                'allowed_branch' => $branchId,
            ]);
            $item = $query->fetch();
            if (!$item) {
                throw new RuntimeException('The student or fee structure is not active or does not belong to the same school.');
            }

            $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(8)));
            $invoice = $db->prepare(
                'INSERT INTO fee_invoices
                    (student_id, invoice_number, issue_date, due_date, total_amount, paid_amount, currency, status, created_by, created_at)
                 VALUES (:student_id, :invoice_number, CURRENT_DATE, :due_date, :total, 0, :currency, "issued", :created_by, CURRENT_TIMESTAMP)'
            );
            $invoice->execute([
                'student_id' => $studentId,
                'invoice_number' => $invoiceNumber,
                'due_date' => $dueDate,
                'total' => $item['amount'],
                'currency' => $item['currency'],
                'created_by' => $createdBy,
            ]);
            $invoiceId = (int) $db->lastInsertId();

            $line = $db->prepare(
                'INSERT INTO fee_invoice_items (invoice_id, fee_structure_id, description, quantity, unit_amount, line_total)
                 VALUES (:invoice_id, :fee_structure_id, :description, 1, :unit_amount, :line_total)'
            );
            $line->execute([
                'invoice_id' => $invoiceId,
                'fee_structure_id' => $feeStructureId,
                'description' => $item['name'],
                'unit_amount' => $item['amount'],
                'line_total' => $item['amount'],
            ]);

            if ($ownsTransaction) {
                $db->commit();
            }
            return $invoiceId;
        } catch (\Throwable $exception) {
            if ($ownsTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    /** @return array<string, mixed>|null */
    public function getForUser(int $invoiceId, array $user): ?array
    {
        $db = Database::connection();
        $sql = 'SELECT i.id, i.invoice_number, i.issue_date, i.due_date, i.total_amount, i.paid_amount,
                       CAST(i.total_amount - i.paid_amount AS DECIMAL(12,2)) AS balance,
                       i.currency, i.status, s.first_name, s.last_name, s.admission_number,
                       sch.name AS school_name
                FROM fee_invoices i
                INNER JOIN students s ON s.id = i.student_id
                INNER JOIN schools sch ON sch.id = s.school_id
                WHERE i.id = :invoice_id AND (
                    :is_super = 1 OR (:is_school_admin = 1 AND s.school_id = :school_id)
                    OR s.user_id = :student_viewer_id OR EXISTS (
                        SELECT 1 FROM guardians g
                        INNER JOIN student_guardians sg ON sg.guardian_id = g.id
                        WHERE g.user_id = :guardian_viewer_id AND sg.student_id = s.id
                    )
                )';
        $query = $db->prepare($sql);
        $isSuper = in_array('super_administrator', $user['roles'], true) ? 1 : 0;
        $isSchoolAdmin = in_array('school_administrator', $user['roles'], true) || in_array('head_teacher', $user['roles'], true) ? 1 : 0;
        $query->execute([
            'invoice_id' => $invoiceId,
            'is_super' => $isSuper,
            'is_school_admin' => $isSchoolAdmin,
            'school_id' => $user['school_id'],
            'student_viewer_id' => $user['id'],
            'guardian_viewer_id' => $user['id'],
        ]);
        $invoice = $query->fetch();
        if (!$invoice) {
            return null;
        }

        $lines = $db->prepare('SELECT description, quantity, unit_amount, line_total FROM fee_invoice_items WHERE invoice_id = :id');
        $lines->execute(['id' => $invoiceId]);
        $invoice['items'] = $lines->fetchAll();
        return $invoice;
    }
}
