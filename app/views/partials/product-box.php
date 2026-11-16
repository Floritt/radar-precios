<?php
/** Producto insertado en un artículo con [producto:slug]. @var array $p */
$offers = product_offers($p);
$best = best_offer($offers);
$img = product_image($p);
$facts = product_facts($p);
$ideal = $facts['Ideal para'] ?? null;
unset($facts['Ideal para']);
$facts = array_slice($facts, 0, 3, true);
$go = $best ?: ($offers ? reset($offers) : null);
?>
<aside class="pbox<?= $img ? ' pbox--img' : '' ?>">
  <?php if ($img): ?><a class="pbox__img" href="<?= e(product_url($p)) ?>" tabindex="-1" aria-hidden="true"><img src="<?= e($img) ?>" alt="" loading="lazy"></a><?php endif; ?>
  <div class="pbox__body">
    <p class="pbox__name"><a href="<?= e(product_url($p)) ?>"><?= e($p['name']) ?></a><?php if ($p['brand']): ?> <span class="pbox__brand"><?= e($p['brand']) ?></span><?php endif; ?></p>
    <?php if ($ideal): ?><p class="pbox__why"><strong>Ideal para:</strong> <?= e($ideal) ?></p><?php endif; ?>
    <?php if ($facts): ?><ul class="pbox__facts"><?php foreach ($facts as $k => $v): ?><li><span><?= e($k) ?></span> <?= e($v) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <?php if ($best): ?>
      <p class="pbox__price"><strong><?= e(money($best['price'])) ?></strong> <span class="muted">en <?= e($best['name']) ?></span>
        <?php if ((int) $p['at_low']): ?><span class="badge badge--low">Mínimo histórico</span><?php endif; ?></p>
    <?php endif; ?>
  </div>
  <div class="pbox__actions">
    <?php if ($go): ?>
      <a class="btn btn--buy" href="<?= e($go['url']) ?>" rel="sponsored nofollow noopener" target="_blank" data-out data-product="<?= (int) $p['id'] ?>" data-store="<?= e($go['store']) ?>" data-from="post"><?= $best ? 'Ver oferta en ' : 'Ver precio en ' ?><?= e($go['name']) ?></a>
    <?php endif; ?>
    <a class="pbox__more" href="<?= e(product_url($p)) ?>"><?= count($offers) > 1 ? 'Comparar ' . count($offers) . ' tiendas' : 'Más detalles' ?></a>
  </div>
  <?php if ($best && !$best['manual']): ?><p class="fineprint">Precio de Amazon a <?= e(date_short($best['at'])) ?>; puede haber cambiado. Enlace de afiliado.</p><?php endif; ?>
</aside>
