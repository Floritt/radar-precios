<?php
/** @var array $s @var string $pslug @var array $rank @var array $chart @var bool $mailOn */
$prov = FUEL_PROVINCES[(int) $s['province_id']] ?? '';
$first = null;
foreach (FUELS_MAIN as $f) { if ($s[$f] !== null) { $first = $f; break; } }
?>
<div class="wrap page">
  <nav class="crumbs" aria-label="Migas de pan">
    <a href="<?= e(url('')) ?>">Inicio</a> / <a href="<?= e(url('gasolina')) ?>">Gasolina</a> / <a href="<?= e(url('gasolina/' . $pslug)) ?>"><?= e($prov) ?></a>
    <?php if ($s['muni_slug']): ?> / <a href="<?= e(url('gasolina/' . $pslug . '/' . $s['muni_slug'])) ?>"><?= e($s['muni_name']) ?></a><?php endif; ?>
  </nav>

  <div class="pp<?= $mailOn && $first ? '' : ' pp--single' ?>">
    <div class="pp__main">
      <h1><?= e($s['name']) ?></h1>
      <p class="fuel-addr"><?= e($s['address']) ?>, <?= e($s['cp']) ?> <?= e($s['muni_name']) ?> (<?= e($prov) ?>)</p>
      <?php if (!(int) $s['active']): ?><p class="draft-note">Esta gasolinera ya no aparece en los datos oficiales. Los precios son los últimos que conocemos.</p><?php endif; ?>
      <ul class="fuel-meta">
        <?php if ($s['schedule']): ?><li><strong>Horario:</strong> <?= e($s['schedule']) ?></li><?php endif; ?>
        <?php if ($s['lat'] !== null): ?><li><a href="https://www.google.com/maps/dir/?api=1&amp;destination=<?= e($s['lat'] . ',' . $s['lng']) ?>" target="_blank" rel="noopener">Cómo llegar</a></li><?php endif; ?>
      </ul>

      <table class="fuel-prices">
        <thead><tr><th>Carburante</th><th class="r">Precio hoy</th><th>En su municipio</th></tr></thead>
        <tbody>
        <?php foreach (FUELS as $f => [$label]): if ($s[$f] === null) continue; $r = $rank[$f] ?? null; ?>
          <tr>
            <td><?= e($label) ?></td>
            <td class="r"><strong><?= e(fuel_money($s[$f])) ?></strong></td>
            <td><?php if ($r && $r['n'] > 1): ?>
              <?= $r['pos'] === 1 ? '<span class="is-down">La más barata</span>' : 'La ' . $r['pos'] . '.ª más barata' ?> de <?= $r['n'] ?>
              <span class="fuel-muted">(media <?= e(fuel_money($r['avg'], false)) ?>)</span>
            <?php elseif ($r): ?>Única del municipio<?php endif; ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <p class="fineprint">Precios comunicados por la gasolinera al Ministerio. Actualizado el <?= e(date_short($s['updated_at'])) ?>. El válido es el del surtidor.</p>
    </div>

    <?php if ($mailOn && $first): ?>
    <aside class="pp__side">
      <form class="alert-box" method="post" action="<?= e(url('gasolina/aviso')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="gasolinera" value="<?= (int) $s['id'] ?>">
        <p class="alert-box__title"><?= icon('bell') ?> Avísame si baja</p>
        <label for="fa-fuel">Carburante</label>
        <select id="fa-fuel" class="field fuel-select" name="c" required data-fuel-select>
          <option value="" selected disabled>Elige el carburante</option>
          <?php foreach (FUELS_MAIN as $f): if ($s[$f] === null) continue; ?><option value="<?= e($f) ?>" data-suggest="<?= e(number_format(max(0.5, (float) $s[$f] - 0.03), 3, ',', '')) ?>"><?= e(fuel_name($f)) ?> (hoy <?= e(fuel_money($s[$f], false)) ?>)</option><?php endforeach; ?>
        </select>
        <label for="fa-price">Cuando baje de</label>
        <div class="alert-box__row"><input id="fa-price" class="field" name="precio" inputmode="decimal" required placeholder="1,499" data-fuel-price> €/l</div>
        <label for="fa-mail">Tu correo</label>
        <input id="fa-mail" class="field" type="email" name="correo" required autocomplete="email" placeholder="tu@correo.es">
        <button class="btn btn--blue" type="submit">Crear aviso</button>
        <p class="fineprint">Te mandamos un correo para confirmarlo. Usamos tu correo solo para este aviso. <a href="<?= e(url('legal/privacidad')) ?>">Privacidad</a></p>
      </form>
    </aside>
    <?php endif; ?>
  </div>

  <?php if (count($chart['labels']) > 1): ?>
  <section class="chart-card" aria-labelledby="h-fuel-chart">
    <div class="chart-head"><h2 id="h-fuel-chart">Historial de precios de esta gasolinera</h2></div>
    <div class="chart-box"><canvas id="fuel-chart" role="img" aria-label="Historial de precios de <?= e($s['name']) ?>"></canvas></div>
    <script type="application/json" id="fuel-data"><?= json_encode($chart, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
  </section>
  <?php else: ?>
    <p class="fuel-note">Empezamos a guardar el precio de cada gasolinera día a día. En unos días verás aquí su historial.</p>
  <?php endif; ?>

  <?php if ($s['muni_slug']): ?><p><a href="<?= e(url('gasolina/' . $pslug . '/' . $s['muni_slug'])) ?>">Ver todas las gasolineras de <?= e($s['muni_name']) ?></a></p><?php endif; ?>
</div>
