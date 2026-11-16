<?php
/** @var array $deals @var array $popular @var array $reviews @var array $guides @var array $comparisons */
$niches = all_niches();
$telegram = setting('telegram_url');
$hasDeals = (bool) $deals;
$first = $guides ? array_shift($guides) : null;
?>
<div class="wrap page">
  <?php if (!$hasDeals): ?>
  <header class="home-head">
    <h1>Regalos de Navidad, bien elegidos y al mejor precio</h1>
    <p>Qué mirar antes de comprar cada regalo, en qué se diferencian los productos parecidos y cómo saber si una oferta es de verdad.</p>
  </header>
  <?php endif; ?>

  <div class="layout">
    <div>
      <?php if ($hasDeals): ?>
      <section class="panel block" aria-labelledby="h-deals">
        <div class="panel__head">
          <h1 id="h-deals">Bajadas de precio de hoy</h1>
          <nav class="tabs" aria-label="Filtrar ofertas">
            <a href="<?= e(url('ofertas')) ?>">Todas</a>
            <a href="<?= e(url('ofertas?tipo=minimos')) ?>">Mínimos históricos</a>
            <a href="<?= e(url('ofertas?tipo=nuevos')) ?>">Recién añadidos</a>
          </nav>
        </div>
        <?php foreach ($deals as $p): partial('deal', ['p' => $p]); endforeach; ?>
        <a class="panel__more" href="<?= e(url('ofertas')) ?>">Ver todas las ofertas</a>
      </section>
      <?php endif; ?>

      <?php if ($first): ?>
      <section class="block" aria-labelledby="h-guides">
        <div class="section-title"><h2 id="h-guides">Guías de compra</h2><a href="<?= e(url('blog')) ?>">Ver todas</a></div>
        <a class="feature" href="<?= e(post_url($first)) ?>"<?= tint_attr($first) ?>>
          <?php if ($first['cover_path']): ?><span class="feature__img"><img src="<?= e(upload_url($first['cover_path'])) ?>" alt=""></span>
          <?php else: ?><?= article_cover($first, 'feature__img') ?><?php endif; ?>
          <span>
            <span class="kicker"><?= e(!empty($first['niche_name']) ? $first['niche_name'] : (POST_TYPES[$first['type']] ?? 'Artículo')) ?></span>
            <span class="feature__title"><?= e($first['title']) ?></span>
            <?php if ($first['excerpt']): ?><span class="feature__excerpt"><?= e($first['excerpt']) ?></span><?php endif; ?>
            <span class="byline"><?= $first['author'] ? 'Por ' . e($first['author']) . ', ' : '' ?><?= e(date_es($first['published_at'])) ?></span>
          </span>
        </a>
        <?php if ($guides): ?><div class="alist"><?php foreach ($guides as $post): partial('arow', ['post' => $post]); endforeach; ?></div><?php endif; ?>
      </section>
      <?php endif; ?>

      <?php if ($popular): ?>
      <section class="block" aria-labelledby="h-popular">
        <div class="section-title"><h2 id="h-popular"><?= $hasDeals ? 'Los regalos que más se miran' : 'Regalos que seguimos' ?></h2></div>
        <div class="grid"><?php foreach ($popular as $p): partial('pcard', ['p' => $p]); endforeach; ?></div>
      </section>
      <?php endif; ?>
    </div>

    <aside>
      <?php if ($comparisons): ?>
      <section class="panel">
        <div class="panel__head"><h2>Comparativas</h2><a href="<?= e(url('comparativas')) ?>">Ver todas</a></div>
        <?php foreach ($comparisons as $post): partial('mini-post', ['post' => $post]); endforeach; ?>
      </section>
      <?php endif; ?>

      <?php if ($reviews): ?>
      <section class="panel">
        <div class="panel__head"><h2>Reseñas</h2><a href="<?= e(url('resenas')) ?>">Ver todas</a></div>
        <?php foreach ($reviews as $post): partial('mini-post', ['post' => $post]); endforeach; ?>
      </section>
      <?php endif; ?>

      <?php if ($telegram): ?>
      <section class="panel channel-box">
        <h2>Avisos en Telegram</h2>
        <p>Publicamos cada producto que toca su precio más bajo de los últimos seis meses.</p>
        <a class="btn" href="<?= e($telegram) ?>" rel="noopener">Unirme al canal</a>
      </section>
      <?php endif; ?>

      <section class="panel">
        <div class="panel__head"><h2>Categorías</h2></div>
        <ul class="side-list">
          <?php foreach ($niches as $n): ?>
            <li><a href="<?= e(niche_url($n)) ?>"<?= tint_attr(['niche_color' => $n['color']]) ?>><?= e($n['name']) ?> <span><?= (int) $n['product_count'] ?: '' ?></span></a></li>
          <?php endforeach; ?>
        </ul>
      </section>
    </aside>
  </div>
</div>
