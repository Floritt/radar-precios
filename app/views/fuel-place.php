<?php
/** @var string $kind @var string $placeName @var int $pid @var ?array $muni @var string $fuel @var array $today @var array $national @var ?array $verdict @var ?array $chart @var array $stations @var array $stats @var array $munis @var string $updated */
$base = $kind === 'muni' ? fuel_muni_url($muni) : fuel_province_url($pid);
$avg = $stats['avg'];
$natAvg = $national[$fuel]['avg'];
?>
<div class="page-head fuel-head">
  <div class="wrap">
    <nav class="crumbs" aria-label="Migas de pan">
      <a href="<?= e(url('')) ?>">Inicio</a> / <a href="<?= e(url('gasolina')) ?>">Gasolina</a>
      <?php if ($kind === 'muni'): ?> / <a href="<?= e(fuel_province_url($pid)) ?>"><?= e(FUEL_PROVINCES[$pid]) ?></a><?php endif; ?>
    </nav>
    <h1>Gasolineras más baratas en <?= e($placeName) ?></h1>
    <p>
      <?= $stats['n'] ?> gasolineras con <?= e(mb_strtolower(fuel_name($fuel))) ?><?php if ($avg !== null): ?>. Media de hoy: <strong><?= e(fuel_money($avg)) ?></strong><?php if ($natAvg !== null): ?>, <?= e(fuel_cents($avg - $natAvg)) ?> frente a la media de España<?php endif; ?><?php endif; ?>.
      <?php if ($updated): ?>Actualizado el <?= e(date_short($updated)) ?>.<?php endif; ?>
    </p>
    <?php partial('fuel-tools', ['fuel' => $fuel]); ?>
  </div>
</div>

<div class="wrap page">
  <?php if ($verdict): ?>
  <div class="verdict verdict--<?= e($verdict[0]) ?>"><span class="verdict__dot"></span><div><strong><?= e($verdict[1]) ?><?= $kind === 'muni' ? ' en ' . e(FUEL_PROVINCES[$pid]) : '' ?></strong><?= e($verdict[2]) ?></div></div>
  <?php endif; ?>

  <section class="block">
    <h2><?= $kind === 'muni' ? 'De la más barata a la más cara' : 'Las ' . count($stations) . ' más baratas de la provincia' ?></h2>
    <?php if ($stations): ?>
      <?php partial('fuel-table', ['stations' => $stations, 'fuel' => $fuel, 'avg' => $avg, 'showMuni' => $kind === 'province', 'base' => $base]); ?>
    <?php else: ?>
      <p class="empty">Ninguna gasolinera de aquí tiene <?= e(mb_strtolower(fuel_name($fuel))) ?> ahora mismo.</p>
    <?php endif; ?>
  </section>

  <?php if ($chart && count($chart['labels']) > 1): ?>
  <section class="chart-card" aria-labelledby="h-fuel-chart">
    <div class="chart-head"><h2 id="h-fuel-chart">Evolución del precio medio en <?= e($placeName) ?></h2></div>
    <div class="chart-box"><canvas id="fuel-chart" role="img" aria-label="Evolución del precio medio en <?= e($placeName) ?>"></canvas></div>
    <script type="application/json" id="fuel-data"><?= json_encode($chart, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
  </section>
  <?php endif; ?>

  <?php if ($munis): ?>
  <section class="block">
    <h2>Municipios de <?= e($placeName) ?></h2>
    <ul class="fuel-munis">
      <?php foreach ($munis as $m): ?><li><a href="<?= e(fuel_muni_url($m) . ($fuel === 'g95' ? '' : '?c=' . $fuel)) ?>"><?= e($m['name']) ?></a> <span><?= (int) $m['stations'] ?></span></li><?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>

  <?php if ($kind === 'muni'): ?>
    <p><a href="<?= e(fuel_province_url($pid)) ?>">Ver las más baratas de toda la provincia de <?= e(FUEL_PROVINCES[$pid]) ?></a></p>
  <?php endif; ?>
  <p class="fineprint">Fuente: Ministerio para la Transición Ecológica y el Reto Demográfico. Solo gasolineras de venta al público. El precio válido es el del surtidor.</p>
</div>
