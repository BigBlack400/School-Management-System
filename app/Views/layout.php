<!doctype html>
<html lang="en" x-data="{ dark: localStorage.getItem('sms-theme') === 'dark', menu: false }" :class="{ 'dark': dark }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#4f46e5">
    <meta name="color-scheme" content="light dark">
    <title><?= e($title ?? 'School Management System') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
        html { -webkit-text-size-adjust: 100%; }
        body { min-width: 320px; }
        input, select, button, textarea { max-width: 100%; }
        input, select, textarea { font-size: 16px; }
        table { width: 100%; }
        @media (max-width: 640px) {
            main { padding-left: 0.75rem !important; padding-right: 0.75rem !important; }
            h1 { font-size: 1.5rem !important; line-height: 2rem !important; }
            table { min-width: 640px; }
            form table { min-width: 560px; }
        }
        @media (min-width: 641px) and (max-width: 1024px) {
            main { padding-left: 1.25rem !important; padding-right: 1.25rem !important; }
        }
    </style>
</head>
<body class="min-h-screen overscroll-none bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
<?php if (!empty($user)): ?>
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur dark:border-slate-800 dark:bg-slate-900/95">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-3 py-3 sm:px-6 lg:px-8">
            <a href="/dashboard" class="font-bold tracking-tight text-indigo-700 dark:text-indigo-300">Campus<span class="text-slate-700 dark:text-white">OS</span></a>
            <button type="button" @click="menu = !menu" class="rounded-lg border border-slate-200 px-3 py-2 text-slate-600 md:hidden dark:border-slate-700" :aria-expanded="menu" aria-controls="mobile-navigation" aria-label="Toggle navigation">
                <i :class="menu ? 'fa-solid fa-xmark' : 'fa-solid fa-bars'"></i>
            </button>
            <nav class="hidden items-center gap-3 text-sm md:flex lg:gap-5">
                <a class="whitespace-nowrap hover:text-indigo-600" href="/dashboard">Dashboard</a>
                <a class="whitespace-nowrap hover:text-indigo-600" href="/profile">Profile</a>
                <?php if (in_array('it_support', $user['roles'], true)): ?><a class="hover:text-cyan-600" href="/it-support">IT Support</a><?php endif; ?>
                <?php if (in_array('teacher', $user['roles'], true)): ?><a class="hover:text-indigo-600" href="/teacher/workspace">My Classroom</a><?php endif; ?>
                <?php if (in_array('head_teacher', $user['roles'], true) || in_array('school_administrator', $user['roles'], true) || in_array('super_administrator', $user['roles'], true)): ?>
                    <a class="hover:text-indigo-600" href="/school/control">School Control</a>
                <?php else: ?>
                    <?php if (App\Core\Auth::can('attendance.manage')): ?><a class="hover:text-indigo-600" href="/attendance">Attendance</a><?php endif; ?>
                    <?php if (App\Core\Auth::can('fees.manage')): ?><a class="hover:text-indigo-600" href="/fees/invoices">Fees</a><?php endif; ?>
                    <?php if (App\Core\Auth::can('users.manage')): ?><a class="hover:text-indigo-600" href="/teachers">Staff</a><?php endif; ?>
                <?php endif; ?>
                <span class="hidden text-slate-500 sm:inline"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></span>
                <button type="button" class="rounded-md px-2 py-1 hover:bg-slate-100 dark:hover:bg-slate-800" @click="dark = !dark; localStorage.setItem('sms-theme', dark ? 'dark' : 'light')" aria-label="Toggle dark mode"><i class="fa-solid fa-moon"></i></button>
                <form method="post" action="/logout">
                    <input type="hidden" name="_csrf" value="<?= e(App\Core\Csrf::token()) ?>">
                    <button class="text-slate-500 hover:text-red-600" type="submit">Sign out</button>
                </form>
            </nav>
        </div>
        <nav id="mobile-navigation" x-show="menu" x-cloak x-transition class="border-t border-slate-100 px-3 py-3 md:hidden dark:border-slate-800">
            <div class="mx-auto flex max-w-7xl flex-col gap-1">
                <a class="rounded-lg px-3 py-2.5 hover:bg-slate-100 dark:hover:bg-slate-800" href="/dashboard"><i class="fa-solid fa-gauge-high mr-2 w-5 text-indigo-600"></i>Dashboard</a>
                <a class="rounded-lg px-3 py-2.5 hover:bg-slate-100 dark:hover:bg-slate-800" href="/profile"><i class="fa-solid fa-user mr-2 w-5 text-indigo-600"></i>Profile</a>
                <?php if (in_array('it_support', $user['roles'], true)): ?>
                    <a class="rounded-lg px-3 py-2.5 hover:bg-slate-100 dark:hover:bg-slate-800" href="/it-support"><i class="fa-solid fa-laptop-code mr-2 w-5 text-cyan-600"></i>IT Support</a>
                <?php endif; ?>
                <?php if (in_array('teacher', $user['roles'], true)): ?>
                    <a class="rounded-lg px-3 py-2.5 hover:bg-slate-100 dark:hover:bg-slate-800" href="/teacher/workspace"><i class="fa-solid fa-chalkboard mr-2 w-5 text-indigo-600"></i>My Classroom</a>
                <?php endif; ?>
                <?php if (in_array('head_teacher', $user['roles'], true) || in_array('school_administrator', $user['roles'], true) || in_array('super_administrator', $user['roles'], true)): ?>
                    <a class="rounded-lg px-3 py-2.5 hover:bg-slate-100 dark:hover:bg-slate-800" href="/school/control"><i class="fa-solid fa-building-columns mr-2 w-5 text-indigo-600"></i>School Control</a>
                <?php else: ?>
                    <?php if (App\Core\Auth::can('attendance.manage')): ?><a class="rounded-lg px-3 py-2.5 hover:bg-slate-100 dark:hover:bg-slate-800" href="/attendance"><i class="fa-solid fa-clipboard-check mr-2 w-5 text-indigo-600"></i>Attendance</a><?php endif; ?>
                    <?php if (App\Core\Auth::can('fees.manage')): ?><a class="rounded-lg px-3 py-2.5 hover:bg-slate-100 dark:hover:bg-slate-800" href="/fees/invoices"><i class="fa-solid fa-file-invoice-dollar mr-2 w-5 text-indigo-600"></i>Fees</a><?php endif; ?>
                    <?php if (App\Core\Auth::can('users.manage')): ?><a class="rounded-lg px-3 py-2.5 hover:bg-slate-100 dark:hover:bg-slate-800" href="/teachers"><i class="fa-solid fa-users mr-2 w-5 text-indigo-600"></i>Staff</a><?php endif; ?>
                <?php endif; ?>
                <button type="button" @click="dark = !dark; localStorage.setItem('sms-theme', dark ? 'dark' : 'light')" class="rounded-lg px-3 py-2.5 text-left hover:bg-slate-100 dark:hover:bg-slate-800"><i class="fa-solid fa-moon mr-2 w-5 text-indigo-600"></i><span x-text="dark ? 'Light mode' : 'Dark mode'"></span></button>
                <form method="post" action="/logout" class="mt-1 border-t border-slate-100 pt-2 dark:border-slate-800">
                    <input type="hidden" name="_csrf" value="<?= e(App\Core\Csrf::token()) ?>">
                    <button type="submit" class="w-full rounded-lg px-3 py-2.5 text-left text-red-600 hover:bg-red-50 dark:hover:bg-red-950"><i class="fa-solid fa-right-from-bracket mr-2 w-5"></i>Sign out</button>
                </form>
            </div>
        </nav>
    </header>
