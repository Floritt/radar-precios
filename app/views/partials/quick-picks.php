<?php
/** Resumen de los productos recomendados en un artículo, con su botón. @var array $picks */
?>
<section class="picks" aria-labelledby="h-picks">
  <h2 id="h-picks">Nuestra elección rápida</h2>
  <ol class="picks__list">
    <?php foreach ($picks as $p): $offers = product_offers($p); $go = best_offer($offers) ?: ($offers ? reset($offers) : null); $f = product_facts($p); $why = $f['Ideal para'] ?? (product_intro($p, 90) ?: ''); ?>
    <li class="picks__item">
      <div>
        <a class="picks__name" href="<?= e(product_url($p)) ?>"><?= e($p['name']) ?></a>
        <?php if ($why): ?><span class="picks__why"><?= e(mb_strtoupper(mb_substr($why, 0, 1)) . mb_substr($why, 1)) ?></span><?php endif; ?>
      </div>
      <?php if ($go): ?><a class="btn btn--buy btn--sm" href="<?= e($go['url']) ?>" rel="sponsored nofollow noopener" target="_blank" data-out data-product="<?= (int) $p['id'] ?>" data-store="<?= e($go['store']) ?>" data-from="picks">Ver precio</a><?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ol>
</section>
