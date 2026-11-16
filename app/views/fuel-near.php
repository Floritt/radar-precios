<?php /** @var array $stations @var string $fuel @var int $radius @var float $lat @var float $lng @var bool $valid */
$base = url('gasolina/cerca?lat=' . rawurlencode((string) $lat) . '&lng=' . rawurlencode((string) $lng) . '&km=' . $radius);
?>
<div class="page-head fuel-head">
  <div class="wrap">
    <nav class="crumbs" aria-label="Migas de pan"><a href="<?= e(url('')) ?>">Inicio</a> / <a href="<?= e(url('gasolina')) ?>">Gasolina</a></nav>
    <h1>Gasolineras baratas cerca de ti</h1>
    <p>A menos de <?= $radius ?> km de tu posición. No guardamos tu ubicación: solo se usa para esta búsqueda.</p>
    <?php partial('fuel-tools', ['fuel' => $fuel]); ?>
  </div>
</div>
<div class="wrap page">
  <?php if ($valid): ?>
  <div class="fuel-sec-head">
    <nav class="fuel-tabs" aria-label="Distancia">
      <?php foreach ([5, 10, 25] as $km): ?><a href="<?= e(url('gasolina/cerca?lat=' . rawurlencode((string) $lat) . '&lng=' . rawurlencode((string) $lng) . '&km=' . $km . '&c=' . $fuel)) ?>"<?= $km === $radius ? ' aria-current="true"' : '' ?>><?= $km ?> km</a><?php endforeach; ?>
    </nav>
  </div>
  <?php endif; ?>
  <?php if ($stations):
    $vals = array_map(fn ($s) => (float) $s[$fuel], $stations);
    partial('fuel-table', ['stations' => $stations, 'fuel' => $fuel, 'avg' => array_sum($vals) / count($vals), 'showMuni' => true, 'showKm' => true, 'base' => $base]);
  else: ?>
    <p class="empty"><?= $valid ? 'No hay gasolineras con ' . e(mb_strtolower(fuel_name($fuel))) . ' a esa distancia. Prueba con un radio mayor.' : 'Pulsa «Cerca de mí» y permite el acceso a tu ubicación, o busca tu municipio.' ?></p>
  <?php endif; ?>
</div>
