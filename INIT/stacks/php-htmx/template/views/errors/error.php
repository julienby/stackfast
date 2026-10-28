<?php use function Core\{e, url}; ?>
<h1 class="mb-2 text-2xl font-bold">Action impossible</h1>
<p class="mb-4"><?= e($message ?? 'Une erreur est survenue.') ?></p>
<p><a href="<?= e(url('/')) ?>" class="underline">Retour à l'accueil</a></p>
