<?php
/** @var array $deals @var array $popular @var array $reviews @var array $guides @var array $comparisons */
$niches = all_niches();
$telegram = setting('telegram_url');
$lead = $guides ? array_shift($guides) : null;
?>
<section class="hero">
  <div class="wrap hero__grid">
    <div>
      <h1>Sigue el precio de cualquier producto de Amazon</h1>
      <p class="hero__lead">Pega el enlace y te enseñamos cómo ha cambiado su precio, para que sepas si hoy es buen momento o conviene esperar.<?= alerts_enabled() ? ' Si quieres, te avisamos ' . e(alert_channels_text()) . ' cuando baje.' : '' ?></p>
      <form class="paste" method="post" action="<?= e(url('seguir')) ?>">
        <?= csrf_field() ?>
        <label class="visually-hidden" for="hook-link">Enlace de Amazon</label>
        <input id="hook-link" name="enlace" type="url" inputmode="url" required placeholder="Pega aquí el enlace de Amazon" autocomplete="off">
        <button class="btn btn--buy" type="submit">Seguir precio</button>
      </form>
      <p class="hero__hint">Gratis y sin registrarte. Sirven los enlaces de amazon.es y los cortos de la app.</p>
    </div>
    <?php partial('hero-chart'); ?>
  </div>
</section>

<?php if ($niches): ?>
<nav class="cats" aria-label="Categorías">
  <ul class="wrap">
    <?php foreach ($niches as $n): ?>
      <li><a href="<?= e(niche_url($n)) ?>"><?= niche_icon($n['slug']) ?><span><?= e($n['name']) ?></span></a></li>
    <?php endforeach; ?>
  </ul>
</nav>
<?php endif; ?>

<?php if ($deals): ?>
<section class="sec" aria-labelledby="h-deals">
  <div class="wrap">
    <div class="sec__head">
      <h2 id="h-deals">Bajadas de precio de hoy</h2>
      <nav class="tabs" aria-label="Filtrar ofertas">
        <a href="<?= e(url('ofertas?tipo=minimos')) ?>">Mínimos históricos</a>
        <a href="<?= e(url('ofertas?tipo=nuevos')) ?>">Recién añadidos</a>
      </nav>
    </div>
    <div class="deals"><?php foreach ($deals as $p): partial('deal', ['p' => $p]); endforeach; ?></div>
    <a class="more" href="<?= e(url('ofertas')) ?>">Ver todas las ofertas</a>
  </div>
</section>
<?php endif; ?>

<?php if ($lead): ?>
<section class="sec<?= $deals ? ' sec--tight' : '' ?>" aria-labelledby="h-guides">
  <div class="wrap">
    <div class="sec__head">
      <h2 id="h-guides">Guías de compra</h2>
      <a href="<?= e(url('blog')) ?>">Ver todas las guías</a>
    </div>
    <div class="stories">
      <a class="lead" href="<?= e(post_url($lead)) ?>">
        <?php if ($lead['cover_path']): ?><span class="lead__img"><img src="<?= e(upload_url($lead['cover_path'])) ?>" alt=""></span><?php endif; ?>
        <?= cat_label($lead) ?>
        <span class="lead__title"><?= e($lead['title']) ?></span>
        <?php if ($lead['excerpt']): ?><span class="lead__excerpt"><?= e($lead['excerpt']) ?></span><?php endif; ?>
        <span class="lead__foot"><?= reading_minutes($lead['body']) ?> min de lectura</span>
        <?php if (!$lead['cover_path']): ?><?= niche_icon($lead['niche_slug'] ?? '', 'lead__icon') ?><?php endif; ?>
      </a>
      <div>
        <?php foreach ($guides as $post): partial('mini-post', ['post' => $post, 'plain' => true]); endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($comparisons): ?>
<section class="sec sec--tint" aria-labelledby="h-vs">
  <div class="wrap">
    <div class="sec__head">
      <h2 id="h-vs">Comparativas</h2>
      <a href="<?= e(url('comparativas')) ?>">Ver todas las comparativas</a>
    </div>
    <div class="vs-grid">
      <?php foreach ($comparisons as $post): ?>
        <a class="vs" href="<?= e(post_url($post)) ?>">
          <?= niche_icon($post['niche_slug'] ?? '') ?>
          <span><?= cat_label($post) ?><span class="vs__title"><?= e($post['title']) ?></span></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($popular): ?>
<section class="sec" aria-labelledby="h-popular">
  <div class="wrap">
    <div class="sec__head"><h2 id="h-popular"><?= $deals ? 'Los que más se miran' : 'Productos que seguimos' ?></h2></div>
    <div class="grid"><?php foreach ($popular as $p): partial('pcard', ['p' => $p]); endforeach; ?></div>
  </div>
</section>
<?php endif; ?>

<?php if (fuel_enabled()): $ft = fuel_today(0); ?>
<section class="sec sec--tight" aria-labelledby="h-fuel">
  <div class="wrap">
    <a class="fuel-band" href="<?= e(url('gasolina')) ?>">
      <?= icon('gas-pump') ?>
      <div>
        <h2 id="h-fuel">Precio de la gasolina hoy</h2>
        <p><?php if ($ft['g95']['avg'] !== null): ?>Gasolina 95 a <?= e(fuel_money($ft['g95']['avg'])) ?> y diésel a <?= e(fuel_money($ft['diesel']['avg'])) ?> de media en España. <?php endif; ?>Encuentra la gasolinera más barata de tu municipio.</p>
      </div>
      <span class="btn btn--blue">Ver gasolineras</span>
    </a>
  </div>
</section>
<?php endif; ?>

<?php if ($reviews): ?>
<section class="sec sec--tight" aria-labelledby="h-reviews">
  <div class="wrap">
    <div class="sec__head">
      <h2 id="h-reviews">Reseñas</h2>
      <a href="<?= e(url('resenas')) ?>">Ver todas las reseñas</a>
    </div>
    <div class="acards"><?php foreach (array_slice($reviews, 0, 3) as $post): partial('acard', ['post' => $post]); endforeach; ?></div>
  </div>
</section>
<?php endif; ?>

<?php if ($telegram): ?>
<section class="sec sec--tight">
  <div class="wrap">
    <div class="channel">
      <div>
        <h2>Avisos en Telegram</h2>
        <p>Publicamos cada producto que toca su precio más bajo de los últimos seis meses.</p>
      </div>
      <a class="btn btn--blue" href="<?= e($telegram) ?>" rel="noopener">Unirme al canal</a>
    </div>
  </div>
</section>
<?php endif; ?>
