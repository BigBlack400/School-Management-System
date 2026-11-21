<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><style>
    body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 12px; }
    .header { border-bottom: 2px solid #4338ca; padding-bottom: 16px; }
    h1 { color: #312e81; font-size: 24px; }
    table { border-collapse: collapse; margin-top: 24px; width: 100%; }
    th, td { border-bottom: 1px solid #dbe1ea; padding: 10px 8px; text-align: left; }
    .total { text-align: right; margin-top: 18px; font-size: 16px; font-weight: bold; }
</style></head>
<body>
    <div class="header"><h1><?= e($invoice['school_name']) ?></h1><p>Fee invoice</p></div>
    <p><strong>Invoice:</strong> <?= e($invoice['invoice_number']) ?> &nbsp; <strong>Issued:</strong> <?= e($invoice['issue_date']) ?> &nbsp; <strong>Due:</strong> <?= e($invoice['due_date']) ?></p>
    <p><strong>Student:</strong> <?= e($invoice['first_name'] . ' ' . $invoice['last_name']) ?> (<?= e($invoice['admission_number']) ?>)</p>
    <table><thead><tr><th>Description</th><th>Qty</th><th>Unit amount</th><th>Line total</th></tr></thead><tbody>
    <?php foreach ($invoice['items'] as $item): ?><tr><td><?= e($item['description']) ?></td><td><?= e($item['quantity']) ?></td><td><?= e($invoice['currency']) ?> <?= e($item['unit_amount']) ?></td><td><?= e($invoice['currency']) ?> <?= e($item['line_total']) ?></td></tr><?php endforeach; ?>
    </tbody></table>
    <p class="total">Total: <?= e($invoice['currency']) ?> <?= e($invoice['total_amount']) ?><br>Paid: <?= e($invoice['currency']) ?> <?= e($invoice['paid_amount']) ?><br>Balance: <?= e($invoice['currency']) ?> <?= e($invoice['balance']) ?></p>
</body></html>
