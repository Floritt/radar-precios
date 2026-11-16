<?php
/** Producto insertado en un artículo con [producto:slug]. @var array $p */
$offers = product_offers($p);
$best = best_offer($offers);
$img = product_image($p);
?>
<aside class="pbox<?= $img ? ' pbox--img' : '' ?>">
  <?php if ($img): ?><a class="pbox__img" href="<?= e(product_url($p)) ?>" tabindex="-1" aria-hidden="true"><img src="<?= e($img) ?>" alt="" loading="lazy"></a><?php endif; ?>
  <div>
    <p class="pbox__name"><a href="<?= e(product_url($p)) ?>"><?= e($p['name']) ?></a></p>
    <?php if ($best): ?>
      <p class="pbox__price"><strong><?= e(money($best['price'])) ?></strong> <span class="muted">en <?= e($best['name']) ?></span>
        <?php if ((int) $p['at_low']): ?><span class="badge badge--low">Mínimo histórico</span><?php endif; ?></p>
    <?php endif; ?>
  </div>
  <div class="pbox__actions">
    <?php if ($best): ?>
      <a class="btn btn--buy btn--sm" href="<?= e($best['url']) ?>" rel="sponsored nofollow noopener" target="_blank" data-out data-product="<?= (int) $p['id'] ?>" data-store="<?= e($best['store']) ?>" data-from="post">Ver oferta</a>
    <?php elseif ($offers): $first = reset($offers); ?>
      <a class="btn btn--buy btn--sm" href="<?= e($first['url']) ?>" rel="sponsored nofollow noopener" target="_blank" data-out data-product="<?= (int) $p['id'] ?>" data-store="<?= e($first['store']) ?>" data-from="post">Ver en <?= e($first['name']) ?></a>
    <?php endif; ?>
    <a class="btn btn--sm" href="<?= e(product_url($p)) ?>"><?= count($offers) > 1 ? 'Comparar ' . count($offers) . ' tiendas' : 'Ver ficha' ?></a>
  </div>
  <?php if ($best && !$best['manual']): ?><p class="fineprint">Precio de Amazon a <?= e(date_short($best['at'])) ?>; puede haber cambiado. Enlace de afiliado.</p><?php endif; ?>
</aside>
