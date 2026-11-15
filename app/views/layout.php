<?php
/** @var array $meta @var string $content */
$niches = all_niches();
$path = request_path();
$telegram = setting('telegram_url');
$whatsapp = setting('whatsapp_url');
$q = $path === '/buscar' ? (string) input('q', '') : '';
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
<meta name="theme-color" content="#FFFFFF">
<link rel="icon" href="<?= e(asset('img/icon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
<?php foreach ($meta['jsonld'] as $ld): ?>
<script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php endforeach; ?>
</head>
<body>
<a class="skip" href="#contenido">Saltar al contenido</a>
<?php if (has_demo_content()): ?>
<div class="demo-strip">Contenido de ejemplo con precios inventados. Bórralo desde el panel antes de publicar la web.</div>
<?php endif; ?>

<header class="site-header">
  <div class="wrap site-header__bar">
    <a class="brand" href="<?= e(url('')) ?>">
      <span class="brand__mark"><svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="#fff" stroke-width="2.2" stroke-linejoin="round"><path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9Z"/><circle cx="8" cy="8" r="1.6" fill="#fff" stroke="none"/></svg></span>
      <span class="brand__name"><?= e(site_name()) ?></span>
    </a>
    <form class="header-search" action="<?= e(url('buscar')) ?>" method="get" role="search">
      <label class="visually-hidden" for="hq">Buscar</label>
      <input id="hq" name="q" type="search" value="<?= e($q) ?>" placeholder="Buscar productos o pegar un enlace de Amazon" autocomplete="off">
      <button type="submit" aria-label="Buscar"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="2.2"/><path d="m15.5 15.5 5 5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg></button>
    </form>
    <nav class="header-links" aria-label="Secciones">
      <a href="<?= e(url('ofertas')) ?>"<?= str_starts_with($path, '/ofertas') ? ' aria-current="page"' : '' ?>>Ofertas</a>
      <a href="<?= e(url('resenas')) ?>"<?= str_starts_with($path, '/resenas') ? ' aria-current="page"' : '' ?>>Reseñas</a>
      <a href="<?= e(url('blog')) ?>"<?= str_starts_with($path, '/blog') ? ' aria-current="page"' : '' ?>>Guías</a>
      <?php if ($telegram): ?><a class="btn-channel" href="<?= e($telegram) ?>" rel="noopener">Telegram</a><?php endif; ?>
    </nav>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-menu">Menú</button>
  </div>
  <nav class="mobile-menu" id="mobile-menu" aria-label="Menú">
    <a href="<?= e(url('ofertas')) ?>">Ofertas</a>
    <a href="<?= e(url('resenas')) ?>">Reseñas</a>
    <a href="<?= e(url('blog')) ?>">Guías de compra</a>
    <?php if ($telegram): ?><a href="<?= e($telegram) ?>" rel="noopener">Canal de Telegram</a><?php endif; ?>
  </nav>
  <nav class="cat-bar" aria-label="Categorías">
    <ul class="wrap">
      <?php foreach ($niches as $n): ?>
        <li><a href="<?= e(niche_url($n)) ?>"<?= $path === '/nicho/' . $n['slug'] ? ' aria-current="page"' : '' ?>><?= e($n['name']) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </nav>
</header>

<main id="contenido">
<?= $content ?>
</main>

<footer class="site-footer">
  <div class="wrap site-footer__grid">
    <div class="site-footer__about">
      <p class="site-footer__name"><?= e(site_name()) ?></p>
      <p><?= e(setting('tagline', 'Historial de precios y reseñas para comprar en el mejor momento.')) ?></p>
      <?php if ($telegram): ?><p><a href="<?= e($telegram) ?>" rel="noopener">Canal de Telegram</a></p><?php endif; ?>
      <?php if ($whatsapp): ?><p><a href="<?= e($whatsapp) ?>" rel="noopener">Canal de WhatsApp</a></p><?php endif; ?>
    </div>
    <div>
      <p class="site-footer__title">Categorías</p>
      <ul><?php foreach ($niches as $n): ?><li><a href="<?= e(niche_url($n)) ?>"><?= e($n['name']) ?></a></li><?php endforeach; ?></ul>
    </div>
    <div>
      <p class="site-footer__title">Secciones</p>
      <ul>
        <li><a href="<?= e(url('ofertas')) ?>">Ofertas</a></li>
        <li><a href="<?= e(url('resenas')) ?>">Reseñas</a></li>
        <li><a href="<?= e(url('blog')) ?>">Guías de compra</a></li>
        <li><a href="<?= e(url('legal/afiliacion')) ?>">Cómo nos financiamos</a></li>
      </ul>
    </div>
    <div>
      <p class="site-footer__title">Legal</p>
      <ul>
        <li><a href="<?= e(url('legal/aviso-legal')) ?>">Aviso legal</a></li>
        <li><a href="<?= e(url('legal/privacidad')) ?>">Privacidad</a></li>
        <li><a href="<?= e(url('legal/cookies')) ?>">Cookies</a></li>
      </ul>
    </div>
  </div>
  <div class="wrap site-footer__note">
    <p>Como afiliado de Amazon, obtengo ingresos por las compras adscritas que cumplen los requisitos aplicables. También participamos en los programas de afiliados de Temu y Shein. Los precios de Amazon se muestran con la fecha y hora de la última comprobación y pueden haber cambiado.</p>
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
