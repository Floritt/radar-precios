<?php
/**
 * Portada: el precio de la gasolina de hoy, los avisos por zona y, después, los accesorios de coche y el resto de la web.
 * @var ?array $fuel @var bool $mailOn @var array $zones @var array $carProducts @var array $carGuides @var array $guides
 */
$niches = all_niches();
$car = car_niche();
$v16 = null;
foreach ($carProducts as $i => $p) {
    if ($p['slug'] === 'baliza-v16-conectada') {
        $v16 = $p;
        unset($carProducts[$i]);
    }
}
$carProducts = array_slice(array_values($carProducts), 0, 4);
$v16Guide = null;
foreach ($carGuides as $g) {
    if ($g['slug'] === 'que-baliza-v16-comprar') {
        $v16Guide = $g;
    }
}

/** Línea de 90 días de un carburante, escalada al recuadro que se le pasa. */
$spark = function (array $series, string $key, int $w, int $h, float $lo, float $hi): string {
    foreach ($series['series'] as $s) {
        if ($s['key'] !== $key) {
            continue;
        }
        $vals = $s['values'];
        $n = count($vals);
        $pts = [];
        foreach ($vals as $i => $v) {
            if ($v === null) {
                continue;
            }
            $x = $n > 1 ? $i / ($n - 1) * $w : 0;
            $y = $hi - $lo < 0.001 ? $h / 2 : $h - ($v - $lo) / ($hi - $lo) * $h;
            $pts[] = round($x, 1) . ',' . round($y, 1);
        }
        return implode(' ', $pts);
    }
    return '';
};
?>
<?php if ($fuel): $t = $fuel['today']; $k = $fuel['key']; ?>
<section class="fhero">
  <div class="wrap fhero__grid">
    <div class="fhero__text">
      <h1>Precio de la gasolina hoy y cuándo llenar el depósito</h1>
      <p class="fhero__lead">Precios oficiales de las <?= e(number_format($fuel['stations'], 0, ',', '.')) ?> gasolineras de España, actualizados cada hora.<?= $mailOn ? ' Te avisamos gratis cuando bajen o suban en tu zona.' : '' ?></p>
      <form class="fhero__search" method="get" action="<?= e(url('gasolina/buscar')) ?>" role="search">
        <label class="visually-hidden" for="hero-q">Municipio o código postal</label>
        <input id="hero-q" name="q" type="search" required minlength="2" placeholder="Tu municipio o código postal" autocomplete="off">
        <button class="btn btn--buy" type="submit">Ver gasolineras</button>
      </form>
      <div class="fhero__more">
        <button class="btn fhero__near" type="button" data-near data-fuel="<?= e($k) ?>" hidden><?= icon('navigation-arrow') ?> Cerca de mí</button>
        <p class="fhero__msg" data-near-msg hidden></p>
        <?php if ($mailOn): ?><a class="fhero__link" href="#aviso"><?= icon('bell') ?> Crear un aviso</a><?php endif; ?>
      </div>
    </div>

    <div class="totem-wrap">
      <div class="totem" role="group" aria-labelledby="totem-title">
        <p class="totem__head" id="totem-title">Media en España hoy</p>
        <ul class="totem__rows">
          <?php foreach (FUELS_MAIN as $f): $row = $t[$f]; if ($row['avg'] === null) continue; $d = $row['diff7']; ?>
          <li class="totem__row<?= $f === $k ? ' is-current' : '' ?>">
            <a class="totem__fuel" href="<?= e(url($f === 'g95' ? '' : '?c=' . $f)) ?>"><?= e(fuel_name($f)) ?></a>
            <span class="totem__price"><?= e(number_format((float) $row['avg'], 3, ',', '.')) ?><small>€/l</small></span>
            <?php if ($d !== null): ?>
              <span class="totem__chg<?= $d < -0.0005 ? ' is-down' : ($d > 0.0005 ? ' is-up' : '') ?>"><?= $d < -0.0005 ? icon('trend-down') : ($d > 0.0005 ? icon('trend-up') : '') ?><?= e(fuel_cents($d)) ?> <span>en 7 días</span></span>
            <?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php
        $vals = [];
        foreach ($fuel['series']['series'] as $s) { if (in_array($s['key'], ['g95', 'diesel'], true)) { $vals = array_merge($vals, array_filter($s['values'], fn ($v) => $v !== null)); } }
        if (count($fuel['series']['labels']) > 6 && $vals):
            $lo = min($vals) - 0.01; $hi = max($vals) + 0.01;
        ?>
        <figure class="totem__spark">
          <svg viewBox="0 0 300 64" preserveAspectRatio="none" aria-hidden="true" focusable="false">
            <polyline class="totem__line totem__line--g95" points="<?= e($spark($fuel['series'], 'g95', 300, 64, $lo, $hi)) ?>" />
            <polyline class="totem__line totem__line--diesel" points="<?= e($spark($fuel['series'], 'diesel', 300, 64, $lo, $hi)) ?>" />
          </svg>
          <figcaption><span class="totem__key totem__key--g95">Gasolina 95</span> <span class="totem__key totem__key--diesel">Diésel</span> en los últimos <?= count($fuel['series']['labels']) ?> días</figcaption>
        </figure>
        <?php endif; ?>
        <?php if ($fuel['verdict']): $vd = $fuel['verdict']; ?>
          <p class="totem__verdict totem__verdict--<?= e($vd[0]) ?>"><strong><?= e($vd[1]) ?>.</strong> <?= e($vd[2]) ?></p>
        <?php endif; ?>
      </div>
      <span class="totem__pole" aria-hidden="true"></span>
    </div>
  </div>
