<?php $title = 'Staff access levels | CampusOS'; ?>
<div class="mb-8">
    <p class="text-sm font-semibold uppercase tracking-wide text-cyan-600">IT access control</p>
    <h1 class="mt-1 text-3xl font-bold">Staff roles and permissions</h1>
    <p class="mt-2 text-slate-500">Configure only Teacher, Administrative Staff, and IT Support access levels.</p>
</div>
<?php if (!empty($message)): ?><div role="status" class="mb-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><?= e($message) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div role="alert" class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>

<div class="grid gap-6 lg:grid-cols-2">
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800"><h2 class="font-semibold">Staff account roles</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-5 py-3">Staff</th><th>Current role</th><th>Change access</th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <?php foreach ($accounts as $account): ?>
                    <tr><td class="px-5 py-3"><span class="font-medium"><?= e($account['first_name'] . ' ' . $account['last_name']) ?></span><div class="text-xs text-slate-500"><?= e($account['email']) ?> · <?= e($account['status']) ?></div></td>
                    <td><?= e($account['role_labels']) ?></td>
                    <td><form method="post" action="/it-support/roles" class="flex gap-2"><input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="account_id" value="<?= e($account['id']) ?>"><select name="role" class="rounded-md border border-slate-300 bg-transparent px-2 py-1 dark:border-slate-700"><option value="teacher">Teacher</option><option value="administrative_staff">Administrative Staff</option><option value="it_support">IT Support</option></select><button type="submit" class="rounded-md border border-cyan-300 px-2 py-1 text-cyan-700 dark:border-cyan-700 dark:text-cyan-300">Assign</button></form></td></tr>
                <?php endforeach; ?>
                <?php if (!$accounts): ?><tr><td colspan="3" class="px-5 py-8 text-center text-slate-500">No subordinate staff accounts found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800"><h2 class="font-semibold">Role permission matrix</h2></div>
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($roles as $role): ?>
                <div class="p-5"><h3 class="font-semibold"><?= e($role['label']) ?></h3><p class="mt-1 text-sm text-slate-500"><?= e($role['description']) ?></p><div class="mt-3 flex flex-wrap gap-2"><?php foreach (explode(', ', (string) $role['permissions']) as $permission): ?><?php if ($permission): ?><span class="rounded-full bg-cyan-50 px-2 py-1 text-xs text-cyan-800 dark:bg-cyan-950 dark:text-cyan-300"><?= e($permission) ?></span><?php endif; ?><?php endforeach; ?></div></div>
            <?php endforeach; ?>
        </div>
    </section>
</div>
