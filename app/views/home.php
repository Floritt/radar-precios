<?php
/** @var array $deals @var array $popular @var array $reviews @var array $guides @var array $comparisons */
$niches = all_niches();
$telegram = setting('telegram_url');
$hasProducts = (bool) $deals;
?>
<div class="wrap page">
  <section class="panel hook">
    <div class="panel__body">
      <h1>Sigue el precio de cualquier producto de Amazon</h1>
      <p>Pega el enlace y te enseñamos cómo cambia su precio. Si quieres, te avisamos por Telegram cuando baje al precio que tú digas.</p>
      <form class="follow__row" method="post" action="<?= e(url('seguir')) ?>">
        <?= csrf_field() ?>
        <label class="visually-hidden" for="hook-link">Enlace de Amazon</label>
        <input id="hook-link" name="enlace" type="url" inputmode="url" required placeholder="https://www.amazon.es/dp/... o https://amzn.eu/d/..." autocomplete="off">
        <button class="btn btn--buy" type="submit">Seguir precio</button>
      </form>
    </div>
  </section>
  <div class="layout">
    <div>
      <?php if (!$hasProducts): ?>
      <?php if ($popular): ?>
        <div class="section-title"><h2>Regalos que seguimos</h2></div>
        <div class="grid" style="margin-bottom: 24px"><?php foreach ($popular as $p): partial('pcard', ['p' => $p]); endforeach; ?></div>
      <?php endif; ?>
      <?php if ($guides): ?>
        <div class="section-title"><h2>Guías de compra</h2><a href="<?= e(url('blog')) ?>">Ver todas</a></div>
        <div class="acards acards--home"><?php foreach ($guides as $post): partial('acard', ['post' => $post]); endforeach; ?></div>
      <?php endif; ?>
      <?php else: ?>
      <section class="panel" aria-labelledby="h-deals">
        <div class="panel__head">
          <h2 id="h-deals">Bajadas de precio de hoy</h2>
          <nav class="tabs" aria-label="Filtrar ofertas">
            <a href="<?= e(url('ofertas')) ?>">Todas</a>
            <a href="<?= e(url('ofertas?tipo=minimos')) ?>">Mínimos históricos</a>
            <a href="<?= e(url('ofertas?tipo=nuevos')) ?>">Recién añadidos</a>
          </nav>
        </div>
        <?php if ($deals): ?>
          <?php foreach ($deals as $p): partial('deal', ['p' => $p]); endforeach; ?>
          <a class="panel__more" href="<?= e(url('ofertas')) ?>">Ver todas las ofertas</a>
        <?php else: ?>
          <p class="empty">Todavía no hay bajadas de precio. En cuanto se registren cambios aparecerán aquí.</p>
        <?php endif; ?>
      </section>

      <?php if ($popular): ?>
      <section aria-labelledby="h-popular">
        <div class="section-title"><h2 id="h-popular">Regalos de Navidad que más se miran</h2></div>
        <div class="grid"><?php foreach ($popular as $p): partial('pcard', ['p' => $p]); endforeach; ?></div>
      </section>
      <?php endif; ?>
      <?php endif; ?>
    </div>

    <aside>
      <?php if (!$hasProducts && $comparisons): ?>
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
            <li><a href="<?= e(niche_url($n)) ?>"><?= e($n['name']) ?> <span><?= (int) $n['product_count'] ?: '' ?></span></a></li>
          <?php endforeach; ?>
        </ul>
      </section>
    </aside>
  </div>

  <?php if ($guides && $hasProducts): ?>
  <section aria-labelledby="h-guides" style="margin-top: 12px">
    <div class="section-title"><h2 id="h-guides">Guías de compra</h2><a href="<?= e(url('blog')) ?>">Ver todas</a></div>
    <?php $first = array_shift($guides); ?>
    <a class="feature" href="<?= e(post_url($first)) ?>">
      <span class="feature__img thumb<?= $first['cover_path'] ? '' : ' thumb--ph' ?>"<?= $first['cover_path'] ? '' : tint_attr($first) ?>><?php if ($first['cover_path']): ?><img src="<?= e(upload_url($first['cover_path'])) ?>" alt=""><?php else: ?><?= placeholder_svg($first['niche_slug'] ?? '') ?><?php endif; ?></span>
      <span class="feature__body">
        <span class="feature__kicker"><?= e(POST_TYPES[$first['type']] ?? 'Artículo') ?></span>
        <span class="feature__title"><?= e($first['title']) ?></span>
        <?php if ($first['excerpt']): ?><span class="feature__excerpt"><?= e($first['excerpt']) ?></span><?php endif; ?>
        <span class="byline"><?= $first['author'] ? 'Por ' . e($first['author']) . ', ' : '' ?><?= e(date_es($first['published_at'])) ?></span>
      </span>
    </a>
    <?php if ($guides): ?><div class="acards"><?php foreach ($guides as $post): partial('acard', ['post' => $post]); endforeach; ?></div><?php endif; ?>
  </section>
  <?php endif; ?>
</div>
