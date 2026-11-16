<?php /** @var string $q @var array $results */ ?>
<div class="page-head fuel-head">
  <div class="wrap">
    <nav class="crumbs" aria-label="Migas de pan"><a href="<?= e(url('')) ?>">Inicio</a> / <a href="<?= e(url('gasolina')) ?>">Gasolina</a></nav>
    <h1>Buscar gasolineras</h1>
    <?php partial('fuel-tools', ['fuel' => 'g95']); ?>
  </div>
</div>
<div class="wrap page">
  <?php if ($results): ?>
    <h2>Municipios para «<?= e($q) ?>»</h2>
    <ul class="fuel-munis">
      <?php foreach ($results as $m): ?><li><a href="<?= e(fuel_muni_url($m)) ?>"><?= e($m['name']) ?></a> <span><?= e(FUEL_PROVINCES[(int) $m['province_id']] ?? '') ?></span></li><?php endforeach; ?>
    </ul>
  <?php elseif ($q !== ''): ?>
    <p class="empty">No encontramos ningún municipio con gasolineras para «<?= e($q) ?>». Prueba con otro nombre o con el código postal.</p>
  <?php endif; ?>
</div>
