<?php $title = 'Your profile | CampusOS'; ?>
<section class="max-w-2xl rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
    <h1 class="text-2xl font-bold">Your profile</h1>
    <?php if (!empty($message)): ?><div role="status" class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><?= e($message) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div role="alert" class="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="/profile" class="mt-6 grid gap-4 sm:grid-cols-2">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <label class="text-sm font-medium">First name
            <input name="first_name" maxlength="100" required value="<?= e($user['first_name']) ?>" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <label class="text-sm font-medium">Last name
            <input name="last_name" maxlength="100" required value="<?= e($user['last_name']) ?>" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <label class="text-sm font-medium">Phone
            <input name="phone" maxlength="32" value="<?= e($user['phone'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <label class="text-sm font-medium">Email
            <input type="email" name="email" required maxlength="254" value="<?= e($user['email']) ?>" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <div class="sm:col-span-2 mt-2 border-t border-slate-100 pt-5 dark:border-slate-800">
            <h2 class="font-semibold">Change password</h2>
            <p class="mt-1 text-sm text-slate-500">Leave all password fields blank to keep the current password.</p>
        </div>
        <label class="text-sm font-medium sm:col-span-2">Current password
            <div x-data="{ show: false }" class="relative mt-1"><input :type="show ? 'text' : 'password'" name="current_password" maxlength="4096" autocomplete="current-password" class="w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pr-12 dark:border-slate-700"><button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 text-slate-500" :aria-label="show ? 'Hide password' : 'Show password'"><i :class="show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i></button></div>
        </label>
        <label class="text-sm font-medium">New password
            <div x-data="{ show: false }" class="relative mt-1"><input :type="show ? 'text' : 'password'" name="new_password" minlength="14" maxlength="4096" autocomplete="new-password" class="w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pr-12 dark:border-slate-700"><button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 text-slate-500" :aria-label="show ? 'Hide password' : 'Show password'"><i :class="show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i></button></div>
        </label>
        <label class="text-sm font-medium">Confirm new password
            <div x-data="{ show: false }" class="relative mt-1"><input :type="show ? 'text' : 'password'" name="confirm_password" minlength="14" maxlength="4096" autocomplete="new-password" class="w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pr-12 dark:border-slate-700"><button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 text-slate-500" :aria-label="show ? 'Hide password' : 'Show password'"><i :class="show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i></button></div>
        </label>
        <div class="sm:col-span-2"><button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white">Save profile and credentials</button></div>
    </form>
</section>
