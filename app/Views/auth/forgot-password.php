<?php $title = 'Password recovery | CampusOS'; ?>
<section class="mx-auto mt-10 max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <h1 class="text-2xl font-bold">Reset your password</h1>
    <p class="mt-2 text-sm text-slate-500">If the account is active, we will email a one-time reset link.</p>
    <form method="post" action="/password/forgot" class="mt-6 space-y-4">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <label class="block text-sm font-medium">Account email
            <input type="email" name="email" maxlength="254" required autocomplete="email" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2.5 dark:border-slate-700">
        </label>
        <button class="w-full rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white" type="submit">Send reset link</button>
    </form>
</section>
