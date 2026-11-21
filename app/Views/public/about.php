<?php $title = 'About ' . ($school['name'] ?? 'our school'); ?>
<section class="mx-auto max-w-5xl">
    <div class="rounded-3xl bg-slate-950 px-6 py-12 text-white sm:px-10 sm:py-16">
        <p class="text-sm font-semibold uppercase tracking-widest text-indigo-300">About us</p>
        <h1 class="mt-3 text-4xl font-bold"><?= e($school['name'] ?? 'Our School') ?></h1>
        <p class="mt-5 max-w-3xl text-lg leading-8 text-slate-300">We provide a disciplined, inclusive and forward-looking learning environment where students develop knowledge, character and practical skills.</p>
    </div>
    <div class="mt-8 grid gap-6 md:grid-cols-2">
        <article class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-bullseye text-2xl text-indigo-600"></i><h2 class="mt-4 text-xl font-bold">Our mission</h2><p class="mt-3 leading-7 text-slate-500">To nurture capable, responsible and confident learners prepared to contribute positively to society.</p></article>
        <article class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900"><i class="fa-solid fa-eye text-2xl text-indigo-600"></i><h2 class="mt-4 text-xl font-bold">Our vision</h2><p class="mt-3 leading-7 text-slate-500">To be a leading educational community recognized for academic excellence, innovation and strong values.</p></article>
    </div>
    <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="text-xl font-bold">Our values</h2>
        <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800"><i class="fa-solid fa-handshake text-indigo-600"></i><p class="mt-2 font-semibold">Integrity</p></div>
            <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800"><i class="fa-solid fa-lightbulb text-indigo-600"></i><p class="mt-2 font-semibold">Curiosity</p></div>
            <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800"><i class="fa-solid fa-users text-indigo-600"></i><p class="mt-2 font-semibold">Respect</p></div>
            <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800"><i class="fa-solid fa-medal text-indigo-600"></i><p class="mt-2 font-semibold">Excellence</p></div>
        </div>
    </div>
</section>
