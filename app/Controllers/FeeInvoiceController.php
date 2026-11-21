<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;
use App\Services\FeeInvoiceService;
use Dompdf\Dompdf;

final class FeeInvoiceController
{
    public function index(): void
    {
        $user = Auth::requirePermission('fees.manage');
        $isSuperAdmin = in_array('super_administrator', $user['roles'], true);
        $query = Database::connection()->prepare(
            'SELECT id, name, amount, currency FROM fee_structures
             WHERE (:is_super_admin = 1 OR school_id = :school_id)
               AND (:branch_id IS NULL OR branch_id IS NULL OR branch_id = :fee_branch_id)
               AND status = "active" ORDER BY name'
        );
        $query->execute([
            'is_super_admin' => $isSuperAdmin ? 1 : 0,
            'school_id' => $user['school_id'],
            'branch_id' => $user['branch_id'],
            'fee_branch_id' => $user['branch_id'],
        ]);
        $studentsQuery = Database::connection()->prepare(
            'SELECT id, admission_number, first_name, last_name FROM students
             WHERE (:is_super = 1 OR school_id = :student_school_id)
               AND (:branch_unrestricted = 1 OR branch_id = :student_branch_id)
               AND status = "active" ORDER BY last_name, first_name'
        );
        $studentsQuery->execute([
            'is_super' => $isSuperAdmin ? 1 : 0,
            'student_school_id' => $user['school_id'],
            'branch_unrestricted' => $user['branch_id'] === null ? 1 : 0,
            'student_branch_id' => $user['branch_id'],
        ]);
        $invoicesQuery = Database::connection()->prepare(
            'SELECT i.id, i.invoice_number, i.due_date, i.total_amount, i.currency, i.status,
                    s.first_name, s.last_name, s.admission_number
             FROM fee_invoices i INNER JOIN students s ON s.id = i.student_id
             WHERE (:is_super_invoice = 1 OR s.school_id = :invoice_school_id)
               AND (:branch_unrestricted_invoice = 1 OR s.branch_id = :invoice_branch_id)
             ORDER BY i.created_at DESC LIMIT 30'
        );
        $invoicesQuery->execute([
            'is_super_invoice' => $isSuperAdmin ? 1 : 0,
            'invoice_school_id' => $user['school_id'],
            'branch_unrestricted_invoice' => $user['branch_id'] === null ? 1 : 0,
            'invoice_branch_id' => $user['branch_id'],
        ]);
        View::render('fees/index', [
            'user' => $user,
            'csrf' => Csrf::token(),
            'feeStructures' => $query->fetchAll(),
            'students' => $studentsQuery->fetchAll(),
            'invoices' => $invoicesQuery->fetchAll(),
            'message' => $_SESSION['flash_success'] ?? null,
        ]);
        unset($_SESSION['flash_success']);
    }

    public function create(): void
    {
        $user = Auth::requirePermission('fees.manage');
        Csrf::validateRequest();
        $studentId = filter_var($_POST['student_id'] ?? null, FILTER_VALIDATE_INT);
        $feeStructureId = filter_var($_POST['fee_structure_id'] ?? null, FILTER_VALIDATE_INT);
        $dueDate = (string) ($_POST['due_date'] ?? '');
        if (!$studentId || !$feeStructureId) {
            http_response_code(422);
            exit('A valid student and fee structure are required.');
        }

        $invoiceId = (new FeeInvoiceService())->create(
            (int) $studentId,
            (int) $feeStructureId,
            $dueDate,
            (int) $user['id'],
            $user['school_id'] === null ? null : (int) $user['school_id'],
            $user['branch_id'] === null ? null : (int) $user['branch_id'],
            in_array('super_administrator', $user['roles'], true)
        );
        AuditLogger::record((int) $user['id'], 'fees.invoice_created', 'fee_invoice', $invoiceId);
        $_SESSION['flash_success'] = 'Invoice created successfully.';
        header('Location: /fees/invoices', true, 303);
        exit;
    }

    public function download(): void
    {
        $user = Auth::requireRole(['super_administrator', 'school_administrator', 'head_teacher', 'student', 'parent_guardian']);
        $invoiceId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$invoiceId) {
            http_response_code(404);
            exit('Invoice not found.');
        }
        $invoice = (new FeeInvoiceService())->getForUser((int) $invoiceId, $user);
        if (!$invoice) {
            http_response_code(404);
            exit('Invoice not found.');
        }

        ob_start();
        View::render('fees/invoice-pdf', ['invoice' => $invoice], false);
        $html = (string) ob_get_clean();
        $pdf = new Dompdf(['isRemoteEnabled' => false]);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();
        $pdf->stream($invoice['invoice_number'] . '.pdf', ['Attachment' => true]);
    }
}