</section>

<?php if ($mailOn): ?>
<section class="sec zone-sec" id="aviso" aria-labelledby="h-aviso">
  <div class="wrap">
    <div class="zone-band">
      <div class="zone-band__head">
        <h2 id="h-aviso">Te avisamos cuando cambie el precio en tu zona</h2>
        <p>Un correo cuando la media sube o baja los céntimos que elijas, con las tres gasolineras más baratas de ese momento. Para toda España, tu provincia o tu código postal.</p>
      </div>
      <?php partial('fuel-zone-form', ['zones' => $zones, 'form' => ['zone' => 'es', 'fuel' => ''], 'idp' => 'zh', 'variant' => 'band']); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="sec<?= $mailOn ? ' sec--tight' : '' ?>" id="provincias" aria-labelledby="h-prov">
  <div class="wrap">
    <div class="sec__head">
      <h2 id="h-prov">Hoy por provincias</h2>
      <?php partial('fuel-tabs', ['fuel' => $k, 'base' => url('')]); ?>
    </div>
    <?php
    $all = $fuel['provinces'];
    $nat = $t[$k]['avg'];
    $provs = array_values(array_filter($all, fn ($p) => !in_array((int) $p['province_id'], FUEL_LOW_TAX, true)));
    $lowProvs = array_values(array_filter($all, fn ($p) => in_array((int) $p['province_id'], FUEL_LOW_TAX, true)));
    $groups = count($provs) > 10 ? [['Las más baratas de la península y Baleares', array_slice($provs, 0, 5)], ['Las más caras', array_reverse(array_slice($provs, -5))]] : [['De la más barata a la más cara', $provs]];
    ?>
    <div class="prov-rank<?= count($groups) === 1 ? ' prov-rank--one' : '' ?>">
      <?php foreach ($groups as [$title, $list]): ?>
      <div>
        <h3><?= e($title) ?></h3>
        <ol class="prov-list">
          <?php foreach ($list as $p): $diff = $nat !== null ? (float) $p['avg'] - $nat : null; ?>
          <li>
            <a href="<?= e(fuel_province_url((int) $p['province_id']) . ($k === 'g95' ? '' : '?c=' . $k)) ?>"><?= e($p['name']) ?></a>
            <span class="prov-list__price"><?= e(fuel_money($p['avg'], false)) ?></span>
            <?php if ($diff !== null): ?><span class="prov-list__diff <?= $diff < 0 ? 'is-down' : 'is-up' ?>"><?= e(fuel_cents($diff)) ?></span><?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ol>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if ($lowProvs): ?>
    <p class="prov-low">Con menos impuestos:
      <?php foreach ($lowProvs as $i => $p): ?><a href="<?= e(fuel_province_url((int) $p['province_id']) . ($k === 'g95' ? '' : '?c=' . $k)) ?>"><?= e($p['name']) ?></a> <?= e(fuel_money($p['avg'], false)) ?><?= $i < count($lowProvs) - 1 ? ', ' : '.' ?><?php endforeach; ?>
    </p>
    <?php endif; ?>
    <p class="fuel-legend"><?= e(fuel_name($k)) ?>, media de cada provincia en €/l y diferencia con la media de España.</p>
    <a class="more" href="<?= e(url('gasolina' . ($k === 'g95' ? '' : '?c=' . $k))) ?>">Ver las <?= count($all) ?> provincias y el gráfico</a>
  </div>
