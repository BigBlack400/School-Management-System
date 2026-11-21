<?php $title = ($school['name'] ?? 'School') . ' | Welcome'; ?>
<div class="-mx-3 -mt-5 sm:-mx-6 sm:-mt-8 lg:-mx-8">
    <section class="relative isolate overflow-hidden bg-slate-950 px-5 py-16 text-white sm:px-8 sm:py-20 lg:px-12 lg:py-24">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_right,rgba(99,102,241,0.35),transparent_42%),radial-gradient(circle_at_bottom_left,rgba(14,165,233,0.18),transparent_35%)]"></div>
        <div class="mx-auto grid max-w-7xl items-center gap-10 lg:grid-cols-2">
            <div>
                <p class="mb-4 inline-flex rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-indigo-200"><i class="fa-solid fa-star mr-2"></i>Learning · Character · Excellence</p>
                <h1 class="max-w-3xl text-4xl font-bold leading-tight sm:text-5xl lg:text-6xl"><?= e($school['name'] ?? 'School') ?></h1>
                <p class="mt-5 max-w-xl text-base leading-7 text-slate-300 sm:text-lg">A welcoming learning community where every student is known, challenged and inspired to achieve excellence.</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="/apply" class="inline-flex items-center justify-center rounded-xl bg-indigo-500 px-5 py-3 font-semibold text-white shadow-lg shadow-indigo-950/30 hover:bg-indigo-400"><i class="fa-solid fa-file-pen mr-2"></i>Apply for admission</a>
                    <a href="/about" class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-white/5 px-5 py-3 font-semibold text-white hover:bg-white/10"><i class="fa-solid fa-circle-info mr-2"></i>Discover our school</a>
                </div>
            </div>
            <div class="relative">
                <div class="mx-auto max-w-lg rounded-3xl border border-white/10 bg-white/10 p-5 backdrop-blur">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-indigo-500 p-5"><i class="fa-solid fa-user-graduate text-2xl"></i><p class="mt-5 text-3xl font-bold"><?= e($stats['students']) ?></p><p class="mt-1 text-sm text-indigo-100">Active students</p></div>
                        <div class="rounded-2xl bg-white p-5 text-slate-900"><i class="fa-solid fa-chalkboard-user text-2xl text-indigo-600"></i><p class="mt-5 text-3xl font-bold"><?= e($stats['teachers']) ?></p><p class="mt-1 text-sm text-slate-500">Dedicated teachers</p></div>
                        <div class="col-span-2 rounded-2xl border border-white/10 bg-slate-900/70 p-5"><div class="flex items-center justify-between"><div><p class="text-sm text-slate-400">Academic community</p><p class="mt-1 text-xl font-semibold"><?= e($stats['classes']) ?> active classes</p></div><i class="fa-solid fa-school text-3xl text-sky-400"></i></div></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white px-5 py-14 dark:bg-slate-900 sm:px-8 lg:px-12">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-2xl text-center"><p class="text-sm font-semibold uppercase tracking-widest text-indigo-600">Why choose us</p><h2 class="mt-2 text-3xl font-bold">A complete learning journey</h2></div>
            <div class="mt-10 grid gap-5 md:grid-cols-3">
                <article class="rounded-2xl border border-slate-200 p-6 dark:border-slate-800"><i class="fa-solid fa-book-open-reader text-2xl text-indigo-600"></i><h3 class="mt-4 font-bold">Strong academics</h3><p class="mt-2 text-sm leading-6 text-slate-500">Structured learning, experienced teachers and transparent progress reporting.</p></article>
                <article class="rounded-2xl border border-slate-200 p-6 dark:border-slate-800"><i class="fa-solid fa-shield-heart text-2xl text-indigo-600"></i><h3 class="mt-4 font-bold">Safe environment</h3><p class="mt-2 text-sm leading-6 text-slate-500">A caring community that supports wellbeing, discipline and personal growth.</p></article>
                <article class="rounded-2xl border border-slate-200 p-6 dark:border-slate-800"><i class="fa-solid fa-people-group text-2xl text-indigo-600"></i><h3 class="mt-4 font-bold">Family partnership</h3><p class="mt-2 text-sm leading-6 text-slate-500">Clear communication for parents, guardians and staff at every stage.</p></article>
            </div>
        </div>
    </section>

    <section class="bg-indigo-600 px-5 py-10 text-white sm:px-8 lg:px-12">
        <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-5 sm:flex-row sm:items-center"><div><h2 class="text-2xl font-bold">Ready to join our community?</h2><p class="mt-1 text-indigo-100">Submit an application and begin the admissions process.</p></div><a href="/apply" class="rounded-xl bg-white px-5 py-3 font-semibold text-indigo-700 hover:bg-indigo-50">Start application <i class="fa-solid fa-arrow-right ml-2"></i></a></div>
    </section>
</div>
