<?php
/** @var string $q @var array $products @var array $posts */
?>
<div class="wrap page">
  <div class="list-head">
    <div>
      <h1><?= $q ? 'Resultados para «' . e($q) . '»' : 'Buscar' ?></h1>
      <form class="search-page" action="<?= e(url('buscar')) ?>" method="get" role="search">
        <label class="visually-hidden" for="sq">Buscar</label>
        <input id="sq" name="q" type="search" value="<?= e($q) ?>" placeholder="Nombre del producto o enlace de Amazon">
        <button type="submit">Buscar</button>
      </form>
    </div>
  </div>
  <?php if ($q && !$products && !$posts): ?>
    <div class="panel"><p class="empty">No hay resultados para «<?= e($q) ?>». Prueba con una palabra más general, como «luces» o «perfume».</p></div>
  <?php endif; ?>
  <?php if ($products): ?>
    <div class="section-title"><h2>Productos</h2></div>
    <div class="grid" style="margin-bottom: 24px"><?php foreach ($products as $p): partial('pcard', ['p' => $p]); endforeach; ?></div>
  <?php endif; ?>
  <?php if ($posts): ?>
    <div class="section-title"><h2>Reseñas y guías</h2></div>
    <div class="acards"><?php foreach ($posts as $post): partial('acard', ['post' => $post]); endforeach; ?></div>
  <?php endif; ?>
</div>
