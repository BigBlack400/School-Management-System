<?php $title = 'Choose a new password | CampusOS'; ?>
<section class="mx-auto mt-10 max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <h1 class="text-2xl font-bold">Choose a new password</h1>
    <?php if (!empty($error)): ?><div role="alert" class="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="/password/reset" class="mt-6 space-y-4">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <label class="block text-sm font-medium">New password
            <div x-data="{ show: false }" class="relative mt-1">
                <input :type="show ? 'text' : 'password'" name="password" minlength="14" maxlength="4096" required autocomplete="new-password" class="w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2.5 pr-12 dark:border-slate-700">
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 text-slate-500" :aria-label="show ? 'Hide password' : 'Show password'"><i :class="show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i></button>
            </div>
        </label>
        <label class="block text-sm font-medium">Confirm new password
            <div x-data="{ show: false }" class="relative mt-1">
                <input :type="show ? 'text' : 'password'" name="password_confirmation" minlength="14" maxlength="4096" required autocomplete="new-password" class="w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2.5 pr-12 dark:border-slate-700">
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 text-slate-500" :aria-label="show ? 'Hide password' : 'Show password'"><i :class="show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i></button>
            </div>
        </label>
        <button class="w-full rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white" type="submit">Save new password</button>
    </form>
</section>
