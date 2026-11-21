<?php $title = 'Teachers | CampusOS'; ?>
<div class="mb-6">
    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Staff</p>
    <h1 class="mt-1 text-3xl font-bold">Teachers & staff</h1>
    <p class="mt-2 text-slate-500">Create staff accounts and assign the appropriate school role.</p>
</div>
<?php if (!empty($message)): ?><div role="status" class="mb-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><?= e($message) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div role="alert" class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>

<section class="mb-8 rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
    <h2 class="font-semibold">Create staff account</h2>
    <form method="post" action="/teachers" x-data="{ role: 'teacher' }" class="mt-5 grid gap-4 sm:grid-cols-2">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <label class="text-sm font-medium">First name
            <input name="first_name" required maxlength="100" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <label class="text-sm font-medium">Last name
            <input name="last_name" required maxlength="100" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <label class="text-sm font-medium">Email
            <input type="email" name="email" required maxlength="254" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <label class="text-sm font-medium">Phone
            <input name="phone" maxlength="32" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <label class="text-sm font-medium sm:col-span-2">Staff role
            <select name="role" x-model="role" required class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
                <option value="teacher">Teacher</option>
                <option value="administrative_staff">Administrative Staff</option>
                <option value="it_support">IT Support</option>
            </select>
        </label>
        <div x-show="role === 'teacher'" x-cloak class="grid gap-4 sm:col-span-2 sm:grid-cols-2">
            <label class="text-sm font-medium">Class / section
                <select name="section_id" :required="role === 'teacher'" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
                    <option value="">Select class and section</option>
                    <?php foreach ($sections as $section): ?><option value="<?= e($section['id']) ?>"><?= e($section['class_name'] . ' / ' . $section['section_name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label class="text-sm font-medium">Subject / course
                <select name="subject_id" :required="role === 'teacher'" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
                    <option value="">Select subject or course</option>
                    <?php foreach ($subjects as $subject): ?><option value="<?= e($subject['id']) ?>"><?= e($subject['name'] . ' (' . $subject['code'] . ')') ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <label class="text-sm font-medium sm:col-span-2">Initial password
            <div x-data="{ show: false }" class="relative mt-1"><input :type="show ? 'text' : 'password'" name="password" required minlength="14" maxlength="4096" autocomplete="new-password" class="w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pr-12 dark:border-slate-700"><button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 text-slate-500" :aria-label="show ? 'Hide password' : 'Show password'"><i :class="show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i></button></div>
            <span class="mt-1 block text-xs font-normal text-slate-500">Use at least 14 characters. Require the teacher to change it after first sign-in.</span>
        </label>
        <div class="sm:col-span-2"><button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white">Create staff account</button></div>
    </form>
</section>

<section class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-5 py-3">Name</th><th>Email</th><th>Role</th><th>Subject / class allocation</th><th>Phone</th><th>Status</th></tr></thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($teachers as $teacher): ?><tr><td class="px-5 py-3 font-medium"><?= e($teacher['first_name'] . ' ' . $teacher['last_name']) ?></td><td><?= e($teacher['email']) ?></td><td><?= e($teacher['roles'] ?? '—') ?></td><td><?= e($teacher['allocations'] ?? '—') ?></td><td><?= e($teacher['phone'] ?? '—') ?></td><td class="capitalize"><?= e($teacher['status']) ?></td></tr><?php endforeach; ?>
            <?php if (!$teachers): ?><tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">No staff accounts currently exist in this authorized scope. Create one above.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
