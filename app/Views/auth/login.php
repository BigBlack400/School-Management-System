<?php $title = 'Sign in | CampusOS'; ?>
<section class="mx-auto mt-10 max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="mb-7 text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600 text-white"><i class="fa-solid fa-graduation-cap"></i></div>
        <h1 class="text-2xl font-bold">Welcome back</h1>
        <p class="mt-2 text-sm text-slate-500">Sign in to your school account.</p>
    </div>
    <?php if (!empty($error)): ?><div role="alert" class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="/login" class="space-y-5">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <label class="block text-sm font-medium">Email
            <input class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2.5 dark:border-slate-700" type="email" name="email" autocomplete="username" required maxlength="254">
        </label>
        <label class="block text-sm font-medium">Password
            <div x-data="{ show: false }" class="relative mt-1">
                <input class="w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2.5 pr-12 dark:border-slate-700" :type="show ? 'text' : 'password'" name="password" autocomplete="current-password" required>
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 text-slate-500 hover:text-indigo-600" :aria-label="show ? 'Hide password' : 'Show password'" title="Show or hide password"><i :class="show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i></button>
            </div>
        </label>
        <button class="w-full rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white hover:bg-indigo-700" type="submit">Sign in</button>
    </form>
    <p class="mt-5 text-center text-sm"><a class="text-indigo-600 hover:underline" href="/password/forgot">Forgot your password?</a></p>
</section>
