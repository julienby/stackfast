<?php use function Stack\{e, url}; ?>
<h1 class="mb-4 text-2xl font-bold">__SLUG__</h1>
<?php if (!empty($error)): ?>
  <p class="mb-4 rounded bg-red-100 p-2 text-red-800"><?= e($error) ?></p>
<?php endif; ?>
<form hx-post="<?= e(url('/items')) ?>" hx-target="#items" hx-swap="outerHTML" class="mb-6 flex gap-2">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
  <input name="label" required maxlength="200" placeholder="Nouvel élément" class="flex-1 rounded border px-3 py-2">
  <button class="rounded bg-gray-900 px-4 py-2 text-white">Ajouter</button>
</form>
<?php require __DIR__ . '/partials/items.php'; ?>
