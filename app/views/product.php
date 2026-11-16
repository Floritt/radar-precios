<?php
/** @var array $p @var array $offers @var ?array $best @var ?array $stats @var ?string $statsStore @var array $verdict @var array $chart @var array $related @var array $similar @var ?array $review */
$sorted = array_values($offers);
usort($sorted, fn($a, $b) => ($a['price'] ?? INF) <=> ($b['price'] ?? INF));
$verdictClass = in_array($verdict['key'], ['low', 'good', 'high'], true) ? ' verdict--' . $verdict['key'] : '';
$img = product_image($p);
$single = count($sorted) === 1 ? $sorted[0] : null;
// Sin precio no hay bajada que avisar: la caja vuelve sola cuando el producto tenga precio.
$showAlert = alerts_enabled() && $best !== null;
$facts = product_facts($p);
$ideal = $facts['Ideal para'] ?? null;
unset($facts['Ideal para']);
$intro = product_intro($p, 260);
$hasSide = $img || $showAlert || $best || $related;
$out = fn(array $o, string $from) => 'href="' . e($o['url']) . '" rel="sponsored nofollow noopener" target="_blank" data-out data-product="' . (int) $p['id'] . '" data-store="' . e($o['store']) . '" data-from="' . $from . '"';
?>
<div class="wrap page">
  <nav class="crumbs" aria-label="Migas de pan">
    <a href="<?= e(url('')) ?>">Inicio</a>
    <?php if ($p['niche_name']): ?> / <a href="<?= e(url('nicho/' . $p['niche_slug'])) ?>"><?= e($p['niche_name']) ?></a><?php endif; ?>
  </nav>
  <?php if ($p['status'] === 'unlisted'): ?><p class="draft-note">Producto añadido por un visitante. Seguimos su precio, aunque todavía no aparece en las categorías.</p>
  <?php elseif ($p['status'] !== 'published'): ?><p class="draft-note">Borrador: solo lo ves tú porque has entrado en el panel.</p><?php endif; ?>

  <div class="pp<?= $hasSide ? '' : ' pp--single' ?>">
    <div class="pp__main">
      <h1><?= e($p['name']) ?></h1>
      <div class="pp__meta">
        <?php if ($p['brand']): ?><span><?= e($p['brand']) ?></span><?php endif; ?>
        <?php if ($review): ?><a href="<?= e(post_url($review)) ?>"><span class="score"><?= e(number_format((float) $review['rating'], 1, ',', '')) ?><small>/10</small></span> Leer nuestra reseña</a><?php endif; ?>
      </div>

      <?php if ($best): ?>
      <div class="verdict<?= $verdictClass ?>">
        <span class="verdict__dot"></span>
        <div><strong><?= e($verdict['label']) ?></strong><?= e($verdict['text']) ?></div>
      </div>
      <?php else: ?>
        <?php if ($intro): ?><p class="pp__intro"><?= e($intro) ?></p><?php endif; ?>
        <?php if ($ideal): ?><p class="pp__ideal"><strong>Ideal para:</strong> <?= e($ideal) ?></p><?php endif; ?>
        <?php if ($facts): ?><ul class="pbox__facts pp__facts"><?php foreach ($facts as $k => $v): ?><li><span><?= e($k) ?></span> <?= e($v) ?></li><?php endforeach; ?></ul><?php endif; ?>
      <?php endif; ?>

      <?php if ($single): ?>
        <div class="buy">
          <?php if ($single['price'] !== null): ?>
            <div class="buy__price">
              <strong><?= e(money($single['price'])) ?></strong>
              <span>en <?= e($single['name']) ?>, <?= $single['manual'] ? 'visto el ' . e(date('d/m/Y', strtotime($single['at']))) : 'comprobado el ' . e(date_short($single['at'])) ?><?php if ($best && $best['list_price'] && $best['list_price'] > $best['price']): ?>. <s>PVP <?= e(money($best['list_price'])) ?></s><?php endif; ?></span>
            </div>
          <?php endif; ?>
          <a class="btn btn--buy btn--lg" <?= $out($single, 'product') ?>><?= $single['price'] !== null ? 'Ver oferta en ' : 'Ver precio en ' ?><?= e($single['name']) ?></a>
        </div>
      <?php elseif ($sorted): ?>
        <table class="offers">
          <thead><tr><th>Tienda</th><th>Precio</th><th><span class="visually-hidden">Comprar</span></th></tr></thead>
          <tbody>
          <?php foreach ($sorted as $i => $o): $isBest = $best ? $o['store'] === $best['store'] : $i === 0; ?>
            <tr<?= $isBest ? ' class="is-best"' : '' ?>>
              <td class="offers__store"><?= e($o['name']) ?>
                <span class="offers__note">
                  <?php if ($o['price'] === null): ?>Consulta el precio en la tienda
                  <?php elseif ($o['manual']): ?>Visto el <?= e(date('d/m/Y', strtotime($o['at']))) ?>
                  <?php else: ?>Comprobado el <?= e(date_short($o['at'])) ?><?php endif; ?>
                </span>
              </td>
              <td class="offers__price"><?= $o['price'] !== null ? e(money($o['price'])) : '' ?></td>
              <td><a class="btn <?= $isBest ? 'btn--buy' : '' ?> btn--sm" <?= $out($o, 'product') ?>>Ir a la tienda</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="empty">Aún no hay tiendas enlazadas para este producto.</p>
      <?php endif; ?>
      <?php if ($sorted): ?>
        <p class="fineprint">
          <?php if (isset($offers['amazon']) && $offers['amazon']['price'] !== null): ?>El precio de Amazon puede haber cambiado desde la hora indicada; el válido es el que veas en la tienda al comprar. <?php endif; ?>
          Enlace de afiliado: si compras, cobramos una pequeña comisión y a ti te cuesta lo mismo.
        </p>
      <?php endif; ?>
    </div>

    <?php if ($hasSide): ?>
    <aside class="pp__side">
      <?php if ($img): ?><div class="pp__img"><img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>"></div><?php endif; ?>
      <?php if ($showAlert): $suggest = $best ? floor($best['price'] * 0.9) : null; $byMail = mail_enabled(); $byTg = telegram_alerts_enabled(); ?>
        <form class="alert-box" method="post" action="<?= e(url('aviso')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="producto" value="<?= (int) $p['id'] ?>">
          <p class="alert-box__title"><?= icon('bell') ?> Avísame cuando baje</p>
          <label for="aviso-precio">Precio al que lo comprarías</label>
          <div class="alert-box__row">
            <input class="field" id="aviso-precio" name="precio" inputmode="decimal" required placeholder="<?= $suggest ? e(number_format($suggest, 2, ',', '')) : '25,00' ?>" autocomplete="off">
            <span>€</span>
          </div>
          <?php if ($byMail): ?>
            <label for="aviso-correo">Tu correo</label>
            <input class="field" id="aviso-correo" name="correo" type="email" required placeholder="nombre@correo.com" autocomplete="email">
            <button class="btn btn--blue" type="submit" name="canal" value="correo">Avisarme por correo</button>
          <?php endif; ?>
          <?php if ($byTg): ?><button class="btn<?= $byMail ? '' : ' btn--blue' ?>" type="submit" name="canal" value="telegram"<?= $byMail ? ' formnovalidate' : '' ?>>Avisarme por Telegram</button><?php endif; ?>
          <p class="fineprint"><?= $best ? '' : 'Todavía no tenemos el precio de este producto. Te avisaremos cuando empecemos a seguirlo y esté por debajo de esa cifra. ' ?><?= $byMail ? 'Usamos tu correo solo para este aviso. ' : '' ?><a href="<?= e(url('legal/privacidad')) ?>">Privacidad</a></p>
        </form>
      <?php endif; ?>
      <?php if ($best): ?>
        <?php partial('legend', ['class' => 'legend--side']); ?>
      <?php endif; ?>
      <?php if ($related): ?>
        <h2 class="aside-title">Aparece en</h2>
        <?php foreach ($related as $post): partial('mini-post', ['post' => $post]); endforeach; ?>
      <?php endif; ?>
    </aside>
    <?php endif; ?>
  </div>

  <?php if ($chart['series']): ?>
  <section class="chart-card" aria-labelledby="h-history">
    <div class="chart-head">
      <h2 id="h-history">Historial de precios</h2>
      <div class="seg" role="group" aria-label="Periodo">
        <button type="button" data-range="30">1 mes</button>
        <button type="button" data-range="90" aria-pressed="true">3 meses</button>
        <button type="button" data-range="180">6 meses</button>
      </div>
    </div>
    <div class="chart-box"><canvas id="price-chart" role="img" aria-label="Historial de precios de <?= e($p['name']) ?>"></canvas></div>
    <script type="application/json" id="price-data"><?= json_encode($chart, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
    <p class="chart-key">El punto amarillo marca el precio más bajo del periodo.</p>
    <?php if ($stats && $stats['current'] !== null): ?>
      <p class="stats-caption">Resumen de <?= e(store_label($p, (string) $statsStore)) ?> en los últimos 6 meses</p>
      <dl class="stats">
        <div><dt>Precio actual</dt><dd><?= e(money($stats['current'])) ?></dd></div>
        <div><dt>Media de 90 días</dt><dd><?= e(money($stats['avg90'])) ?></dd></div>
        <div><dt>Más bajo</dt><dd><?= e(money($stats['min'])) ?></dd></div>
        <div><dt>Más alto</dt><dd><?= e(money($stats['max'])) ?></dd></div>
      </dl>
    <?php endif; ?>
  </section>
  <?php endif; ?>
  <?php if ($best): partial('legend', ['class' => 'legend--below']); endif; ?>

  <?php if ($p['summary']): ?>
  <section class="block">
    <h2>Descripción</h2>
    <?php $desc = (string) $p['summary'];
      if (!$best && $intro !== '') { $parts = preg_split('/\n\s*\n/', trim($desc), 2); $desc = $parts[1] ?? ''; } ?>
    <?php if (trim($desc) !== ''): ?><div class="prose"><?= render_markdown($desc) ?></div><?php endif; ?>
    <?php $go = $best ?: ($sorted ? $sorted[0] : null); if ($go): ?>
      <p class="pp__again"><a class="btn btn--buy btn--lg" <?= $out($go, 'product-bottom') ?>><?= $go['price'] !== null ? 'Ver oferta en ' : 'Ver precio en ' ?><?= e($go['name']) ?></a></p>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ($similar): ?>
  <section class="block">
    <h2>Productos parecidos</h2>
    <div class="grid"><?php foreach ($similar as $s): partial('pcard', ['p' => $s]); endforeach; ?></div>
  </section>
  <?php endif; ?>
</div>
