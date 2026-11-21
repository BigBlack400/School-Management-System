<?php $title = 'Student application'; ?>
<section class="mx-auto max-w-5xl">
    <div class="rounded-3xl bg-slate-950 px-6 py-10 text-white sm:px-10">
        <p class="text-sm font-semibold uppercase tracking-widest text-indigo-300">Admissions</p>
        <h1 class="mt-3 text-3xl font-bold sm:text-4xl">Student application</h1>
        <p class="mt-3 max-w-2xl text-slate-300">Complete this form to submit an application for admission. The admissions office will review and contact you.</p>
    </div>
    <?php if (!empty($message)): ?><div role="status" class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"><?= e($message) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div role="alert" class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="/apply" class="mt-6 grid gap-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:grid-cols-2 sm:p-7">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <label class="text-sm font-medium">Student first name<input name="first_name" required maxlength="100" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2.5 dark:border-slate-700"></label>
        <label class="text-sm font-medium">Student last name<input name="last_name" required maxlength="100" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2.5 dark:border-slate-700"></label>
        <label class="text-sm font-medium">Date of birth<input type="date" name="date_of_birth" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2.5 dark:border-slate-700"></label>
        <label class="text-sm font-medium">Desired class<select name="class_id" required class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2.5 dark:border-slate-700"><option value="">Select a class</option><?php foreach ($classes as $class): ?><option value="<?= e($class['id']) ?>"><?= e($class['name']) ?><?= $class['grade_level'] !== null ? ' · Level ' . e($class['grade_level']) : '' ?></option><?php endforeach; ?></select></label>
        <label class="text-sm font-medium">Student email<input type="email" name="email" required maxlength="254" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2.5 dark:border-slate-700"></label>
        <label class="text-sm font-medium">Student phone<input name="phone" maxlength="32" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2.5 dark:border-slate-700"></label>
        <label class="text-sm font-medium">Guardian name<input name="guardian_name" maxlength="120" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2.5 dark:border-slate-700"></label>
        <label class="text-sm font-medium">Guardian phone<input name="guardian_phone" maxlength="32" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2.5 dark:border-slate-700"></label>
        <label class="text-sm font-medium sm:col-span-2">Home address<input name="address" maxlength="500" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2.5 dark:border-slate-700"></label>
        <label class="text-sm font-medium sm:col-span-2">Additional information<textarea name="notes" maxlength="2000" rows="5" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2.5 dark:border-slate-700"></textarea></label>
        <div class="sm:col-span-2 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between"><p class="text-xs text-slate-500">By submitting, you confirm that the information provided is accurate.</p><button type="submit" class="rounded-xl bg-indigo-600 px-5 py-3 font-semibold text-white hover:bg-indigo-700"><i class="fa-solid fa-paper-plane mr-2"></i>Submit application</button></div>
    </form>
</section>
