<?php
/** @var array $meta @var string $content */
$niches = all_niches();
$path = request_path();
$telegram = setting('telegram_url');
$whatsapp = setting('whatsapp_url');
$q = $path === '/buscar' ? (string) input('q', '') : '';
$counts = site_counts();
$isHome = $path === '/';
$showCats = !$isHome && !preg_match('#^/(blog|comparativas|resenas|ofertas|buscar|seguir)$#', $path);
$links = [];
if ($counts['priced']) { $links[] = ['ofertas', 'Ofertas']; }
$links[] = ['blog', 'Guías'];
if ($counts['comparisons']) { $links[] = ['comparativas', 'Comparativas']; }
if ($counts['reviews']) { $links[] = ['resenas', 'Reseñas']; }
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($meta['full_title']) ?></title>
<meta name="description" content="<?= e($meta['description']) ?>">
<link rel="canonical" href="<?= e($meta['canonical']) ?>">
<?php if ($meta['noindex']): ?><meta name="robots" content="noindex, follow"><?php endif; ?>
<meta property="og:site_name" content="<?= e(site_name()) ?>">
<meta property="og:title" content="<?= e($meta['title'] ?: site_name()) ?>">
<meta property="og:description" content="<?= e($meta['description']) ?>">
<meta property="og:type" content="<?= e($meta['type']) ?>">
<meta property="og:url" content="<?= e($meta['canonical']) ?>">
<meta property="og:locale" content="es_ES">
<?php if ($meta['image']): ?><meta property="og:image" content="<?= e($meta['image']) ?>"><?php endif; ?>
<meta name="theme-color" content="#1F3FD8">
<link rel="icon" href="<?= e(asset('img/icon.svg')) ?>" type="image/svg+xml">
<link rel="preload" href="<?= e(url('assets/fonts/red-hat-display.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(url('assets/fonts/red-hat-text.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
<?php foreach ($meta['jsonld'] as $ld): ?>
<script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php endforeach; ?>
</head>
<body<?= $isHome ? ' class="is-home"' : '' ?>>
<a class="skip" href="#contenido">Saltar al contenido</a>
<?php if (has_demo_content()): ?>
<div class="demo-strip">Contenido de ejemplo con precios inventados. Bórralo desde el panel antes de publicar la web.</div>
<?php endif; ?>

<header class="top">
  <div class="wrap top__bar">
    <?php partial('logo'); ?>
    <nav class="top__nav" aria-label="Secciones">
      <?php foreach ($links as [$href, $label]): ?>
        <a href="<?= e(url($href)) ?>"<?= str_starts_with($path, '/' . $href) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
      <?php if ($telegram): ?><a href="<?= e($telegram) ?>" rel="noopener">Telegram</a><?php endif; ?>
    </nav>
    <form class="top__search" action="<?= e(url('buscar')) ?>" method="get" role="search">
      <label class="visually-hidden" for="hq">Buscar un producto o pegar un enlace de Amazon</label>
      <input id="hq" name="q" type="search" value="<?= e($q) ?>" placeholder="Busca o pega un enlace" autocomplete="off">
      <button type="submit" aria-label="Buscar"><?= icon('magnifying-glass') ?></button>
    </form>
    <?php if ($path !== '/seguir'): ?><a class="btn btn--buy btn--sm top__cta" href="<?= e(url('seguir')) ?>">Seguir un precio</a><?php endif; ?>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-menu">Menú</button>
  </div>
  <nav class="mobile-menu" id="mobile-menu" aria-label="Menú">
    <form action="<?= e(url('buscar')) ?>" method="get" role="search">
      <label class="visually-hidden" for="mq">Buscar</label>
      <input class="field" id="mq" name="q" type="search" placeholder="Busca o pega un enlace de Amazon" autocomplete="off">
      <button class="btn btn--buy" type="submit">Buscar</button>
    </form>
    <a href="<?= e(url('seguir')) ?>">Seguir el precio de un producto</a>
    <?php foreach ($links as [$href, $label]): ?><a href="<?= e(url($href)) ?>"><?= e($label === 'Guías' ? 'Guías de compra' : $label) ?></a><?php endforeach; ?>
    <?php foreach ($niches as $n): ?><a href="<?= e(niche_url($n)) ?>"><?= e($n['name']) ?></a><?php endforeach; ?>
    <?php if ($telegram): ?><a href="<?= e($telegram) ?>" rel="noopener">Canal de Telegram</a><?php endif; ?>
  </nav>
</header>
<?php if ($showCats && $niches): ?>
<nav class="cat-bar" aria-label="Categorías">
  <ul class="wrap">
    <?php foreach ($niches as $n): ?>
      <li><a href="<?= e(niche_url($n)) ?>"<?= $path === '/nicho/' . $n['slug'] ? ' aria-current="page"' : '' ?>><?= e($n['name']) ?></a></li>
    <?php endforeach; ?>
  </ul>
</nav>
<?php endif; ?>

<main id="contenido">
<?= $content ?>
</main>

<footer class="foot">
  <div class="wrap foot__grid">
    <div class="foot__about">
      <?php partial('logo'); ?>
      <p><?= e(setting('tagline', 'Historial de precios y guías para comprar en el mejor momento.')) ?></p>
      <?php if ($telegram): ?><p><a href="<?= e($telegram) ?>" rel="noopener">Canal de Telegram</a></p><?php endif; ?>
      <?php if ($whatsapp): ?><p><a href="<?= e($whatsapp) ?>" rel="noopener">Canal de WhatsApp</a></p><?php endif; ?>
    </div>
    <div>
      <p class="foot__title">Categorías</p>
      <ul><?php foreach ($niches as $n): ?><li><a href="<?= e(niche_url($n)) ?>"><?= e($n['name']) ?></a></li><?php endforeach; ?></ul>
    </div>
    <div>
      <p class="foot__title">Secciones</p>
      <ul>
        <li><a href="<?= e(url('seguir')) ?>">Seguir un precio</a></li>
        <?php foreach ($links as [$href, $label]): ?><li><a href="<?= e(url($href)) ?>"><?= e($label === 'Guías' ? 'Guías de compra' : $label) ?></a></li><?php endforeach; ?>
        <li><a href="<?= e(url('legal/afiliacion')) ?>">Cómo nos financiamos</a></li>
      </ul>
    </div>
    <div>
      <p class="foot__title">Legal</p>
      <ul>
        <li><a href="<?= e(url('legal/aviso-legal')) ?>">Aviso legal</a></li>
        <li><a href="<?= e(url('legal/privacidad')) ?>">Privacidad</a></li>
        <li><a href="<?= e(url('legal/cookies')) ?>">Cookies</a></li>
      </ul>
    </div>
  </div>
  <div class="wrap foot__note">
    <p>Como afiliado de Amazon, obtengo ingresos por las compras adscritas que cumplen los requisitos aplicables. Los precios de Amazon se muestran con la fecha y hora de la última comprobación y pueden haber cambiado. Amazon y el logotipo de Amazon son marcas registradas de Amazon.com, Inc. o sus filiales.</p>
    <p>&copy; <?= date('Y') ?> <?= e(setting('legal_owner', site_name())) ?></p>
  </div>
</footer>

<script src="<?= e(asset('js/site.js')) ?>" defer></script>
<?php if (!empty($loadChart)): ?>
<script src="<?= e(asset('vendor/chart.umd.min.js')) ?>" defer></script>
<script src="<?= e(asset('js/product-chart.js')) ?>" defer></script>
<?php endif; ?>
</body>
</html>
