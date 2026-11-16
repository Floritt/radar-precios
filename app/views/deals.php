<?php
/** @var array $products @var string $type @var int $page @var int $pages @var ?array $niche */
$tabs = ['' => 'Todas las bajadas', 'minimos' => 'Mínimos históricos', 'nuevos' => 'Recién añadidos'];
$qs = fn(array $extra) => http_build_query(array_filter(array_merge(['tipo' => $type, 'nicho' => $niche['slug'] ?? ''], $extra)));
?>
<div class="page-head">
  <div class="wrap">
    <h1>Ofertas</h1>
    <p>Productos que han bajado de precio respecto a su media de los últimos 90 días.</p>
  </div>
</div>
<div class="wrap page">
  <nav class="chips" aria-label="Tipo de oferta">
    <?php foreach ($tabs as $k => $label): ?>
      <a href="<?= e(url('ofertas') . ($k || $niche ? '?' . http_build_query(array_filter(['tipo' => $k, 'nicho' => $niche['slug'] ?? ''])) : '')) ?>"<?= $type === $k ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <?php if ($products): ?>
    <div class="deals"><?php foreach ($products as $p): partial('deal', ['p' => $p]); endforeach; ?></div>
  <?php else: ?>
    <p class="empty">No hay ofertas con este filtro ahora mismo.</p>
  <?php endif; ?>
  <?php if ($pages > 1): ?>
    <nav class="pager" aria-label="Páginas">
      <?php if ($page > 1): ?><a href="<?= e(url('ofertas') . '?' . $qs(['pagina' => $page - 1])) ?>">Anterior</a><?php else: ?><span></span><?php endif; ?>
      <span>Página <?= $page ?> de <?= $pages ?></span>
      <?php if ($page < $pages): ?><a href="<?= e(url('ofertas') . '?' . $qs(['pagina' => $page + 1])) ?>">Siguiente</a><?php else: ?><span></span><?php endif; ?>
    </nav>
  <?php endif; ?>
</div>
