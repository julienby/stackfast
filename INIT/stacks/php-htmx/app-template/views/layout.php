<?php use function Stack\{e, url}; ?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? '__SLUG__') ?></title>
  <script src="https://cdn.jsdelivr.net/npm/htmx.org@4.0.0/dist/htmx.min.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
  <header class="border-b bg-white">
    <nav class="mx-auto max-w-3xl px-4 py-3">
      <a href="<?= e(url('/')) ?>" class="font-semibold">__SLUG__</a>
    </nav>
  </header>
  <main class="mx-auto max-w-3xl px-4 py-6">
    <?= $content ?>
  </main>
</body>
</html>
