<?php /** Fila de oferta, estilo listado de chollos. @var array $p */
$tp = tag_price($p);
$off = deal_discount($p);
$when = $tp && $tp['store'] === 'amazon' ? $p['amazon_price_at'] : $p['updated_at'];
$buyUrl = null;
if ($tp) {
    $buyUrl = $tp['store'] === 'amazon' ? amazon_link($p) : ($p[$tp['store'] === 'other' ? 'other_url' : $tp['store'] . '_url'] ?? null);
}
?>
<article class="deal">
  <a href="<?= e(product_url($p)) ?>" tabindex="-1" aria-hidden="true"><?php partial('thumb', ['p' => $p]); ?></a>
  <div class="deal__main">
    <div class="deal__top">
      <?php if ($tp && (int) $p['at_low']): ?><span class="badge badge--low">Mínimo histórico</span><?php endif; ?>
      <?php if ($off): ?><span class="badge badge--off">−<?= e(pct($off, false)) ?> frente a su media</span><?php endif; ?>
      <?php if (!empty($p['niche_name'])): ?><span class="badge badge--plain"><?= e($p['niche_name']) ?></span><?php endif; ?>
      <span class="deal__time">Actualizado <?= e(time_ago($when)) ?></span>
    </div>
    <h3 class="deal__title"><a href="<?= e(product_url($p)) ?>"><?= e($p['name']) ?></a></h3>
    <div class="deal__price">
      <?php if ($tp): ?>
        <strong><?= e(money($tp['price'])) ?></strong>
        <span class="deal__store">en <?= e(store_label($p, $tp['store'])) ?></span>
        <?php if ($p['avg90'] && ($p['stats_store'] ?? '') === $tp['store']): ?><span class="deal__avg">Media 90 días: <?= e(money($p['avg90'])) ?></span><?php endif; ?>
      <?php else: ?>
        <span class="deal__store">Precio pendiente de comprobar</span>
      <?php endif; ?>
    </div>
  </div>
  <div class="deal__side">
    <?php if ($buyUrl): ?>
      <a class="btn btn--buy" href="<?= e($buyUrl) ?>" rel="sponsored nofollow noopener" target="_blank" data-out data-product="<?= (int) $p['id'] ?>" data-store="<?= e($tp['store']) ?>" data-from="deal">Ver oferta</a>
    <?php endif; ?>
    <a class="deal__hist" href="<?= e(product_url($p)) ?>">Historial de precios</a>
  </div>
</article>
