<?php $title = 'Class fees | CampusOS'; ?>
<div class="mb-6">
    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Fee assignments</p>
    <h1 class="mt-1 text-3xl font-bold">Fees by class level</h1>
    <p class="mt-2 text-slate-500">Assign fees to Grade 1, Grade 2, or another class level and optionally generate invoices.</p>
</div>
<?php if (!empty($message)): ?><div role="status" class="mb-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><?= e($message) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div role="alert" class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>

<section class="mb-8 rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
    <h2 class="font-semibold">Assign or create a class fee</h2>
    <form method="post" action="/fees/class-fees" class="mt-5 grid gap-5 sm:grid-cols-2" x-data="{ mode: 'assign' }">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" :value="mode">
        <label class="text-sm font-medium sm:col-span-2">Operation
            <select x-model="mode" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
                <option value="assign">Assign an existing fee to selected classes</option>
                <option value="create">Create a new fee and assign it</option>
                <option value="generate">Assign and generate invoices for active students</option>
            </select>
        </label>
        <div x-show="mode !== 'create'" x-cloak>
            <label class="text-sm font-medium">Existing fee
                <select name="fee_structure_id" :required="mode !== 'create'" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
                    <option value="">Select fee</option>
                    <?php foreach ($fees as $fee): ?><option value="<?= e($fee['id']) ?>"><?= e($fee['name'] . ' — ' . $fee['currency'] . ' ' . $fee['amount']) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <div x-show="mode === 'create'" x-cloak class="grid gap-4 sm:col-span-2 sm:grid-cols-3">
            <label class="text-sm font-medium">Fee name
                <input name="fee_name" maxlength="160" :required="mode === 'create'" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
            </label>
            <label class="text-sm font-medium">Amount
                <input name="fee_amount" inputmode="decimal" :required="mode === 'create'" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
            </label>
            <label class="text-sm font-medium">Frequency
                <select name="frequency" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
                    <option value="one_time">One time</option>
                    <option value="monthly">Monthly</option>
                    <option value="termly">Termly</option>
                    <option value="annual">Annual</option>
                </select>
            </label>
        </div>
        <label class="text-sm font-medium">Due date
            <input type="date" name="due_date" min="<?= e(date('Y-m-d')) ?>" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <label class="flex items-center gap-2 text-sm font-medium sm:mt-6"><input type="checkbox" name="is_mandatory" value="1" checked class="rounded border-slate-300"> Mandatory fee</label>
        <fieldset class="sm:col-span-2">
            <legend class="text-sm font-medium">Classes / levels</legend>
            <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($classes as $class): ?>
                    <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700">
                        <input type="checkbox" name="class_ids[]" value="<?= e($class['id']) ?>">
                        <?= e($class['name']) ?><?= $class['grade_level'] !== null ? ' · Level ' . e($class['grade_level']) : '' ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <div class="sm:col-span-2"><button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white">Apply fee to selected classes</button></div>
    </form>
</section>

<section class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800"><h2 class="font-semibold">Current class fee assignments</h2></div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-5 py-3">Class / level</th><th>Fee</th><th>Amount</th><th>Due date</th><th>Mandatory</th></tr></thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($assignments as $assignment): ?><tr><td class="px-5 py-3"><?= e($assignment['class_name']) ?><?= $assignment['grade_level'] !== null ? ' · Level ' . e($assignment['grade_level']) : '' ?></td><td><?= e($assignment['fee_name']) ?></td><td><?= e($assignment['currency']) ?> <?= e($assignment['amount']) ?></td><td><?= e($assignment['due_date'] ?? 'Set on generation') ?></td><td><?= $assignment['is_mandatory'] ? 'Yes' : 'Optional' ?></td></tr><?php endforeach; ?>
            <?php if (!$assignments): ?><tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">No fees are assigned to classes yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
