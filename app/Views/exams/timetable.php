<?php $title = 'Examination timetable | CampusOS'; ?>
<div class="mb-6">
    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Examinations</p>
    <h1 class="mt-1 text-3xl font-bold">Examination timetable</h1>
    <p class="mt-2 text-slate-500">Schedule exams by term, class/section, subject, date, time, room, and marks.</p>
</div>
<?php if (!empty($message)): ?><div role="status" class="mb-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><?= e($message) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div role="alert" class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>

<section class="mb-8 rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
    <h2 class="font-semibold">Add examination</h2>
    <form method="post" action="/exams/timetable" class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <label class="text-sm font-medium sm:col-span-2 lg:col-span-1">Exam name
            <input name="name" required maxlength="160" placeholder="Mid-Term Mathematics" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <label class="text-sm font-medium">Term
            <select name="term_id" required class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700"><option value="">Select term</option><?php foreach ($terms as $term): ?><option value="<?= e($term['id']) ?>"><?= e($term['academic_year'] . ' · ' . $term['name']) ?></option><?php endforeach; ?></select>
        </label>
        <label class="text-sm font-medium">Class / section
            <select name="section_id" required class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700"><option value="">Select class / section</option><?php foreach ($sections as $section): ?><option value="<?= e($section['id']) ?>"><?= e($section['class_name'] . ' / ' . $section['section_name']) ?></option><?php endforeach; ?></select>
        </label>
        <label class="text-sm font-medium">Subject
            <select name="subject_id" required class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700"><option value="">Select subject</option><?php foreach ($subjects as $subject): ?><option value="<?= e($subject['id']) ?>"><?= e($subject['name'] . ' (' . $subject['code'] . ')') ?></option><?php endforeach; ?></select>
        </label>
        <label class="text-sm font-medium">Exam date
            <input type="date" name="exam_date" required class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <label class="text-sm font-medium">Start time
            <input type="time" name="starts_at" required class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <label class="text-sm font-medium">End time
            <input type="time" name="ends_at" required class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <label class="text-sm font-medium">Room / hall
            <input name="room" maxlength="80" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <label class="text-sm font-medium">Maximum marks
            <input type="number" name="max_marks" min="0.01" step="0.01" required class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <label class="text-sm font-medium">Weight %
            <input type="number" name="weight_percent" min="0.01" max="100" step="0.01" value="100" required class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <div class="sm:col-span-2 lg:col-span-3"><button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white">Schedule exam</button></div>
    </form>
</section>

<section class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800"><h2 class="font-semibold">Scheduled examinations</h2></div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-5 py-3">Date</th><th>Time</th><th>Exam</th><th>Class</th><th>Subject</th><th>Room</th><th>Marks</th></tr></thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($timetable as $exam): ?><tr><td class="px-5 py-3 whitespace-nowrap"><?= e($exam['exam_date']) ?></td><td class="whitespace-nowrap"><?= e($exam['starts_at'] ? substr((string) $exam['starts_at'], 0, 5) : '—') ?>–<?= e($exam['ends_at'] ? substr((string) $exam['ends_at'], 0, 5) : '—') ?></td><td><?= e($exam['name']) ?></td><td><?= e($exam['class_name'] . ' / ' . $exam['section_name']) ?></td><td><?= e($exam['subject_name']) ?></td><td><?= e($exam['room'] ?? '—') ?></td><td><?= e($exam['max_marks']) ?> · <?= e($exam['weight_percent']) ?>%</td></tr><?php endforeach; ?>
            <?php if (!$timetable): ?><tr><td colspan="7" class="px-5 py-8 text-center text-slate-500">No examinations scheduled.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
