<?php
/** @var array $p @var array $offers @var ?array $best @var ?array $stats @var ?string $statsStore @var array $verdict @var array $chart @var array $related @var array $similar @var ?array $review */
$sorted = array_values($offers);
usort($sorted, fn($a, $b) => ($a['price'] ?? INF) <=> ($b['price'] ?? INF));
$verdictClass = in_array($verdict['key'], ['low', 'good', 'high'], true) ? ' pp__verdict--' . $verdict['key'] : '';
?>
<div class="wrap page">
  <nav class="crumbs" aria-label="Migas de pan">
    <a href="<?= e(url('')) ?>">Inicio</a>
    <?php if ($p['niche_name']): ?> / <a href="<?= e(url('nicho/' . $p['niche_slug'])) ?>"><?= e($p['niche_name']) ?></a><?php endif; ?>
  </nav>
  <?php if ($p['status'] !== 'published'): ?><p class="draft-note">Borrador: solo lo ves tú porque has entrado en el panel.</p><?php endif; ?>

  <div class="pp">
    <div class="panel pp__gallery"><?php partial('thumb', ['p' => $p]); ?></div>

    <div class="panel pp__info">
      <h1><?= e($p['name']) ?></h1>
      <div class="pp__meta">
        <?php if ($p['brand']): ?><span>Marca: <?= e($p['brand']) ?></span><?php endif; ?>
        <?php if ($review): ?><a href="<?= e(post_url($review)) ?>"><span class="score"><?= e(number_format((float) $review['rating'], 1, ',', '')) ?><small>/10</small></span> Leer nuestra reseña</a><?php endif; ?>
        <?php if ((int) $p['at_low'] && $best): ?><span class="badge badge--low">Mínimo histórico</span><?php endif; ?>
      </div>

      <?php if ($best): ?>
        <div class="pp__best">
          <span>Mejor precio</span>
          <strong><?= e(money($best['price'])) ?></strong>
          <span>en <?= e($best['name']) ?></span>
          <?php if ($best['list_price'] && $best['list_price'] > $best['price']): ?><s>PVP <?= e(money($best['list_price'])) ?></s><?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="pp__verdict<?= $verdictClass ?>">
        <strong><?= e($verdict['label']) ?></strong>
        <?= e($verdict['text']) ?>
      </div>

      <?php if ($sorted): ?>
        <table class="offers">
          <thead><tr><th>Tienda</th><th>Precio</th><th><span class="visually-hidden">Comprar</span></th></tr></thead>
          <tbody>
          <?php foreach ($sorted as $o): $isBest = $best && $o['store'] === $best['store']; ?>
            <tr<?= $isBest ? ' class="is-best"' : '' ?>>
              <td class="offers__store"><?= e($o['name']) ?>
                <span class="offers__note">
                  <?php if ($o['price'] === null): ?>Consulta el precio en la tienda
                  <?php elseif ($o['manual']): ?>Visto el <?= e(date('d/m/Y', strtotime($o['at']))) ?>
                  <?php else: ?>Comprobado el <?= e(date_short($o['at'])) ?><?php endif; ?>
                </span>
              </td>
              <td class="offers__price"><?= $o['price'] !== null ? e(money($o['price'])) : '' ?></td>
              <td><a class="btn <?= $isBest ? 'btn--buy' : '' ?> btn--sm" href="<?= e($o['url']) ?>" rel="sponsored nofollow noopener" target="_blank" data-out data-product="<?= (int) $p['id'] ?>" data-store="<?= e($o['store']) ?>" data-from="product">Ir a la tienda</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <p class="fineprint">
          <?php if (isset($offers['amazon']) && $offers['amazon']['price'] !== null): ?>El precio de Amazon puede haber cambiado desde la hora indicada; el válido es el que veas en la tienda al comprar. <?php endif; ?>
          Enlaces de afiliado: si compras, cobramos una pequeña comisión y a ti te cuesta lo mismo.
        </p>
      <?php else: ?>
        <p class="empty">Aún no hay tiendas enlazadas para este producto.</p>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($chart['series']): ?>
  <section class="panel" aria-labelledby="h-history">
    <div class="panel__head chart-head">
      <h2 id="h-history">Historial de precios</h2>
      <div class="seg" role="group" aria-label="Periodo">
        <button type="button" data-range="30">1 mes</button>
        <button type="button" data-range="90" aria-pressed="true">3 meses</button>
        <button type="button" data-range="180">6 meses</button>
      </div>
    </div>
    <div class="panel__body">
      <div class="chart-box"><canvas id="price-chart" role="img" aria-label="Historial de precios de <?= e($p['name']) ?>"></canvas></div>
      <script type="application/json" id="price-data"><?= json_encode($chart, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
      <?php if ($stats && $stats['current'] !== null): ?>
        <p class="stats-caption">Resumen de <?= e(store_label($p, (string) $statsStore)) ?> en los últimos 6 meses</p>
        <dl class="stats">
          <div><dt>Precio actual</dt><dd><?= e(money($stats['current'])) ?></dd></div>
          <div><dt>Media 90 días</dt><dd><?= e(money($stats['avg90'])) ?></dd></div>
          <div><dt>Más bajo</dt><dd><?= e(money($stats['min'])) ?></dd></div>
          <div><dt>Más alto</dt><dd><?= e(money($stats['max'])) ?></dd></div>
        </dl>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($p['summary']): ?>
  <section class="panel">
    <div class="panel__head"><h2>Descripción</h2></div>
    <div class="panel__body prose"><?= render_markdown($p['summary']) ?></div>
  </section>
  <?php endif; ?>

  <?php if ($related): ?>
    <div class="section-title"><h2>Reseñas y guías donde aparece</h2></div>
    <div class="acards" style="margin-bottom: 24px"><?php foreach ($related as $post): partial('acard', ['post' => $post]); endforeach; ?></div>
  <?php endif; ?>

  <?php if ($similar): ?>
    <div class="section-title"><h2>Productos parecidos</h2></div>
    <div class="grid"><?php foreach ($similar as $s): partial('pcard', ['p' => $s]); endforeach; ?></div>
  <?php endif; ?>
</div>
