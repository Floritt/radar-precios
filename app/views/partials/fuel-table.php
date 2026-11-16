<?php
/**
 * Gasolineras con una columna por carburante. Se ordenan por el carburante elegido, y la cabecera de cada
 * columna es el enlace para ordenar por ella.
 * @var array $stations @var string $fuel @var ?float $avg @var bool $showMuni @var bool $showKm @var string $base
 */
$showMuni = $showMuni ?? false;
$showKm = $showKm ?? false;
$avg = $avg ?? null;
$base = $base ?? '';
$sortUrl = fn (string $f) => $base . ($f === 'g95' ? '' : (str_contains($base, '?') ? '&' : '?') . 'c=' . $f);
$short = ['g95' => 'Gasolina 95', 'diesel' => 'Diésel', 'g98' => 'Gasolina 98'];
?>
<div class="fuel-list" role="table" aria-label="Gasolineras ordenadas por precio de <?= e(mb_strtolower(fuel_name($fuel))) ?>">
  <div class="fuel-row fuel-row--head" role="row">
    <span role="columnheader" class="fuel-row__pos">#</span>
    <span role="columnheader">Gasolinera</span>
    <?php foreach (FUELS_MAIN as $f): ?>
      <span role="columnheader" class="fuel-row__col"><a href="<?= e($sortUrl($f)) ?>"<?= $f === $fuel ? ' aria-current="true"' : '' ?> title="Ordenar por <?= e(mb_strtolower(fuel_name($f))) ?>"><?= e($short[$f]) ?><?= $f === $fuel ? ' ↑' : '' ?></a></span>
    <?php endforeach; ?>
  </div>
  <?php foreach ($stations as $i => $s): ?>
  <div class="fuel-row<?= $i === 0 ? ' is-best' : '' ?>" role="row">
    <span class="fuel-row__pos" role="cell"><?= $i + 1 ?></span>
    <div class="fuel-row__info" role="cell">
      <a class="fuel-row__name" href="<?= e(fuel_station_url($s)) ?>"><?= e($s['name']) ?></a>
      <span class="fuel-row__addr"><?= e($s['address']) ?><?= $showMuni && !empty($s['muni_name']) ? ', ' . e($s['muni_name']) : '' ?><?= $showKm ? ' · ' . e(number_format((float) $s['km'], 1, ',', '.')) . ' km' : '' ?></span>
    </div>
    <?php foreach (FUELS_MAIN as $f): $v = $s[$f]; ?>
      <span class="fuel-row__col<?= $f === $fuel ? ' is-sorted' : '' ?>" role="cell">
        <?php if ($v === null): ?><span class="fuel-none" aria-label="Sin <?= e(mb_strtolower(fuel_name($f))) ?>">–</span>
        <?php else: ?><strong><?= e(number_format((float) $v, 3, ',', '.')) ?></strong>
          <?php if ($f === $fuel && $avg !== null && abs((float) $v - $avg) >= 0.001): $d = (float) $v - $avg; ?><em class="<?= $d < 0 ? 'is-down' : 'is-up' ?>"><?= e(fuel_cents($d)) ?></em><?php endif; ?>
        <?php endif; ?>
      </span>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>
</div>
<p class="fuel-legend">Precios en €/l. Pulsa el nombre de un carburante para ordenar por él. Bajo el precio, la diferencia con la media de la zona.</p>
