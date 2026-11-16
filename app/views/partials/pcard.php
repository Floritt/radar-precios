<?php /** Tarjeta de producto para cuadrículas. @var array $p */
$tp = tag_price($p);
$off = deal_discount($p);
$n = store_count($p);
?>
<a class="pcard" href="<?= e(product_url($p)) ?>">
  <?php partial('thumb', ['p' => $p]); ?>
  <?php if ($tp && (int) $p['at_low']): ?><span class="pcard__badge badge badge--low">Mínimo histórico</span>
  <?php elseif ($off): ?><span class="pcard__badge badge badge--off">−<?= e(pct($off, false)) ?></span><?php endif; ?>
  <span class="pcard__title"><?= e($p['name']) ?></span>
  <span class="pcard__price">
    <?php if ($tp): ?>desde<strong><?= e(money($tp['price'])) ?></strong><?php else: ?><span class="pcard__noprice">Ver precio en la tienda</span><?php endif; ?>
  </span>
  <span class="pcard__stores"><?= $n === 1 ? 'En 1 tienda' : 'Comparado en ' . $n . ' tiendas' ?></span>
</a>
