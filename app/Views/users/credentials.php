<?php $title = 'Account credentials | CampusOS'; ?>
<div class="mb-6">
    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">User administration</p>
    <h1 class="mt-1 text-3xl font-bold">Account email and password</h1>
</div>
<?php if (!empty($message)): ?><div role="status" class="mb-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><?= e($message) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div role="alert" class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>

<div class="grid gap-6 lg:grid-cols-3">
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white lg:col-span-2 dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-5 py-3">Name</th><th>Email</th><th>Roles</th><th>Status</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <?php foreach ($accounts as $account): ?><tr><td class="px-5 py-3 font-medium"><?= e($account['first_name'] . ' ' . $account['last_name']) ?></td><td><?= e($account['email']) ?></td><td><?= e($account['roles'] ?? '—') ?></td><td class="capitalize"><?= e($account['status']) ?></td><td><a class="text-indigo-600 hover:underline" href="/users/credentials?id=<?= e($account['id']) ?>">Edit</a></td></tr><?php endforeach; ?>
                <?php if (!$accounts): ?><tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">No authorized accounts found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="font-semibold"><?= $selected ? 'Edit ' . e($selected['first_name'] . ' ' . $selected['last_name']) : 'Select an account' ?></h2>
        <?php if ($selected): ?>
        <form method="post" action="/users/credentials" class="mt-5 space-y-4">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="account_id" value="<?= e($selected['id']) ?>">
            <label class="block text-sm font-medium">Email
                <input type="email" name="email" required maxlength="254" value="<?= e($selected['email']) ?>" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
            </label>
            <label class="block text-sm font-medium">New password
                <div x-data="{ show: false }" class="relative mt-1"><input :type="show ? 'text' : 'password'" name="new_password" minlength="14" maxlength="4096" autocomplete="new-password" class="w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pr-12 dark:border-slate-700"><button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 text-slate-500" :aria-label="show ? 'Hide password' : 'Show password'"><i :class="show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i></button></div>
            </label>
            <label class="block text-sm font-medium">Confirm new password
                <div x-data="{ show: false }" class="relative mt-1"><input :type="show ? 'text' : 'password'" name="confirm_password" minlength="14" maxlength="4096" autocomplete="new-password" class="w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pr-12 dark:border-slate-700"><button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 text-slate-500" :aria-label="show ? 'Hide password' : 'Show password'"><i :class="show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i></button></div>
                <span class="mt-1 block text-xs font-normal text-slate-500">Leave both password fields blank to keep the existing password.</span>
            </label>
            <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 font-semibold text-white">Save credentials</button>
        </form>
        <?php else: ?>
            <p class="mt-3 text-sm text-slate-500">Choose an account from the list or from the School Control page.</p>
        <?php endif; ?>
    </section>
</div>
