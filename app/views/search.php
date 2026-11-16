<?php
/** @var string $q @var array $products @var array $posts @var ?string $follow @var bool $isLink */
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
  <?php if ($follow): ?>
    <div class="panel panel__body follow__done">
      <h2>Todavía no seguimos este producto</h2>
      <p>Si quieres, empezamos a seguir su precio ahora.</p>
      <form method="post" action="<?= e(url('seguir')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="enlace" value="<?= e((string) input('q', '')) ?>">
        <button class="btn btn--buy" type="submit">Seguir su precio</button>
      </form>
    </div>
  <?php elseif ($isLink): ?>
    <div class="panel"><p class="empty">No encontramos un producto de Amazon en ese enlace. Prueba con el enlace de la página del producto.</p></div>
  <?php endif; ?>
  <?php if ($q && !$products && !$posts && !$isLink): ?>
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
