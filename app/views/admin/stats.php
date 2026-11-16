<?php
/** @var int $days @var array $totals @var array $series @var array $products @var array $posts @var array $links */
$pct = fn (int $clicks, int $views): string => $views > 0 ? str_replace('.', ',', (string) round($clicks * 100 / $views, 1)) . ' %' : '';
$max = max(1, ...array_map(fn ($d) => max($d['views'], $d['clicks']), array_values($series)));
$table = function (array $items, string $first) use ($pct): void { ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th><?= e($first) ?></th><th class="r">Visitas</th><th class="r">Clics a Amazon</th><th class="r">Pulsan</th></tr></thead>
    <tbody>
    <?php foreach ($items as $it): ?>
      <tr<?= $it['views'] || $it['clicks'] ? '' : ' class="is-zero"' ?>>
        <td><a href="<?= e(url($it['url'])) ?>"><?= e($it['name']) ?></a></td>
        <td class="r"><?= $it['views'] ?></td><td class="r"><?= $it['clicks'] ?></td><td class="r"><?= e($pct($it['clicks'], $it['views'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
<?php }; ?>
<h1>Estadísticas</h1>

<p class="seg-links">
  <?php foreach ([7 => '7 días', 30 => '30 días', 90 => '90 días'] as $d => $label): ?>
    <a href="<?= e(url('admin/estadisticas?dias=' . $d)) ?>"<?= $d === $days ? ' aria-current="true"' : '' ?>><?= $label ?></a>
  <?php endforeach; ?>
</p>

<div class="kpis">
  <div class="kpi"><b><?= $totals['views'] ?></b><span>visitas</span></div>
  <div class="kpi"><b><?= $totals['clicks'] ?></b><span>clics a Amazon</span></div>
  <div class="kpi"><b><?= e($pct($totals['clicks'], $totals['views']) ?: '0 %') ?></b><span>de las visitas acaban en clic</span></div>
  <div class="kpi"><b><?= $totals['home'] ?></b><span>visitas a la portada</span></div>
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
  <p class="muted daybars__key"><span class="k k--v"></span> Visitas <span class="k k--c"></span> Clics a Amazon. Del <?= e(date('d/m', strtotime((string) array_key_first($series)))) ?> al <?= e(date('d/m', strtotime((string) array_key_last($series)))) ?>.</p>
</section>

<section class="card">
  <h2>Productos</h2>
  <?php $table($products, 'Producto'); ?>
</section>

<section class="card">
  <h2>Guías, comparativas y reseñas</h2>
  <?php $table($posts, 'Artículo'); ?>
</section>

<section class="card">
  <h2>Botones de los artículos más pulsados</h2>
  <?php if ($links): ?>
    <div class="table-wrap"><table class="table"><thead><tr><th>Búsqueda o producto del botón</th><th class="r">Clics</th></tr></thead><tbody>
    <?php foreach ($links as $l): ?><tr><td><?= e($l['label']) ?></td><td class="r"><?= (int) $l['c'] ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  <?php else: ?><p class="muted">Todavía no hay clics en los botones de los artículos.</p><?php endif; ?>
</section>

<p class="muted">Se cuentan las visitas de personas, sin robots y sin tus propias visitas mientras tengas el panel abierto. No se guarda ningún dato personal. Las ventas y las comisiones solo se ven en tu panel de Amazon Afiliados: un clic aquí no garantiza una venta allí.</p>
