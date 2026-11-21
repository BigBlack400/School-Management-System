<?php $title = 'Classes & sections | CampusOS'; ?>
<div class="mb-6">
    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Academics</p>
    <h1 class="mt-1 text-3xl font-bold">Classes and sections</h1>
    <p class="mt-2 text-slate-500">Add class levels and create sections for admissions, fees, attendance, and allocation.</p>
</div>
<?php if (!empty($message)): ?><div role="status" class="mb-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><?= e($message) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div role="alert" class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>

<div class="grid gap-6 lg:grid-cols-2" x-data="{ type: 'class' }">
    <section class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="font-semibold">Create class or section</h2>
        <form method="post" action="/academics/classes" class="mt-5 grid gap-4 sm:grid-cols-2">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <label class="text-sm font-medium sm:col-span-2">Type
                <select name="type" x-model="type" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
                    <option value="class">Class / grade level</option>
                    <option value="section">Section</option>
                </select>
            </label>
            <label class="text-sm font-medium" x-show="type === 'class'">
                Academic year
                <select name="academic_year_id" :required="type === 'class'" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
                    <option value="">Select academic year</option>
                    <?php foreach ($academicYears as $year): ?><option value="<?= e($year['id']) ?>" <?= $year['is_current'] ? 'selected' : '' ?>><?= e($year['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label class="text-sm font-medium" x-show="type === 'section'">
                Class
                <select name="class_id" :required="type === 'section'" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
                    <option value="">Select class</option>
                    <?php foreach ($classes as $class): ?><option value="<?= e($class['id']) ?>"><?= e($class['name']) ?><?= $class['grade_level'] !== null ? ' · Level ' . e($class['grade_level']) : '' ?></option><?php endforeach; ?>
                </select>
            </label>
            <label class="text-sm font-medium" x-bind:class="{ 'sm:col-span-2': type === 'section' }">Name
                <input name="name" required maxlength="120" placeholder="Grade 2 or Section A" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
            </label>
            <label class="text-sm font-medium" x-show="type === 'class'">Grade level
                <input type="number" name="grade_level" min="0" max="65535" placeholder="For example 2" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
            </label>
            <label class="text-sm font-medium" x-show="type === 'section'">Section capacity
                <input type="number" name="capacity" min="1" max="65535" placeholder="For example 30" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
            </label>
            <label class="text-sm font-medium sm:col-span-2">Status
                <select name="status" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700"><option value="active">Active</option><option value="inactive">Inactive</option></select>
            </label>
            <div class="sm:col-span-2"><button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white">Create</button></div>
        </form>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800"><h2 class="font-semibold">Classes</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-5 py-3">Class</th><th>Level</th><th>Academic year</th><th>Sections</th><th>Status</th></tr></thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($classes as $class): ?><tr><td class="px-5 py-3 font-medium"><?= e($class['name']) ?></td><td><?= e($class['grade_level'] ?? '—') ?></td><td><?= e($class['academic_year']) ?></td><td><?= e($class['section_count']) ?></td><td class="capitalize"><?= e($class['status']) ?></td></tr><?php endforeach; ?>
            <?php if (!$classes): ?><tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">No classes found.</td></tr><?php endif; ?>
            </tbody></table>
        </div>
    </section>
</div>

<section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800"><h2 class="font-semibold">Sections</h2></div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-5 py-3">Class</th><th>Section</th><th>Capacity</th><th>Status</th></tr></thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
        <?php foreach ($sections as $section): ?><tr><td class="px-5 py-3"><?= e($section['class_name']) ?><?= $section['grade_level'] !== null ? ' · Level ' . e($section['grade_level']) : '' ?></td><td><?= e($section['name']) ?></td><td><?= e($section['capacity'] ?? 'Unlimited') ?></td><td class="capitalize"><?= e($section['status']) ?></td></tr><?php endforeach; ?>
        <?php if (!$sections): ?><tr><td colspan="4" class="px-5 py-8 text-center text-slate-500">No sections found.</td></tr><?php endif; ?>
        </tbody></table>
    </div>
</section>
