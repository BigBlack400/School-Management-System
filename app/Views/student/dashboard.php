<?php $title = 'Student dashboard | CampusOS'; ?>
<div class="mb-8">
    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Student portal</p>
    <h1 class="mt-1 text-3xl font-bold"><?= e($student['first_name'] . ' ' . $student['last_name']) ?></h1>
    <p class="mt-2 text-slate-500"><?= e($student['class_name'] ?? 'Class unassigned') ?><?= !empty($student['section_name']) ? ' · Section ' . e($student['section_name']) : '' ?> · Admission <?= e($student['admission_number']) ?></p>
</div>
<div class="grid gap-6 lg:grid-cols-3">
    <section class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="font-semibold">Attendance · last 30 days</h2>
        <dl class="mt-4 space-y-3">
            <?php foreach ($attendanceSummary as $row): ?>
                <div class="flex justify-between text-sm"><dt class="capitalize text-slate-500"><?= e($row['status']) ?></dt><dd class="font-semibold"><?= e($row['total']) ?></dd></div>
            <?php endforeach; ?>
            <?php if (!$attendanceSummary): ?><p class="text-sm text-slate-500">No recent attendance recorded.</p><?php endif; ?>
        </dl>
    </section>
    <section class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 lg:col-span-2">
        <h2 class="font-semibold">Recent grades</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm"><thead class="text-xs uppercase text-slate-500"><tr><th class="py-2">Exam</th><th>Subject</th><th>Marks</th><th>Grade</th></tr></thead><tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($grades as $grade): ?><tr><td class="py-3"><?= e($grade['exam_name']) ?></td><td><?= e($grade['subject_name']) ?></td><td><?= e($grade['marks_obtained']) ?> / <?= e($grade['max_marks']) ?></td><td><?= e($grade['letter_grade'] ?? '—') ?></td></tr><?php endforeach; ?>
            <?php if (!$grades): ?><tr><td class="py-3 text-slate-500" colspan="4">No grades are available yet.</td></tr><?php endif; ?>
            </tbody></table>
        </div>
    </section>
    <section class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 lg:col-span-3">
        <h2 class="font-semibold">Outstanding fees</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm"><thead class="text-xs uppercase text-slate-500"><tr><th class="py-2">Invoice</th><th>Due</th><th>Balance</th><th>Status</th></tr></thead><tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($invoices as $invoice): ?><tr><td class="py-3"><a class="text-indigo-600 hover:underline" href="/fees/invoices/download?id=<?= e($invoice['id']) ?>"><?= e($invoice['invoice_number']) ?></a></td><td><?= e($invoice['due_date']) ?></td><td><?= e($invoice['balance']) ?></td><td class="capitalize"><?= e(str_replace('_', ' ', $invoice['status'])) ?></td></tr><?php endforeach; ?>
            <?php if (!$invoices): ?><tr><td class="py-3 text-slate-500" colspan="4">No outstanding invoices.</td></tr><?php endif; ?>
            </tbody></table>
        </div>
    </section>
</div>
