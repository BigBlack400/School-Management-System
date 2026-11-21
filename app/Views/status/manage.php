<?php $title = 'Status management | CampusOS'; ?>
<div class="mb-6">
    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Discipline and staffing</p>
    <h1 class="mt-1 text-3xl font-bold">Suspend or dismiss</h1>
    <p class="mt-2 text-slate-500">Status changes are audited. Dismissed/suspended staff lose active role access.</p>
</div>
<?php if (!empty($message)): ?><div role="status" class="mb-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><?= e($message) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div role="alert" class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>

<div class="grid gap-6 lg:grid-cols-2">
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800"><h2 class="font-semibold">Students</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-5 py-3">Student</th><th>Status</th><th>Change</th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <?php foreach ($students as $student): ?>
                <tr><td class="px-5 py-3 font-medium"><?= e($student['first_name'] . ' ' . $student['last_name']) ?><div class="text-xs font-normal text-slate-500"><?= e($student['admission_number']) ?></div></td><td class="capitalize"><?= e($student['status']) ?></td>
                <td class="py-2"><form method="post" action="/status/manage" class="flex min-w-72 gap-2">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="type" value="student"><input type="hidden" name="record_id" value="<?= e($student['id']) ?>">
                    <select name="action" class="rounded-md border border-slate-300 bg-transparent px-2 py-1 dark:border-slate-700"><option value="active">Restore active</option><option value="suspended" <?= $student['status'] === 'suspended' ? 'selected' : '' ?>>Suspend</option><option value="expelled" <?= $student['status'] === 'expelled' ? 'selected' : '' ?>>Expel</option><option value="withdrawn" <?= $student['status'] === 'withdrawn' ? 'selected' : '' ?>>Withdraw</option></select>
                    <input name="reason" required minlength="3" maxlength="1000" placeholder="Reason" class="w-32 rounded-md border border-slate-300 bg-transparent px-2 py-1 dark:border-slate-700">
                    <button type="submit" class="rounded-md border border-indigo-300 px-2 py-1 text-indigo-700 dark:border-indigo-700 dark:text-indigo-300">Apply</button>
                </form></td></tr>
                <?php endforeach; ?>
                <?php if (!$students): ?><tr><td colspan="3" class="px-5 py-8 text-center text-slate-500">No students found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800"><h2 class="font-semibold">Teachers and staff</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-5 py-3">Staff</th><th>Status</th><th>Change</th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <?php foreach ($staff as $person): ?>
                <tr><td class="px-5 py-3 font-medium"><?= e($person['first_name'] . ' ' . $person['last_name']) ?><div class="text-xs font-normal text-slate-500"><?= e($person['roles']) ?></div></td><td class="capitalize"><?= e($person['status']) ?></td>
                <td class="py-2"><form method="post" action="/status/manage" class="flex min-w-72 gap-2">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="type" value="staff"><input type="hidden" name="record_id" value="<?= e($person['id']) ?>">
                    <select name="action" class="rounded-md border border-slate-300 bg-transparent px-2 py-1 dark:border-slate-700"><option value="active">Restore active</option><option value="suspended" <?= $person['status'] === 'suspended' ? 'selected' : '' ?>>Suspend</option><option value="dismissed" <?= $person['status'] === 'dismissed' ? 'selected' : '' ?>>Dismiss</option><option value="inactive" <?= $person['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option></select>
                    <input name="reason" required minlength="3" maxlength="1000" placeholder="Reason" class="w-32 rounded-md border border-slate-300 bg-transparent px-2 py-1 dark:border-slate-700">
                    <button type="submit" class="rounded-md border border-red-300 px-2 py-1 text-red-700 dark:border-red-700 dark:text-red-300">Apply</button>
                </form></td></tr>
                <?php endforeach; ?>
                <?php if (!$staff): ?><tr><td colspan="3" class="px-5 py-8 text-center text-slate-500">No other authorized staff accounts found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
