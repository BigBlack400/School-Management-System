<?php $title = 'Reporting & oversight | CampusOS'; ?>
<div class="mb-8">
    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Head Teacher oversight</p>
    <h1 class="mt-1 text-3xl font-bold">Reports and implementation</h1>
    <p class="mt-2 text-slate-500">Academic, financial, attendance/compliance, security, workflow, migration, and vendor oversight.</p>
</div>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-chart-line text-indigo-600"></i><p class="mt-3 text-sm text-slate-500">Average GPA points</p><p class="mt-1 text-2xl font-bold"><?= e(number_format((float) $reports['gpa_average'], 2)) ?></p></div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-triangle-exclamation text-red-600"></i><p class="mt-3 text-sm text-slate-500">Failure grades</p><p class="mt-1 text-2xl font-bold"><?= e($reports['failing_grades']) ?></p></div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-clipboard-check text-indigo-600"></i><p class="mt-3 text-sm text-slate-500">Grades recorded</p><p class="mt-1 text-2xl font-bold"><?= e($reports['grades_recorded']) ?></p></div>
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm dark:border-emerald-900 dark:bg-emerald-950"><i class="fa-solid fa-sack-dollar text-emerald-600"></i><p class="mt-3 text-sm text-slate-500">Income collected</p><p class="mt-1 text-2xl font-bold"><?= e(number_format((float) $reports['income_collected'], 2)) ?></p></div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-file-invoice-dollar text-indigo-600"></i><p class="mt-3 text-sm text-slate-500">Outstanding fees</p><p class="mt-1 text-2xl font-bold"><?= e(number_format((float) $reports['outstanding_fees'], 2)) ?></p></div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-receipt text-indigo-600"></i><p class="mt-3 text-sm text-slate-500">Recorded expenses</p><p class="mt-1 text-2xl font-bold"><?= e(number_format((float) $reports['expenses'], 2)) ?></p></div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-user-check text-emerald-600"></i><p class="mt-3 text-sm text-slate-500">Present today</p><p class="mt-1 text-2xl font-bold"><?= e($reports['present']) ?></p></div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-user-xmark text-red-600"></i><p class="mt-3 text-sm text-slate-500">Absent / truancy today</p><p class="mt-1 text-2xl font-bold"><?= e($reports['absent']) ?></p></div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-clock text-amber-600"></i><p class="mt-3 text-sm text-slate-500">Late today</p><p class="mt-1 text-2xl font-bold"><?= e($reports['late']) ?></p></div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-shield-halved text-indigo-600"></i><p class="mt-3 text-sm text-slate-500">Audit events</p><p class="mt-1 text-2xl font-bold"><?= e($reports['audit_events']) ?></p></div>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <section class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="font-semibold">Security and implementation</h2>
        <ul class="mt-4 space-y-3 text-sm">
            <li class="flex items-center justify-between"><span><i class="fa-solid fa-shield-halved mr-2 text-indigo-600"></i>Security settings</span><a class="text-indigo-600 hover:underline" href="/school/settings">Configure</a></li>
            <li class="flex items-center justify-between"><span><i class="fa-solid fa-scroll mr-2 text-indigo-600"></i>Audit log review</span><a class="text-indigo-600 hover:underline" href="/school/control">View</a></li>
            <li class="flex items-center justify-between"><span><i class="fa-solid fa-code-branch mr-2 text-indigo-600"></i>Workflow / configuration approval</span><span class="rounded-full bg-amber-50 px-2 py-1 text-xs text-amber-700 dark:bg-amber-950 dark:text-amber-300">Approval authority</span></li>
            <li class="flex items-center justify-between"><span><i class="fa-solid fa-database mr-2 text-indigo-600"></i>Data migration oversight</span><span class="rounded-full bg-amber-50 px-2 py-1 text-xs text-amber-700 dark:bg-amber-950 dark:text-amber-300">Review required</span></li>
            <li class="flex items-center justify-between"><span><i class="fa-solid fa-file-contract mr-2 text-indigo-600"></i>Vendor SLA / update schedule</span><span class="rounded-full bg-amber-50 px-2 py-1 text-xs text-amber-700 dark:bg-amber-950 dark:text-amber-300">Oversight authority</span></li>
        </ul>
    </section>
    <section class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="font-semibold">Subordinate role boundaries</h2>
        <dl class="mt-4 space-y-3 text-sm">
            <div class="flex justify-between gap-4"><dt>Teachers</dt><dd class="text-right text-slate-500">Gradebook, attendance, classroom resources</dd></div>
            <div class="flex justify-between gap-4"><dt>Administrative staff</dt><dd class="text-right text-slate-500">Fees, basic student records, scheduling</dd></div>
            <div class="flex justify-between gap-4"><dt>IT Support</dt><dd class="text-right text-slate-500">Accounts, security monitoring, integrations; no financial/academic access</dd></div>
        </dl>
    </section>
</div>
