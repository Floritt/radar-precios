<?php /** Fila de oferta. @var array $p */
$tp = tag_price($p);
$off = deal_discount($p);
$img = product_image($p);
$when = $tp && $tp['store'] === 'amazon' ? $p['amazon_price_at'] : $p['updated_at'];
$buyUrl = null;
if ($tp) {
    $buyUrl = $tp['store'] === 'amazon' ? amazon_link($p) : ($p[$tp['store'] === 'other' ? 'other_url' : $tp['store'] . '_url'] ?? null);
}
?>
<article class="deal<?= $img ? ' deal--img' : '' ?>">
  <?php if ($img): ?><a class="deal__img" href="<?= e(product_url($p)) ?>" tabindex="-1" aria-hidden="true"><img src="<?= e($img) ?>" alt="" loading="lazy"></a><?php endif; ?>
  <div>
    <h3 class="deal__title"><a href="<?= e(product_url($p)) ?>"><?= e($p['name']) ?></a></h3>
    <p class="deal__meta">
      <?php if ($tp && (int) $p['at_low']): ?><span class="badge badge--low">Mínimo histórico</span><?php endif; ?>
      <?php if ($off): ?><span class="badge badge--off">−<?= e(pct($off, false)) ?> frente a su media</span><?php endif; ?>
      <?= cat_label($p) ?>
      <span>Actualizado <?= e(time_ago($when)) ?></span>
    </p>
  </div>
  <div class="deal__price">
    <?php if ($tp): ?>
      <strong><?= e(money($tp['price'])) ?></strong>
      <span>en <?= e(store_label($p, $tp['store'])) ?></span>
    <?php else: ?>
      <span>Precio pendiente de comprobar</span>
    <?php endif; ?>
  </div>
  <div class="deal__side">
    <?php if ($buyUrl): ?>
      <a class="btn btn--buy" href="<?= e($buyUrl) ?>" rel="sponsored nofollow noopener" target="_blank" data-out data-product="<?= (int) $p['id'] ?>" data-store="<?= e($tp['store']) ?>" data-from="deal">Ver oferta</a>
    <?php endif; ?>
    <a class="deal__hist" href="<?= e(product_url($p)) ?>">Historial de precios</a>
  </div>
</article>
