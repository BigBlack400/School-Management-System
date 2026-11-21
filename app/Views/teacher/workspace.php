<?php $title = 'Teacher workspace | CampusOS'; ?>
<div class="mb-6">
    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Teacher workspace</p>
    <h1 class="mt-1 text-3xl font-bold">Classes, marks & assignments</h1>
    <p class="mt-2 text-slate-500">Manage only the classes and subjects assigned to you.</p>
</div>
<?php if (!empty($message)): ?><div role="status" class="mb-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><?= e($message) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div role="alert" class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>

<?php if (!$allocations): ?>
    <section class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">
        <h2 class="font-semibold">No active subject allocations</h2>
        <p class="mt-2 text-sm">Ask the Head Teacher or administrator to assign you to a class and subject.</p>
    </section>
<?php endif; ?>

<div class="grid gap-6 lg:grid-cols-2">
    <section class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="font-semibold">Create class assignment</h2>
        <form method="post" action="/teacher/assignments" class="mt-5 space-y-4">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <label class="block text-sm font-medium">Class & subject
                <select name="allocation_id" required class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
                    <option value="">Select your allocation</option>
                    <?php foreach ($allocations as $allocation): ?><option value="<?= e($allocation['id']) ?>"><?= e($allocation['class_name'] . ' / ' . $allocation['section_name'] . ' · ' . $allocation['subject_name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label class="block text-sm font-medium">Assignment title
                <input name="title" required maxlength="160" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
            </label>
            <label class="block text-sm font-medium">Instructions
                <textarea name="instructions" maxlength="10000" rows="5" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700"></textarea>
            </label>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="text-sm font-medium">Due date
                    <input type="date" name="due_date" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
                </label>
                <label class="text-sm font-medium">Maximum marks
                    <input type="number" name="max_marks" min="0.01" step="0.01" value="100" required class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
                </label>
            </div>
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white" <?= !$allocations ? 'disabled' : '' ?>>Publish assignment</button>
        </form>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800"><h2 class="font-semibold">Your allocations</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-5 py-3">Class</th><th>Section</th><th>Subject</th></tr></thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($allocations as $allocation): ?><tr><td class="px-5 py-3"><?= e($allocation['class_name']) ?></td><td><?= e($allocation['section_name']) ?></td><td><?= e($allocation['subject_name']) ?></td></tr><?php endforeach; ?>
            <?php if (!$allocations): ?><tr><td colspan="3" class="px-5 py-8 text-center text-slate-500">No allocations.</td></tr><?php endif; ?>
            </tbody></table>
        </div>
    </section>
</div>

<section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800"><h2 class="font-semibold">Enter exam marks</h2></div>
    <?php if ($exams): ?>
    <form method="post" action="/teacher/marks" class="p-5">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <label class="block max-w-md text-sm font-medium">Exam
            <select name="exam_id" required class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
                <option value="">Select exam</option>
                <?php foreach ($exams as $exam): ?><option value="<?= e($exam['id']) ?>"><?= e($exam['exam_date'] . ' · ' . $exam['class_name'] . ' / ' . $exam['section_name'] . ' · ' . $exam['subject_name'] . ' · max ' . $exam['max_marks']) ?></option><?php endforeach; ?>
            </select>
        </label>
        <div class="mt-5 overflow-x-auto">
            <table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-5 py-3">Student</th><th>Admission</th><th>Marks</th><th>Comment</th></tr></thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($students as $student): ?><tr><td class="px-5 py-3"><?= e($student['first_name'] . ' ' . $student['last_name']) ?><div class="text-xs text-slate-500"><?= e($student['class_name'] . ' / ' . $student['section_name']) ?></div></td><td><?= e($student['admission_number']) ?></td><td><input type="number" name="marks[<?= e($student['id']) ?>]" min="0" step="0.01" class="w-28 rounded-md border border-slate-300 bg-transparent px-2 py-1 dark:border-slate-700"></td><td><input name="comments[<?= e($student['id']) ?>]" maxlength="1000" class="w-full rounded-md border border-slate-300 bg-transparent px-2 py-1 dark:border-slate-700"></td></tr><?php endforeach; ?>
            </tbody></table>
        </div>
        <?php if ($students): ?><div class="mt-5 text-right"><button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white">Save marks</button></div><?php endif; ?>
    </form>
    <?php else: ?>
        <p class="px-5 py-8 text-center text-sm text-slate-500">No allocated exams are available.</p>
    <?php endif; ?>
</section>

<section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800"><h2 class="font-semibold">Published assignments</h2></div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-5 py-3">Assignment</th><th>Class / subject</th><th>Due</th><th>Marks</th><th>Status</th></tr></thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
        <?php foreach ($assignments as $assignment): ?><tr><td class="px-5 py-3"><span class="font-medium"><?= e($assignment['title']) ?></span><div class="mt-1 max-w-md truncate text-xs text-slate-500"><?= e($assignment['instructions'] ?? '—') ?></div></td><td><?= e($assignment['class_name'] . ' / ' . $assignment['section_name'] . ' · ' . $assignment['subject_name']) ?></td><td><?= e($assignment['due_date'] ?? '—') ?></td><td><?= e($assignment['max_marks']) ?></td><td class="capitalize"><?= e($assignment['status']) ?></td></tr><?php endforeach; ?>
        <?php if (!$assignments): ?><tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">No assignments published.</td></tr><?php endif; ?>
        </tbody></table>
    </div>
</section>
