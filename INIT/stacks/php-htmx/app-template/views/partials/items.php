<?php use function Stack\e; ?>
<ul id="items" class="divide-y rounded border bg-white">
  <?php if (!empty($error)): ?>
    <li class="bg-red-100 px-3 py-2 text-red-800"><?= e($error) ?></li>
  <?php endif; ?>
  <?php foreach ($items as $item): ?>
    <li class="px-3 py-2"><?= e($item['label']) ?></li>
  <?php endforeach; ?>
  <?php if (!$items): ?>
    <li class="px-3 py-2 text-gray-500">Rien pour l'instant.</li>
  <?php endif; ?>
</ul>
