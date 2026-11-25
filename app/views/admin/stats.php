<?php
/**
 * @var int $days @var array $totals @var array $series @var array $hours @var array $weekdays @var array $countries @var array $areas
 * @var array $sources @var array $devices @var array $browsers @var array $oses @var array $langs @var array $kinds
 * @var array $fuelProv @var array $fuelMuni @var array $fuelStation @var array $fuelTerms @var array $webTerms
 * @var array $alertsProduct @var array $alertsFuel @var array $alertsZone @var array $signups @var array $products @var array $posts @var array $links
 */
$pct = fn (int $part, int $whole): string => $whole > 0 ? str_replace('.', ',', (string) round($part * 100 / $whole, 1)) . ' %' : '';
$max = max(1, ...array_map(fn ($d) => max($d['views'], $d['clicks']), array_values($series)));
$table = function (array $items, string $first) use ($pct): void {
    $seen = array_values(array_filter($items, fn ($it) => $it['views'] || $it['clicks']));
    $zero = array_values(array_filter($items, fn ($it) => !$it['views'] && !$it['clicks']));
    if (!$seen) { echo '<p class="muted">Todavía sin visitas en este periodo.</p>'; }
    if ($seen): ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th><?= e($first) ?></th><th class="r">Visitas</th><th class="r">Clics a Amazon</th><th class="r">Pulsan</th></tr></thead>
    <tbody>
    <?php foreach ($seen as $it): ?>
      <tr<?= $it['views'] || $it['clicks'] ? '' : ' class="is-zero"' ?>>
        <td><a href="<?= e(url($it['url'])) ?>"><?= e($it['name']) ?></a></td>
        <td class="r"><?= $it['views'] ?></td><td class="r"><?= $it['clicks'] ?></td><td class="r"><?= e($pct($it['clicks'], $it['views'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
    <?php endif;
    if ($zero): ?>
  <details class="zero-list"><summary><?= count($zero) ?> sin visitas en este periodo</summary>
    <p><?php foreach ($zero as $i => $it): ?><?= $i ? ', ' : '' ?><a href="<?= e(url($it['url'])) ?>"><?= e($it['name']) ?></a><?php endforeach; ?></p>
  </details>
    <?php endif;
};
/** Lista con barra proporcional: etiqueta, número y porcentaje del total. */
$bars = function (array $items, string $empty = 'Todavía no hay datos.'): void {
    if (!$items) { echo '<p class="muted">' . e($empty) . '</p>'; return; }
    $total = array_sum(array_column($items, 'n')) ?: 1;
    $top = max(array_column($items, 'n')) ?: 1; ?>
  <ul class="barlist">
    <?php foreach ($items as $it): ?>
      <li><span class="barlist__label"><?= e($it['label']) ?></span><span class="barlist__n"><?= (int) $it['n'] ?> <small><?= e(str_replace('.', ',', (string) round($it['n'] * 100 / $total))) ?> %</small></span><i style="width:<?= round($it['n'] * 100 / $top, 1) ?>%"></i></li>
    <?php endforeach; ?>
  </ul>
<?php };
$hourMax = max(1, ...$hours);
$peak = array_search(max($hours), $hours, true);
$wdNames = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];
$wdMax = max(1, ...array_values($weekdays));
$spain = 0;
foreach ($countries as $c) { if ($c['label'] === 'España') { $spain = $c['n']; } }
$countryTotal = array_sum(array_column($countries, 'n'));
$statusNames = ['pending' => 'Sin confirmar', 'active' => 'Activo', 'sent' => 'Enviado'];
?>
<h1>Estadísticas</h1>

<p class="seg-links">
  <?php foreach ([7 => '7 días', 30 => '30 días', 90 => '90 días'] as $d => $label): ?>
    <a href="<?= e(url('admin/estadisticas?dias=' . $d)) ?>"<?= $d === $days ? ' aria-current="true"' : '' ?>><?= $label ?></a>
  <?php endforeach; ?>
</p>

<div class="kpis">
  <div class="kpi"><b><?= $totals['views'] ?></b><span>páginas vistas</span></div>
  <div class="kpi"><b><?= $totals['clicks'] ?></b><span>clics a Amazon</span></div>
  <div class="kpi"><b><?= e($pct($totals['clicks'], $totals['views']) ?: '0 %') ?></b><span>de las visitas acaban en clic</span></div>
  <div class="kpi"><b><?= $totals['fuel'] ?></b><span>páginas vistas de Gasolina</span></div>
  <div class="kpi"><b><?= $alertsProduct['new'] + $alertsFuel['new'] + $alertsZone['new'] ?></b><span>avisos nuevos</span></div>
  <div class="kpi"><b><?= $countryTotal ? e($pct($spain, $countryTotal)) : '–' ?></b><span>de las visitas desde España</span></div>
</div>

<section class="card">
  <h2>Día a día</h2>
  <div class="daybars" role="img" aria-label="Visitas y clics por día en los últimos <?= $days ?> días">
    <?php foreach ($series as $day => $d): ?>
      <div class="daybars__col" title="<?= e(date('d/m', strtotime($day))) ?>: <?= $d['views'] ?> visitas, <?= $d['clicks'] ?> clics">
        <i class="v" style="height:<?= round($d['views'] * 100 / $max, 1) ?>%"></i><i class="c" style="height:<?= round($d['clicks'] * 100 / $max, 1) ?>%"></i>
      </div>
    <?php endforeach; ?>
  </div>
  <p class="muted daybars__key"><span class="k k--v"></span> Visitas <span class="k k--c"></span> Clics a Amazon. Del <?= e(date('d/m', strtotime((string) array_key_first($series)))) ?> al <?= e(date('d/m', strtotime((string) array_key_last($series)))) ?>. Pasa el dedo o el ratón por una barra para ver el día.</p>
</section>

<div class="cols">
  <section class="card">
    <h2>Por hora del día</h2>
    <div class="hourbars" role="img" aria-label="Visitas por hora del día">
      <?php foreach ($hours as $h => $n): ?><div title="<?= sprintf('%02d:00', $h) ?>: <?= $n ?> visitas"><i style="height:<?= round($n * 100 / $hourMax, 1) ?>%"></i><span><?= $h % 3 === 0 ? $h : '' ?></span></div><?php endforeach; ?>
    </div>
    <p class="muted"><?= array_sum($hours) ? 'La hora con más visitas es de las ' . sprintf('%02d:00 a las %02d:00', $peak, ($peak + 1) % 24) . ' (hora de España).' : 'Todavía no hay datos.' ?></p>
  </section>
  <section class="card">
    <h2>Por día de la semana</h2>
    <div class="hourbars hourbars--week" role="img" aria-label="Visitas por día de la semana">
      <?php foreach ($weekdays as $wd => $n): ?><div title="<?= $wdNames[$wd] ?>: <?= $n ?> visitas"><i style="height:<?= round($n * 100 / $wdMax, 1) ?>%"></i><span><?= $wdNames[$wd] ?></span></div><?php endforeach; ?>
    </div>
  </section>
</div>

<div class="cols">
  <section class="card"><h2>Países</h2><?php $bars($countries); ?>
    <?php if ($areas): ?><h3 class="card__sub">Dentro de España</h3><?php $bars($areas); endif; ?>
    <p class="muted">Según la zona horaria del navegador: es aproximado y no usa la IP.</p></section>
  <section class="card"><h2>De dónde llegan</h2><?php $bars($sources); ?>
    <p class="muted">«Directo» incluye quien escribe la dirección, usa un marcador o llega desde una app como WhatsApp, que no dice de dónde viene.</p></section>
</div>

<div class="cols cols--4">
  <section class="card"><h2>Dispositivo</h2><?php $bars($devices); ?></section>
  <section class="card"><h2>Sistema</h2><?php $bars($oses); ?></section>
  <section class="card"><h2>Navegador</h2><?php $bars($browsers); ?></section>
  <section class="card"><h2>Idioma</h2><?php $bars($langs); ?></section>
</div>

<section class="card"><h2>Secciones más vistas</h2><?php $bars($kinds); ?></section>

<section class="card">
  <h2>Avisos de precio</h2>
  <div class="table-wrap"><table class="table">
    <thead><tr><th></th><th class="r">Nuevos</th><th class="r">Confirmados</th><th class="r">Enviados</th><th class="r">Activos ahora</th><th class="r">Sin confirmar</th></tr></thead>
    <tbody>
      <?php foreach (['Productos' => $alertsProduct, 'Gasolineras' => $alertsFuel, 'Gasolina por zona' => $alertsZone] as $label => $a): ?>
        <tr><td><?= $label ?></td><td class="r"><?= $a['new'] ?></td><td class="r"><?= $a['confirmed'] ?></td><td class="r"><?= $a['sent'] ?></td><td class="r"><?= $a['active'] ?></td><td class="r"><?= $a['pending'] ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <h3 class="card__sub">Últimas altas</h3>
  <?php if ($signups): ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Fecha</th><th>Tipo</th><th>Qué</th><th class="r">Precio</th><th>Correo</th><th>Estado</th></tr></thead>
      <tbody>
      <?php foreach ($signups as $s): ?>
        <tr><td><?= e(date_short($s['at'])) ?></td><td><?= e($s['type']) ?></td>
          <td><?= $s['url'] ? '<a href="' . e(url($s['url'])) . '">' . e($s['what']) . '</a>' : e($s['what']) ?></td>
          <td class="r"><?= e($s['target']) ?></td><td><?= e($s['who']) ?></td><td><?= e($statusNames[$s['status']] ?? $s['status']) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <p class="muted">Los correos salen tapados a propósito: no hace falta verlos enteros para seguir los avisos.</p>
  <?php else: ?><p class="muted">Todavía nadie se ha apuntado a un aviso.</p><?php endif; ?>
</section>

<div class="cols">
  <section class="card"><h2>Gasolina: provincias más vistas</h2><?php $bars($fuelProv); ?></section>
  <section class="card"><h2>Gasolina: municipios más vistos</h2><?php $bars($fuelMuni); ?></section>
</div>
<div class="cols">
  <section class="card"><h2>Gasolina: gasolineras más vistas</h2><?php $bars($fuelStation); ?></section>
  <section class="card"><h2>Gasolina: qué buscan</h2><?php $bars($fuelTerms, 'Todavía nadie ha buscado un municipio.'); ?></section>
</div>

<section class="card"><h2>Búsquedas en la web</h2><?php $bars($webTerms, 'Todavía nadie ha usado el buscador.'); ?>
  <p class="muted">Buenas pistas para escribir la próxima guía o añadir productos.</p></section>

<section class="card">
  <h2>Productos</h2>
  <?php $table($products, 'Producto'); ?>
</section>

<section class="card">
  <h2>Guías, comparativas y reseñas</h2>
  <?php $table($posts, 'Artículo'); ?>
</section>

<section class="card">
  <h2>Botones más pulsados</h2>
  <?php if ($links): ?>
    <div class="table-wrap"><table class="table"><thead><tr><th>Búsqueda o producto del botón</th><th class="r">Clics</th></tr></thead><tbody>
    <?php foreach ($links as $l): ?><tr><td><?= e($l['label']) ?></td><td class="r"><?= (int) $l['c'] ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  <?php else: ?><p class="muted">Todavía no hay clics en los botones de los artículos.</p><?php endif; ?>
</section>

<p class="muted">Se cuentan las páginas vistas por personas, sin robots y sin tus propias visitas mientras tengas el panel abierto. No se usan cookies ni se guarda la IP ni ningún dato que identifique a nadie, así que no se pueden contar visitantes únicos. Las ventas y comisiones solo se ven en tu panel de Amazon Afiliados.</p>
