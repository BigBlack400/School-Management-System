<?php $title = 'Attendance | CampusOS'; ?>
<div class="mb-6">
    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Academics</p>
    <h1 class="mt-1 text-3xl font-bold">Daily attendance</h1>
</div>
<form method="get" action="/attendance" class="mb-6 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
    <label class="text-sm font-medium">Class / section
        <select name="section_id" required class="mt-1 block min-w-56 rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
            <option value="">Select a section</option>
            <?php foreach ($sections as $section): ?><option value="<?= e($section['id']) ?>" <?= (int) $selectedSection === (int) $section['id'] ? 'selected' : '' ?>><?= e($section['class_name'] . ' · ' . $section['name']) ?></option><?php endforeach; ?>
        </select>
    </label>
    <label class="text-sm font-medium">Date
        <input type="date" name="date" value="<?= e($date) ?>" max="<?= e(date('Y-m-d')) ?>" required class="mt-1 block rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
    </label>
    <button class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white" type="submit">Load roster</button>
</form>
<?php if ($selectedSection): ?>
<form method="post" action="/attendance" class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="section_id" value="<?= e($selectedSection) ?>">
    <input type="hidden" name="attendance_date" value="<?= e($date) ?>">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-5 py-3">Student</th><th>Admission no.</th><th>Status</th><th>Remarks</th></tr></thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($students as $student): ?>
                <tr>
                    <td class="px-5 py-3 font-medium"><?= e($student['first_name'] . ' ' . $student['last_name']) ?></td>
                    <td><?= e($student['admission_number']) ?></td>
                    <td><select name="attendance[<?= e($student['id']) ?>][status]" class="rounded-md border border-slate-300 bg-transparent px-2 py-1 dark:border-slate-700">
                        <?php foreach (['present', 'absent', 'late', 'excused'] as $status): ?><option value="<?= e($status) ?>" <?= ($student['status'] ?? 'present') === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option><?php endforeach; ?>
                    </select></td>
                    <td><input name="attendance[<?= e($student['id']) ?>][remarks]" value="<?= e($student['remarks'] ?? '') ?>" maxlength="500" class="w-full rounded-md border border-slate-300 bg-transparent px-2 py-1 dark:border-slate-700"></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$students): ?><tr><td colspan="4" class="px-5 py-6 text-center text-slate-500">No active students are enrolled in this section.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($students): ?><div class="flex justify-end border-t border-slate-100 p-4 dark:border-slate-800"><button class="rounded-lg bg-indigo-600 px-5 py-2 font-semibold text-white" type="submit">Save attendance</button></div><?php endif; ?>
</form>
<?php endif; ?>
