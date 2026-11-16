<?php /** Tarjeta de producto para cuadrículas. @var array $p */
$tp = tag_price($p);
$off = deal_discount($p);
$img = product_image($p);
?>
<a class="pcard" href="<?= e(product_url($p)) ?>">
  <?php if ($img): ?><span class="pcard__img"><img src="<?= e($img) ?>" alt="" loading="lazy"></span><?php endif; ?>
  <span class="pcard__title"><?= e($p['name']) ?></span>
  <?php if (!empty($p['brand'])): ?><span class="pcard__brand"><?= e($p['brand']) ?></span><?php endif; ?>
  <?php if ($tp): ?>
    <span class="pcard__price">
      <strong><?= e(money($tp['price'])) ?></strong>
      <?php if ((int) $p['at_low']): ?><span class="badge badge--low">Mínimo histórico</span>
      <?php elseif ($off): ?><span class="badge badge--off">−<?= e(pct($off, false)) ?></span><?php endif; ?>
    </span>
  <?php endif; ?>
</a>
