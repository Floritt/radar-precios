<?php
/** Aviso de campaña de temporada (Black Friday). Solo sale mientras season_campaign() devuelve algo. */
$c = season_campaign();
if (!$c) { return; }
[$title, $text, $query, $guide] = $c;
?>
<div class="season" role="region" aria-label="<?= e($title) ?>">
  <div class="wrap season__row">
    <p><strong><?= e($title) ?>.</strong> <?= e($text) ?> <a href="<?= e(url('blog/' . $guide)) ?>">Leer la guía</a></p>
    <a class="btn btn--buy btn--sm" href="<?= e(amazon_search_url($query)) ?>" rel="sponsored nofollow noopener" target="_blank" data-out data-product="0" data-store="amazon" data-from="season" data-label="<?= e($query) ?>">Ver ofertas en Amazon</a>
  </div>
</div>
