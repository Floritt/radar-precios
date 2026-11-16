<?php /** @var array $counts @var array $topClicks @var array $lows @var string $lastCron @var string $lastCronResult @var bool $demo @var bool $amazonOn @var bool $telegramOn @var int $pendingAsin @var bool $alertsOn @var array $alertCounts @var array $requests @var array $unlisted */ ?>
<h1>Escritorio</h1>

<?php if ($demo): ?>
  <section class="card card--warn">
    <h2>Hay contenido de ejemplo</h2>
    <p>Los productos y artículos de ejemplo tienen precios inventados y se ven en la web con un aviso amarillo. Bórralos antes de anunciar la web; los nichos se quedan.</p>
    <form method="post" action="<?= e(url('admin/ejemplo/borrar')) ?>" data-confirm="Se borrarán los productos y artículos de ejemplo. ¿Seguro?">
      <?= csrf_field() ?><button class="btn btn--danger" type="submit">Borrar contenido de ejemplo</button>
    </form>
  </section>
<?php endif; ?>

<div class="kpis">
  <div class="kpi"><b><?= $counts['products'] ?></b><span>productos publicados</span></div>
  <div class="kpi"><b><?= $counts['drafts'] ?></b><span>productos en borrador</span></div>
  <div class="kpi"><b><?= $counts['posts'] ?></b><span>artículos publicados</span></div>
  <div class="kpi"><b><?= $counts['clicks'] ?></b><span>clics a tiendas en 30 días</span></div>
</div>

<div class="cols">
  <section class="card">
    <h2>Conexiones</h2>
    <ul class="status">
      <li class="<?= $amazonOn ? 'ok' : 'off' ?>">
        <strong>Amazon Creators API:</strong> <?= $amazonOn ? 'activada' : 'desactivada' ?>.
        <?php if (!$amazonOn): ?>Sin ella no se muestran precios de Amazon, solo el botón. Actívala en config.php cuando tengas 10 ventas en 30 días.<?php endif; ?>
        <?php if ($amazonOn && $pendingAsin): ?><?= $pendingAsin ?> productos esperan su primera consulta.<?php endif; ?>
      </li>
      <li class="<?= $telegramOn ? 'ok' : 'off' ?>"><strong>Canal de Telegram:</strong> <?= $telegramOn ? 'activado' : 'desactivado' ?>.</li>
      <li class="<?= $alertsOn ? 'ok' : 'off' ?>"><strong>Avisos de precio:</strong>
        <?= $alertsOn ? $alertCounts['active'] . ' avisos activos, ' . $alertCounts['sent'] . ' enviados en 30 días.' : 'desactivados. Pega el token de tu bot en Ajustes y pulsa Conectar.' ?>
      </li>
      <li class="<?= $lastCron ? 'ok' : 'off' ?>"><strong>Recolector de precios:</strong>
        <?= $lastCron ? 'última ejecución el ' . e(date_short($lastCron)) . '. ' . e($lastCronResult) : 'todavía no se ha ejecutado. Programa la tarea cron (lo explica el README).' ?>
      </li>
    </ul>
  </section>

  <section class="card">
    <h2>Lo más pulsado en 30 días</h2>
    <?php if ($topClicks): ?>
      <table class="table"><thead><tr><th>Producto</th><th>Tienda</th><th class="r">Clics</th></tr></thead><tbody>
      <?php foreach ($topClicks as $c): ?>
        <tr><td><a href="<?= e(url('admin/productos/' . $c['id'])) ?>"><?= e($c['name']) ?></a></td><td><?= e(STORES[$c['store']] ?? $c['store']) ?></td><td class="r"><?= (int) $c['total'] ?></td></tr>
      <?php endforeach; ?>
      </tbody></table>
    <?php else: ?><p class="muted">Todavía no hay clics registrados.</p><?php endif; ?>
  </section>
</div>

<section class="card">
  <h2>En su precio más bajo ahora</h2>
  <?php if ($lows): ?>
    <ul class="plain">
      <?php foreach ($lows as $p): ?><li><a href="<?= e(url('admin/productos/' . $p['id'])) ?>"><?= e($p['name']) ?></a> <span class="muted"><?= e(money($p['best_price'])) ?> en <?= e(STORES[$p['best_store']] ?? '') ?></span></li><?php endforeach; ?>
    </ul>
    <p class="muted">Buen material para publicar hoy en tus canales o escribir una guía.</p>
  <?php else: ?><p class="muted">Ningún producto está en mínimo ahora mismo.</p><?php endif; ?>
</section>

<section class="card">
  <h2>Siguientes pasos</h2>
  <ul class="plain">
    <li><a href="<?= e(url('admin/productos/nuevo')) ?>">Añadir un producto</a></li>
    <li><a href="<?= e(url('admin/articulos/nuevo?tipo=review')) ?>">Escribir una reseña</a></li>
    <li><a href="<?= e(url('admin/articulos/nuevo?tipo=comparison')) ?>">Escribir una comparativa</a></li>
    <li><a href="<?= e(url('admin/articulos/nuevo')) ?>">Escribir una guía o artículo</a></li>
    <li><a href="<?= e(url('admin/ajustes')) ?>">Completar tus datos legales</a></li>
  </ul>
</section>

<?php if ($requests || $unlisted): ?>
<div class="cols">
  <?php if ($unlisted): ?>
  <section class="card">
    <h2>Añadidos por visitantes</h2>
    <p class="muted">Fichas creadas al pegar un enlace. Se ven y se siguen, pero no salen en categorías. Ábrelas, ponles categoría y publícalas si te interesan.</p>
    <table class="table"><thead><tr><th>Producto</th><th>Añadido</th></tr></thead><tbody>
      <?php foreach ($unlisted as $u): ?>
        <tr><td><a href="<?= e(url('admin/productos/' . $u['id'])) ?>"><?= e($u['name']) ?></a></td><td><?= e(date_short($u['created_at'])) ?></td></tr>
      <?php endforeach; ?>
    </tbody></table>
  </section>
  <?php endif; ?>
  <?php if ($requests): ?>
  <section class="card">
    <h2>Peticiones en espera</h2>
    <p class="muted">Enlaces pegados por visitantes mientras la API de Amazon no está activa. Se convierten en fichas solas cuando la actives.</p>
    <table class="table"><thead><tr><th>ASIN</th><th class="r">Veces</th><th>Estado</th></tr></thead><tbody>
      <?php foreach ($requests as $r): ?>
        <tr><td><a href="https://www.amazon.es/dp/<?= e($r['asin']) ?>" rel="noopener" target="_blank"><?= e($r['asin']) ?></a></td><td class="r"><?= (int) $r['requests'] ?></td><td><?= $r['status'] === 'error' ? e($r['error'] ?: 'Error') : 'Pendiente' ?></td></tr>
      <?php endforeach; ?>
    </tbody></table>
  </section>
  <?php endif; ?>
</div>
<?php endif; ?>
