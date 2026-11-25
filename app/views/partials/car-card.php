<?php /** Tarjeta de accesorio de coche: nombre, para quién es y un dato. @var array $p */
$facts = product_facts($p);
$ideal = (string) ($facts['Ideal para'] ?? '');
$fact = null;
foreach ($facts as $label => $value) {
    if ($label !== 'Ideal para') {
        $fact = [$label, $value];
        break;
    }
}
?>
<a class="ccard" href="<?= e(product_url($p)) ?>">
  <span class="ccard__name"><?= e($p['name']) ?></span>
  <?php if ($ideal !== ''): ?><span class="ccard__why">Para <?= e(mb_strtolower(mb_substr($ideal, 0, 1)) . mb_substr($ideal, 1)) ?>.</span><?php endif; ?>
  <?php if ($fact): ?><span class="ccard__fact"><b><?= e($fact[0]) ?>:</b> <?= e($fact[1]) ?></span><?php endif; ?>
</a>
