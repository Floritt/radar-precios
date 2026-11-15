<?php
/** @var string $title @var string $kind @var array $posts @var ?array $niche @var int $page @var int $pages */
$base = $kind === 'review' ? 'resenas' : 'blog';
?>
<div class="wrap page">
  <div class="list-head">
    <div>
      <h1><?= e($title) ?></h1>
      <p><?= $kind === 'review' ? 'Productos que hemos probado, con nota, pros y contras y su historial de precio.' : 'Consejos para elegir bien y no pagar de más.' ?></p>
    </div>
  </div>
  <nav class="chips" aria-label="Categoría">
    <a href="<?= e(url($base)) ?>"<?= !$niche ? ' aria-current="page"' : '' ?>>Todas</a>
    <?php foreach (all_niches() as $n): ?>
      <a href="<?= e(url($base) . '?nicho=' . $n['slug']) ?>"<?= $niche && $niche['id'] == $n['id'] ? ' aria-current="page"' : '' ?>><?= e($n['name']) ?></a>
    <?php endforeach; ?>
  </nav>
  <?php if ($posts): ?>
    <div class="acards"><?php foreach ($posts as $post): partial('acard', ['post' => $post]); endforeach; ?></div>
  <?php else: ?>
    <div class="panel"><p class="empty">Aún no hay nada publicado aquí.</p></div>
  <?php endif; ?>
  <?php if ($pages > 1): $q = $niche ? '&nicho=' . $niche['slug'] : ''; ?>
    <nav class="pager panel" aria-label="Páginas" style="margin-top:16px">
      <?php if ($page > 1): ?><a href="<?= e(url($base) . '?pagina=' . ($page - 1) . $q) ?>">Anterior</a><?php else: ?><span></span><?php endif; ?>
      <span>Página <?= $page ?> de <?= $pages ?></span>
      <?php if ($page < $pages): ?><a href="<?= e(url($base) . '?pagina=' . ($page + 1) . $q) ?>">Siguiente</a><?php else: ?><span></span><?php endif; ?>
    </nav>
  <?php endif; ?>
</div>
