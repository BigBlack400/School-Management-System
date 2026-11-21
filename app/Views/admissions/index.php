<?php $title = 'Admissions | CampusOS'; ?>
<div class="mb-6">
    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Student information</p>
    <h1 class="mt-1 text-3xl font-bold">Admissions</h1>
    <p class="mt-2 text-slate-500">Admit students, assign a class and section, and optionally create a student portal account.</p>
</div>

<?php if (!empty($message)): ?><div role="status" class="mb-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><?= e($message) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div role="alert" class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>

<section class="mb-8 rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
    <h2 class="font-semibold">Admit a student</h2>
    <form method="post" action="/admissions" class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <label class="text-sm font-medium">Admission number
            <input name="admission_number" maxlength="60" placeholder="Generated automatically if blank" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <label class="text-sm font-medium">First name
            <input name="first_name" required maxlength="100" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <label class="text-sm font-medium">Last name
            <input name="last_name" required maxlength="100" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <label class="text-sm font-medium">Date of birth
            <input type="date" name="date_of_birth" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <label class="text-sm font-medium">Gender
            <select name="gender" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
                <option value="undisclosed">Undisclosed</option>
                <option value="female">Female</option>
                <option value="male">Male</option>
                <option value="non_binary">Non-binary</option>
            </select>
        </label>
        <label class="text-sm font-medium">Admission date
            <input type="date" name="admission_date" required value="<?= e(date('Y-m-d')) ?>" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <label class="text-sm font-medium lg:col-span-2">Class / section
            <select name="section_id" required class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
                <option value="">Select class and section</option>
                <?php foreach ($sections as $section): ?><option value="<?= e($section['id']) ?>"><?= e($section['class_name'] . ' / ' . $section['section_name']) ?></option><?php endforeach; ?>
            </select>
        </label>
        <label class="text-sm font-medium">Student portal email
            <input type="email" name="student_email" maxlength="254" placeholder="Optional" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <label class="text-sm font-medium lg:col-span-2">Address
            <input name="address" maxlength="500" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <label class="text-sm font-medium lg:col-span-2">Student portal password
            <div x-data="{ show: false }" class="relative mt-1"><input :type="show ? 'text' : 'password'" name="student_password" minlength="14" maxlength="4096" autocomplete="new-password" placeholder="Required only with a portal email" class="w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pr-12 dark:border-slate-700"><button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 text-slate-500" :aria-label="show ? 'Hide password' : 'Show password'"><i :class="show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i></button></div>
        </label>
        <div class="lg:col-span-3"><button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white">Complete admission</button></div>
    </form>
</section>

<section class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800"><h2 class="font-semibold">Recent admissions</h2></div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-5 py-3">Student</th><th>Admission no.</th><th>Class / section</th><th>Admission date</th><th>Portal</th><th>Status</th></tr></thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($students as $student): ?><tr><td class="px-5 py-3 font-medium"><?= e($student['first_name'] . ' ' . $student['last_name']) ?></td><td><?= e($student['admission_number']) ?></td><td><?= e(($student['class_name'] ?? '—') . ' / ' . ($student['section_name'] ?? '—')) ?></td><td><?= e($student['admission_date']) ?></td><td><?= e($student['student_email'] ?? '—') ?></td><td class="capitalize"><?= e($student['status']) ?></td></tr><?php endforeach; ?>
            <?php if (!$students): ?><tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">No admissions have been recorded in this scope.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
