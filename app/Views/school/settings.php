<?php $title = 'School settings | CampusOS'; ?>
<div class="mb-6">
    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">School configuration</p>
    <h1 class="mt-1 text-3xl font-bold">School settings</h1>
</div>
<?php if (!empty($message)): ?><div role="status" class="mb-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><?= e($message) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div role="alert" class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>
<section class="max-w-2xl rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
    <form method="post" action="/school/settings" class="grid gap-4 sm:grid-cols-2">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="school_id" value="<?= e($school['id']) ?>">
        <label class="text-sm font-medium sm:col-span-2">School name
            <input name="name" required maxlength="180" value="<?= e($school['name']) ?>" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
        </label>
        <label class="text-sm font-medium">School code
            <input name="code" required maxlength="40" value="<?= e($school['code']) ?>" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 uppercase dark:border-slate-700">
        </label>
        <label class="text-sm font-medium">Currency
            <input name="currency" required minlength="3" maxlength="3" value="<?= e($school['currency']) ?>" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 uppercase dark:border-slate-700">
        </label>
        <label class="text-sm font-medium sm:col-span-2">IANA timezone
            <input name="timezone" required maxlength="64" value="<?= e($school['timezone']) ?>" list="timezone-options" class="mt-1 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-700">
            <datalist id="timezone-options">
                <?php foreach (timezone_identifiers_list() as $timezone): ?><option value="<?= e($timezone) ?>"></option><?php endforeach; ?>
            </datalist>
        </label>
        <div class="sm:col-span-2"><button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white">Save school settings</button></div>
    </form>
</section>
