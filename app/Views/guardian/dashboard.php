<?php $title = 'Family portal | CampusOS'; ?>
<div class="mb-8">
    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Family portal</p>
    <h1 class="mt-1 text-3xl font-bold">Linked students</h1>
    <p class="mt-2 text-slate-500">Academic and finance summaries for students linked to your verified account.</p>
</div>
<?php if (!$children): ?>
    <section class="rounded-xl border border-slate-200 bg-white p-6 text-slate-500 dark:border-slate-800 dark:bg-slate-900">No active student records are linked to your account. Contact the school office if this is unexpected.</section>
<?php endif; ?>
<div class="space-y-6">
<?php foreach ($children as $child): ?>
    <section class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-5">
            <h2 class="text-xl font-bold"><?= e($child['first_name'] . ' ' . $child['last_name']) ?></h2>
            <p class="mt-1 text-sm text-slate-500"><?= e($child['class_name'] ?? 'Class unassigned') ?><?= !empty($child['section_name']) ? ' · Section ' . e($child['section_name']) : '' ?> · <?= e($child['admission_number']) ?></p>
        </div>
        <div class="grid gap-6 lg:grid-cols-3">
            <div>
                <h3 class="font-semibold">Attendance · 30 days</h3>
                <ul class="mt-3 space-y-2 text-sm"><?php foreach ($child['attendance'] as $entry): ?><li class="flex justify-between"><span class="capitalize text-slate-500"><?= e($entry['status']) ?></span><span class="font-medium"><?= e($entry['total']) ?></span></li><?php endforeach; ?>
                    <?php if (!$child['attendance']): ?><li class="text-slate-500">No recent records.</li><?php endif; ?>
                </ul>
            </div>
            <div>
                <h3 class="font-semibold">Recent grades</h3>
                <ul class="mt-3 space-y-2 text-sm"><?php foreach ($child['grades'] as $grade): ?><li class="flex justify-between gap-2"><span><?= e($grade['subject_name']) ?> · <?= e($grade['exam_name']) ?></span><span class="shrink-0 font-medium"><?= e($grade['marks_obtained']) ?>/<?= e($grade['max_marks']) ?> <?= e($grade['letter_grade'] ?? '') ?></span></li><?php endforeach; ?>
                    <?php if (!$child['grades']): ?><li class="text-slate-500">No grades are available yet.</li><?php endif; ?>
                </ul>
            </div>
            <div>
                <h3 class="font-semibold">Outstanding fees</h3>
                <ul class="mt-3 space-y-2 text-sm"><?php foreach ($child['invoices'] as $invoice): ?><li class="flex justify-between gap-2"><span><a class="text-indigo-600 hover:underline" href="/fees/invoices/download?id=<?= e($invoice['id']) ?>"><?= e($invoice['invoice_number']) ?></a> · <?= e($invoice['due_date']) ?></span><span class="shrink-0 font-medium"><?= e($invoice['currency']) ?> <?= e($invoice['balance']) ?></span></li><?php endforeach; ?>
                    <?php if (!$child['invoices']): ?><li class="text-slate-500">No outstanding invoices.</li><?php endif; ?>
                </ul>
            </div>
        </div>
    </section>
<?php endforeach; ?>
</div>
