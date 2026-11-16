<?php
/** @var string $content @var ?array $user @var ?array $flash */
$path = request_path();
$nav = [
    'admin' => 'Escritorio',
    'admin/productos' => 'Productos',
    'admin/articulos' => 'Artículos y reseñas',
    'admin/estadisticas' => 'Estadísticas',
    'admin/nichos' => 'Nichos',
    'admin/ajustes' => 'Ajustes',
];
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Panel | <?= e(site_name()) ?></title>
<link rel="icon" href="<?= e(asset('img/icon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="adm">
<header class="adm-top">
  <a class="adm-brand" href="<?= e(url('admin')) ?>"><?= e(site_name()) ?> <span>panel</span></a>
  <nav class="adm-nav" aria-label="Panel">
    <?php foreach ($nav as $href => $label):
      $active = $href === 'admin' ? $path === '/admin' : str_starts_with($path, '/' . $href); ?>
      <a href="<?= e(url($href)) ?>"<?= $active ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="adm-user">
    <a href="<?= e(url('')) ?>" target="_blank" rel="noopener">Ver la web</a>
    <a href="<?= e(url('admin/cuenta')) ?>">Mi cuenta</a>
    <form method="post" action="<?= e(url('admin/salir')) ?>"><?= csrf_field() ?><button type="submit">Salir</button></form>
  </div>
</header>
<main class="adm-main">
  <?php if ($flash): ?><p class="adm-flash adm-flash--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p><?php endif; ?>
  <?= $content ?>
</main>
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>
