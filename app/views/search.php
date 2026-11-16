<?php
/** @var string $q @var array $products @var array $posts @var ?string $follow @var bool $isLink */
?>
<div class="page-head">
  <div class="wrap">
    <h1><?= $q ? 'Resultados para «' . e($q) . '»' : 'Buscar' ?></h1>
    <form class="search-page" action="<?= e(url('buscar')) ?>" method="get" role="search">
      <label class="visually-hidden" for="sq">Buscar</label>
      <input class="field" id="sq" name="q" type="search" value="<?= e($q) ?>" placeholder="Nombre del producto o enlace de Amazon">
      <button class="btn btn--blue" type="submit">Buscar</button>
    </form>
  </div>
</div>
<div class="wrap page">
  <?php if ($follow): ?>
    <div class="notice">
      <h2>Todavía no seguimos este producto</h2>
      <p>Si quieres, empezamos a seguir su precio ahora.</p>
      <form method="post" action="<?= e(url('seguir')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="enlace" value="<?= e((string) input('q', '')) ?>">
        <button class="btn btn--buy" type="submit">Seguir su precio</button>
      </form>
    </div>
  <?php elseif ($isLink): ?>
    <p class="empty">No encontramos un producto de Amazon en ese enlace. Prueba con el enlace de la página del producto.</p>
  <?php endif; ?>
  <?php if ($q && !$products && !$posts && !$isLink): ?>
    <p class="empty">No hay resultados para «<?= e($q) ?>». Prueba con una palabra más general, como «luces» o «perfume».</p>
  <?php endif; ?>
  <?php if ($products): ?>
  <section class="block">
    <h2>Productos</h2>
    <div class="grid"><?php foreach ($products as $p): partial('pcard', ['p' => $p]); endforeach; ?></div>
  </section>
  <?php endif; ?>
  <?php if ($posts): ?>
  <section class="block">
    <h2>Guías y comparativas</h2>
    <div class="acards"><?php foreach ($posts as $post): partial('acard', ['post' => $post]); endforeach; ?></div>
  </section>
  <?php endif; ?>
</div>
