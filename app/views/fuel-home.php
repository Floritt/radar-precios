<?php
/** @var string $fuel @var array $today @var ?array $verdict @var array $chart @var array $provinces @var array $cheapest @var string $updated */
?>
<div class="page-head fuel-head">
  <div class="wrap">
    <nav class="crumbs" aria-label="Migas de pan"><a href="<?= e(url('')) ?>">Inicio</a> / Gasolina</nav>
    <div class="page-head__icon">
      <?= icon('gas-pump') ?>
      <div>
        <h1>Precio de la gasolina hoy</h1>
        <p>Las gasolineras más baratas de cada municipio y si es buen momento para llenar el depósito. <?php if ($updated): ?>Actualizado el <?= e(date_short($updated)) ?>.<?php endif; ?></p>
      </div>
    </div>
    <?php partial('fuel-tools', ['fuel' => $fuel]); ?>
  </div>
</div>

<div class="wrap page">
  <section class="fuel-now" aria-label="Precio medio en España hoy">
    <?php foreach (FUELS_MAIN as $f): $t = $today[$f]; if ($t['avg'] === null) continue; ?>
      <a class="fuel-now__card<?= $f === $fuel ? ' is-current' : '' ?>" href="<?= e(url('gasolina' . ($f === 'g95' ? '' : '?c=' . $f))) ?>">
        <span><?= e(fuel_name($f)) ?>, media en España</span>
        <strong><?= e(fuel_money($t['avg'])) ?></strong>
        <?php if ($t['diff7'] !== null): ?><em class="<?= $t['diff7'] < 0 ? 'is-down' : ($t['diff7'] > 0 ? 'is-up' : '') ?>"><?= e(fuel_cents($t['diff7'])) ?> en 7 días</em><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </section>

  <?php if ($verdict): ?>
  <div class="verdict verdict--<?= e($verdict[0]) ?>"><span class="verdict__dot"></span><div><strong><?= e($verdict[1]) ?></strong><?= e($verdict[2]) ?></div></div>
  <?php endif; ?>

  <?php if (count($chart['labels']) > 1): ?>
  <section class="chart-card" aria-labelledby="h-fuel-chart">
    <div class="chart-head"><h2 id="h-fuel-chart">Evolución del precio medio en España</h2></div>
    <div class="chart-box"><canvas id="fuel-chart" role="img" aria-label="Evolución del precio medio de la gasolina y el diésel en España"></canvas></div>
    <script type="application/json" id="fuel-data"><?= json_encode($chart, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
  </section>
  <?php endif; ?>

  <section class="block">
    <h2>Las gasolineras más baratas de España</h2>
    <?php partial('fuel-table', ['stations' => $cheapest, 'fuel' => $fuel, 'avg' => $today[$fuel]['avg'], 'showMuni' => true, 'base' => url('gasolina')]); ?>
  </section>

  <section class="block">
    <h2>Precio medio por provincia</h2>
    <p class="fuel-note"><?= e(fuel_name($fuel)) ?>, de la provincia más barata a la más cara.</p>
    <div class="table-scroll"><table class="fuel-prov">
      <thead><tr><th>Provincia</th><th class="r">Media</th><th class="r">La más barata</th><th class="r">Gasolineras</th></tr></thead>
      <tbody>
      <?php foreach ($provinces as $p): ?>
        <tr><td><a href="<?= e(fuel_province_url((int) $p['province_id']) . ($fuel === 'g95' ? '' : '?c=' . $fuel)) ?>"><?= e($p['name']) ?></a></td>
          <td class="r"><?= e(fuel_money($p['avg'], false)) ?></td><td class="r"><?= e(fuel_money($p['min'], false)) ?></td><td class="r"><?= (int) $p['stations'] ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </section>

  <section class="block fuel-faq">
    <h2>Preguntas frecuentes</h2>
    <h3>¿De dónde salen estos precios?</h3>
    <p>Del Geoportal de Gasolineras del Ministerio para la Transición Ecológica y el Reto Demográfico. Las gasolineras están obligadas a comunicar sus precios, y nosotros los actualizamos cada hora. Solo mostramos las de venta al público.</p>
    <h3>¿Cuándo es más barato repostar?</h3>
    <p>Depende de cada gasolinera. Por eso guardamos el precio de cada una cada día: en la ficha de cualquier gasolinera ves cómo ha cambiado y si hoy está por encima o por debajo de lo normal.</p>
    <h3>¿Por qué el precio del surtidor no coincide?</h3>
    <p>Las gasolineras pueden cambiar el precio varias veces al día. El válido es siempre el que marca el surtidor.</p>
  </section>
  <p class="fineprint">Fuente: Ministerio para la Transición Ecológica y el Reto Demográfico, datos abiertos de precios de carburantes. Los precios pueden haber cambiado desde la última actualización.</p>
</div>