</section>

<section class="sec sec--tight" aria-labelledby="h-cheap">
  <div class="wrap">
    <div class="sec__head">
      <h2 id="h-cheap">Las 5 gasolineras más baratas de la península y Baleares</h2>
      <a href="<?= e(url('gasolina')) ?>">Busca las de tu municipio</a>
    </div>
    <?php partial('fuel-cheapest', ['split' => $fuel['cheapest'], 'fuel' => $k, 'base' => url(''), 'hid' => 'h-cheap']); ?>
  </div>
</section>
<?php endif; ?>

<?php if ($car && ($v16 || $carProducts)): ?>
<section class="sec sec--tint" aria-labelledby="h-car">
  <div class="wrap">
    <div class="sec__head">
      <h2 id="h-car">Para tu coche</h2>
      <a href="<?= e(niche_url($car)) ?>">Ver todos los accesorios</a>
    </div>
    <div class="car-grid">
      <?php if ($v16): ?>
      <div class="car-feature">
        <?= icon('car-profile') ?>
        <h3>¿Todavía llevas triángulos?</h3>
        <p>Desde el 1 de enero de 2026 solo vale la baliza V16 conectada a la DGT. Las antiguas sin conexión tampoco sirven.</p>
        <div class="car-feature__links">
          <a class="btn btn--buy" href="<?= e(product_url($v16)) ?>">Ver la baliza V16</a>
          <?php if ($v16Guide): ?><a href="<?= e(post_url($v16Guide)) ?>">Cómo saber si es válida</a><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php foreach ($carProducts as $p): partial('car-card', ['p' => $p]); endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($carGuides || $guides): ?>
<section class="sec" aria-labelledby="h-guides">
  <div class="wrap">
    <div class="sec__head">
      <h2 id="h-guides">Guías</h2>
      <a href="<?= e(url('blog')) ?>">Ver todas las guías</a>
    </div>
    <div class="guide-cols">
      <?php if ($carGuides): ?>
      <div>
        <h3 class="guide-cols__title">Para conductores</h3>
        <?php foreach ($carGuides as $post): partial('mini-post', ['post' => $post, 'plain' => true]); endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if ($guides): ?>
      <div>
        <h3 class="guide-cols__title">Para comprar en el mejor momento</h3>
        <?php foreach ($guides as $post): partial('mini-post', ['post' => $post, 'plain' => true]); endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="sec sec--tight" aria-labelledby="h-amazon">
  <div class="wrap">
    <div class="amz">
      <div class="amz__text">
        <h2 id="h-amazon">También seguimos precios de Amazon</h2>
        <p>Pega el enlace de un producto y te enseñamos cómo ha cambiado su precio.</p>
        <form class="paste paste--light" method="post" action="<?= e(url('seguir')) ?>">
          <?= csrf_field() ?>
          <label class="visually-hidden" for="hook-link">Enlace de Amazon</label>
          <input id="hook-link" name="enlace" type="url" inputmode="url" required placeholder="Pega aquí el enlace de Amazon" autocomplete="off">
          <button class="btn btn--blue" type="submit">Seguir precio</button>
        </form>
      </div>
      <?php if ($niches): ?>
      <ul class="chips" aria-label="Categorías">
        <?php foreach ($niches as $n): if ($n['slug'] === CAR_NICHE) continue; ?>
          <li><a href="<?= e(niche_url($n)) ?>"><?= niche_icon($n['slug']) ?><?= e($n['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>
</section>
