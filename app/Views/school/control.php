<?php $title = 'School control | CampusOS'; ?>
<div class="mb-8">
    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">School administration</p>
    <h1 class="mt-1 text-3xl font-bold">School control center</h1>
    <p class="mt-2 text-slate-500">School-wide operational oversight for users, academics, attendance, finance, and audit records.</p>
</div>

<section class="mb-8">
    <div class="mb-4">
        <h2 class="font-semibold">School controls</h2>
        <p class="mt-1 text-sm text-slate-500">Administrative actions are consolidated here.</p>
    </div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <a href="/admissions" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-indigo-300 dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-user-plus text-indigo-600"></i><h3 class="mt-3 font-semibold">Admissions</h3><p class="mt-1 text-sm text-slate-500">Admit and assign students.</p></a>
        <a href="/academics/classes" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-indigo-300 dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-school text-indigo-600"></i><h3 class="mt-3 font-semibold">Classes & sections</h3><p class="mt-1 text-sm text-slate-500">Create class levels and sections.</p></a>
        <a href="/exams/timetable" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-indigo-300 dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-calendar-days text-indigo-600"></i><h3 class="mt-3 font-semibold">Examination timetable</h3><p class="mt-1 text-sm text-slate-500">Schedule exams, rooms, and time slots.</p></a>
        <a href="/teachers" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-indigo-300 dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-chalkboard-user text-indigo-600"></i><h3 class="mt-3 font-semibold">Teachers & staff</h3><p class="mt-1 text-sm text-slate-500">Choose staff roles and teacher subjects.</p></a>
        <a href="/users/credentials" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-indigo-300 dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-key text-indigo-600"></i><h3 class="mt-3 font-semibold">Account credentials</h3><p class="mt-1 text-sm text-slate-500">Manage email and password access.</p></a>
        <a href="/status/manage" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-red-300 dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-user-shield text-red-600"></i><h3 class="mt-3 font-semibold">Status management</h3><p class="mt-1 text-sm text-slate-500">Suspend, expel, dismiss, or restore.</p></a>
        <a href="/attendance" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-indigo-300 dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-clipboard-check text-indigo-600"></i><h3 class="mt-3 font-semibold">Attendance</h3><p class="mt-1 text-sm text-slate-500">View and record attendance.</p></a>
        <a href="/fees/invoices" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-indigo-300 dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-file-invoice-dollar text-indigo-600"></i><h3 class="mt-3 font-semibold">Fees & income</h3><p class="mt-1 text-sm text-slate-500">Invoices, balances, and collection.</p></a>
        <a href="/fees/class-fees" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-indigo-300 dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-money-bill-transfer text-indigo-600"></i><h3 class="mt-3 font-semibold">Fees by class</h3><p class="mt-1 text-sm text-slate-500">Assign fees by grade level and generate invoices.</p></a>
        <a href="/school/settings" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-indigo-300 dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-gears text-indigo-600"></i><h3 class="mt-3 font-semibold">School settings</h3><p class="mt-1 text-sm text-slate-500">Name, code, timezone, and currency.</p></a>
        <a href="/profile" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-indigo-300 dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-id-card text-indigo-600"></i><h3 class="mt-3 font-semibold">Head Teacher profile</h3><p class="mt-1 text-sm text-slate-500">Own account details and security.</p></a>
        <a href="/reports" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-indigo-300 dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-chart-pie text-indigo-600"></i><h3 class="mt-3 font-semibold">Reports & oversight</h3><p class="mt-1 text-sm text-slate-500">Academic, financial, compliance, and SLA reporting.</p></a>
    </div>
</section>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <?php
    $cards = [
        ['Users', 'users', 'fa-users'],
        ['Teachers', 'teachers', 'fa-chalkboard-user'],
        ['Active students', 'students', 'fa-user-graduate'],
        ['Guardians', 'guardians', 'fa-user-group'],
        ['Classes', 'classes', 'fa-school'],
        ['Sections', 'sections', 'fa-layer-group'],
        ['Subjects', 'subjects', 'fa-book-open'],
        ['Exams', 'exams', 'fa-file-lines'],
        ['Grades', 'grades', 'fa-chart-column'],
        ['Invoices', 'invoices', 'fa-file-invoice'],
        ['Payments', 'payments', 'fa-money-check-dollar'],
        ['Audit events', 'audit', 'fa-clock-rotate-left'],
    ];
    foreach ($cards as [$label, $key, $icon]): ?>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <i class="fa-solid <?= e($icon) ?> text-indigo-600"></i>
            <p class="mt-3 text-sm text-slate-500"><?= e($label) ?></p>
            <p class="mt-1 text-2xl font-bold"><?= e($stats[$key]) ?></p>
        </div>
    <?php endforeach; ?>
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm dark:border-emerald-900 dark:bg-emerald-950">
        <i class="fa-solid fa-sack-dollar text-emerald-600"></i>
        <p class="mt-3 text-sm text-slate-500">Collected income</p>
        <p class="mt-1 text-2xl font-bold"><?= e(number_format((float) $stats['income'], 2)) ?></p>
    </div>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800"><h2 class="font-semibold">Recent users</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-5 py-3">Name</th><th>Email</th><th>Role</th><th>Status</th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <?php foreach ($users as $account): ?><tr><td class="px-5 py-3"><?= e($account['first_name'] . ' ' . $account['last_name']) ?></td><td><?= e($account['email']) ?></td><td><?= e($account['roles'] ?? '—') ?></td><td><span class="capitalize"><?= e($account['status']) ?></span> <a class="ml-2 text-indigo-600 hover:underline" href="/users/credentials?id=<?= e($account['id']) ?>">Edit credentials</a></td></tr><?php endforeach; ?>
                <?php if (!$users): ?><tr><td colspan="4" class="px-5 py-6 text-center text-slate-500">No users found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800"><h2 class="font-semibold">Recent students</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-5 py-3">Student</th><th>Admission</th><th>Class</th><th>Status</th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <?php foreach ($students as $student): ?><tr><td class="px-5 py-3"><?= e($student['first_name'] . ' ' . $student['last_name']) ?></td><td><?= e($student['admission_number']) ?></td><td><?= e(($student['class_name'] ?? '—') . ' / ' . ($student['section_name'] ?? '—')) ?></td><td class="capitalize"><?= e($student['status']) ?></td></tr><?php endforeach; ?>
                <?php if (!$students): ?><tr><td colspan="4" class="px-5 py-6 text-center text-slate-500">No students found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white lg:col-span-2 dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800"><h2 class="font-semibold">Recent audit activity</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-5 py-3">Time</th><th>Actor</th><th>Action</th><th>Record</th><th>IP</th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <?php foreach ($audit as $event): ?><tr><td class="px-5 py-3 whitespace-nowrap"><?= e($event['created_at']) ?></td><td><?= e(trim(($event['first_name'] ?? '') . ' ' . ($event['last_name'] ?? '')) ?: 'System') ?></td><td><?= e($event['action']) ?></td><td><?= e($event['entity_type']) ?><?= $event['entity_id'] ? ' #' . e($event['entity_id']) : '' ?></td><td><?= e($event['ip_address'] ?? '—') ?></td></tr><?php endforeach; ?>
                <?php if (!$audit): ?><tr><td colspan="5" class="px-5 py-6 text-center text-slate-500">No audit activity found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