<?php else: ?>
    <header class="sticky top-0 z-40 border-b border-white/10 bg-slate-950/95 text-white backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <a href="/" class="flex items-center gap-2 font-bold tracking-tight"><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-500"><i class="fa-solid fa-graduation-cap"></i></span><span><?= e($school['name'] ?? 'School') ?></span></a>
            <nav class="hidden items-center gap-6 text-sm md:flex">
                <a class="hover:text-indigo-300" href="/">Home</a>
                <a class="hover:text-indigo-300" href="/about">About</a>
                <a class="hover:text-indigo-300" href="/apply">Apply</a>
                <a class="rounded-lg bg-indigo-500 px-4 py-2 font-semibold text-white hover:bg-indigo-400" href="/login"><i class="fa-solid fa-right-to-bracket mr-2"></i>Portal login</a>
            </nav>
            <a class="rounded-lg border border-white/20 px-3 py-2 md:hidden" href="/login" aria-label="Portal login"><i class="fa-solid fa-right-to-bracket"></i></a>
        </div>
    </header>
<?php endif; ?>
<main class="mx-auto min-h-[calc(100vh-4.5rem)] w-full max-w-7xl px-3 py-5 pb-[env(safe-area-inset-bottom)] sm:px-6 sm:py-8 lg:px-8"><?= $content ?></main>
</body>
</html>
