<?php
/** Tabla de gasolineras de más barata a más cara. @var array $stations @var string $fuel @var ?float $avg @var bool $showMuni @var bool $showKm */
$showMuni = $showMuni ?? false;
$showKm = $showKm ?? false;
$avg = $avg ?? null;
?>
<ol class="fuel-list">
  <?php foreach ($stations as $i => $s): $price = (float) $s[$fuel]; $diff = $avg !== null ? $price - $avg : null; ?>
  <li class="fuel-row<?= $i === 0 ? ' is-best' : '' ?>">
    <span class="fuel-row__pos"><?= $i + 1 ?></span>
    <div class="fuel-row__info">
      <a class="fuel-row__name" href="<?= e(fuel_station_url($s)) ?>"><?= e($s['name']) ?></a>
      <span class="fuel-row__addr"><?= e($s['address']) ?><?= $showMuni && !empty($s['muni_name']) ? ', ' . e($s['muni_name']) : '' ?><?= $showKm ? ' · ' . e(number_format((float) $s['km'], 1, ',', '.')) . ' km' : '' ?></span>
    </div>
    <div class="fuel-row__price">
      <strong><?= e(fuel_money($price, false)) ?></strong>
      <?php if ($diff !== null && abs($diff) >= 0.001): ?><span class="<?= $diff < 0 ? 'is-down' : 'is-up' ?>"><?= e(fuel_cents($diff)) ?> vs media</span><?php endif; ?>
    </div>
  </li>
  <?php endforeach; ?>
</ol>
