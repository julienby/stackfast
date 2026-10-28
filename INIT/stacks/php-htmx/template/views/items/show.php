<?php use function Core\{e, url}; ?>
<p class="mb-4"><a href="<?= e(url('/')) ?>" class="text-sm text-gray-600 underline">← Retour</a></p>
<h1 class="mb-2 text-2xl font-bold"><?= e($item['label']) ?></h1>
<p class="text-sm text-gray-500">Ajouté le <?= e($item['created_at']) ?></p>
