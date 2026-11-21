<?php $title = 'Fee invoices | CampusOS'; ?>
<div class="mb-6">
    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Finance</p>
    <h1 class="mt-1 text-3xl font-bold">Fee invoices</h1>
</div>
<?php if (!empty($message)): ?><div role="status" class="mb-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><?= e($message) ?></div><?php endif; ?>
<section class="max-w-2xl rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
    <h2 class="text-lg font-semibold">Create invoice</h2>
    <form method="post" action="/fees/invoices" class="mt-5 grid gap-4 sm:grid-cols-2">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <label class="text-sm font-medium">Student
            <select name="student_id" required class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
                <option value="">Select student</option>
                <?php foreach ($students as $student): ?><option value="<?= e($student['id']) ?>"><?= e($student['last_name'] . ', ' . $student['first_name'] . ' · ' . $student['admission_number']) ?></option><?php endforeach; ?>
            </select>
        </label>
        <label class="text-sm font-medium">Fee structure
            <select name="fee_structure_id" required class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
                <option value="">Select fee</option>
                <?php foreach ($feeStructures as $fee): ?><option value="<?= e($fee['id']) ?>"><?= e($fee['name'] . ' — ' . $fee['currency'] . ' ' . $fee['amount']) ?></option><?php endforeach; ?>
            </select>
        </label>
        <label class="text-sm font-medium">Due date
            <input type="date" name="due_date" min="<?= e(date('Y-m-d')) ?>" required class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <div class="flex items-end"><button class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 font-semibold text-white" type="submit">Create invoice</button></div>
    </form>
</section>
<section class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <div class="border-b border-slate-100 px-6 py-4 dark:border-slate-800"><h2 class="font-semibold">Recent invoices</h2></div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-5 py-3">Invoice</th><th>Student</th><th>Due</th><th>Amount</th><th>Status</th><th>Copy</th></tr></thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($invoices as $invoice): ?><tr><td class="px-5 py-3"><?= e($invoice['invoice_number']) ?></td><td><?= e($invoice['first_name'] . ' ' . $invoice['last_name'] . ' · ' . $invoice['admission_number']) ?></td><td><?= e($invoice['due_date']) ?></td><td><?= e($invoice['currency']) ?> <?= e($invoice['total_amount']) ?></td><td class="capitalize"><?= e(str_replace('_', ' ', $invoice['status'])) ?></td><td><a class="font-medium text-indigo-600 hover:underline" href="/fees/invoices/download?id=<?= e($invoice['id']) ?>">PDF</a></td></tr><?php endforeach; ?>
            <?php if (!$invoices): ?><tr><td colspan="6" class="px-5 py-6 text-center text-slate-500">No invoices have been issued.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
