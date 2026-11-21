<?php $title = 'IT Support dashboard | CampusOS'; ?>
<div class="mb-8">
    <p class="text-sm font-semibold uppercase tracking-wide text-cyan-600">IT operations</p>
    <h1 class="mt-1 text-3xl font-bold">IT Support dashboard</h1>
    <p class="mt-2 text-slate-500">Account, security, integration, notification, and audit monitoring.</p>
</div>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-users text-cyan-600"></i><p class="mt-3 text-sm text-slate-500">User accounts</p><p class="mt-1 text-3xl font-bold"><?= e($stats['accounts']) ?></p></div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-user-check text-emerald-600"></i><p class="mt-3 text-sm text-slate-500">Active accounts</p><p class="mt-1 text-3xl font-bold"><?= e($stats['active_accounts']) ?></p></div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-user-lock text-red-600"></i><p class="mt-3 text-sm text-slate-500">Inactive / suspended / dismissed</p><p class="mt-1 text-3xl font-bold"><?= e($stats['suspended_accounts']) ?></p></div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-shield-halved text-cyan-600"></i><p class="mt-3 text-sm text-slate-500">Audit events</p><p class="mt-1 text-3xl font-bold"><?= e($stats['audit_events']) ?></p></div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-bell-slash text-red-600"></i><p class="mt-3 text-sm text-slate-500">Failed notifications</p><p class="mt-1 text-3xl font-bold"><?= e($stats['failed_notifications']) ?></p></div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-lock text-amber-600"></i><p class="mt-3 text-sm text-slate-500">Currently locked throttles</p><p class="mt-1 text-3xl font-bold"><?= e($stats['locked_accounts']) ?></p></div>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800"><h2 class="font-semibold">Account operations</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-5 py-3">Account</th><th>Role</th><th>Status</th><th>Last login</th></tr></thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($accounts as $account): ?><tr><td class="px-5 py-3"><span class="font-medium"><?= e($account['first_name'] . ' ' . $account['last_name']) ?></span><div class="text-xs text-slate-500"><?= e($account['email']) ?></div></td><td><?= e($account['roles'] ?? '—') ?></td><td class="capitalize"><?= e($account['status']) ?></td><td><?= e($account['last_login_at'] ?? 'Never') ?></td></tr><?php endforeach; ?>
            </tbody></table>
        </div>
        <?php if (App\Core\Auth::can('users.manage')): ?><div class="border-t border-slate-100 p-4 dark:border-slate-800"><a class="text-sm font-medium text-cyan-700 hover:underline dark:text-cyan-300" href="/users/credentials"><i class="fa-solid fa-user-gear mr-1"></i>Manage account credentials</a></div><?php endif; ?>
        <div class="border-t border-slate-100 p-4 dark:border-slate-800"><a class="text-sm font-medium text-cyan-700 hover:underline dark:text-cyan-300" href="/it-support/roles"><i class="fa-solid fa-sliders mr-1"></i>Configure staff roles and access levels</a></div>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800"><h2 class="font-semibold">Recent security audit</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-5 py-3">Time</th><th>Action</th><th>Actor</th><th>IP</th></tr></thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($audit as $event): ?><tr><td class="px-5 py-3 whitespace-nowrap"><?= e($event['created_at']) ?></td><td><?= e($event['action']) ?></td><td><?= e(trim(($event['first_name'] ?? '') . ' ' . ($event['last_name'] ?? '')) ?: 'System') ?></td><td><?= e($event['ip_address'] ?? '—') ?></td></tr><?php endforeach; ?>
            </tbody></table>
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800"><h2 class="font-semibold">Notification delivery</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-5 py-3">Type</th><th>Channel</th><th>Status</th><th>Error</th></tr></thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($notifications as $notification): ?><tr><td class="px-5 py-3"><?= e($notification['event_type']) ?></td><td class="capitalize"><?= e($notification['channel']) ?></td><td class="capitalize"><?= e($notification['status']) ?></td><td><?= e($notification['error_summary'] ?? '—') ?></td></tr><?php endforeach; ?>
            </tbody></table>
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800"><h2 class="font-semibold">Active login locks</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-5 py-3">Failures</th><th>Locked until</th><th>Updated</th></tr></thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($locks as $lock): ?><tr><td class="px-5 py-3"><?= e($lock['failure_count']) ?></td><td><?= e($lock['locked_until']) ?></td><td><?= e($lock['updated_at']) ?></td></tr><?php endforeach; ?>
            <?php if (!$locks): ?><tr><td colspan="3" class="px-5 py-6 text-center text-slate-500">No active login locks.</td></tr><?php endif; ?>
            </tbody></table>
        </div>
    </section>
</div>

<section class="mt-8 rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
    <h2 class="font-semibold">Access boundary</h2>
    <p class="mt-2 text-sm text-slate-500">This dashboard does not expose student records, gradebooks, fee records, payroll, or income data. IT Support may manage accounts and monitor security/integration health within the assigned school scope.</p>
</section>
