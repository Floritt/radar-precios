<?php
/** @var array $niche @var array $products @var array $posts @var string $sort */
?>
<div class="wrap page">
  <nav class="crumbs" aria-label="Migas de pan"><a href="<?= e(url('')) ?>">Inicio</a> / <?= e($niche['name']) ?></nav>
  <div class="list-head">
    <div>
      <h1><?= e($niche['name']) ?></h1>
      <?php if ($niche['intro']): ?><p><?= e($niche['intro']) ?></p><?php endif; ?>
    </div>
    <?php if ($products): ?>
    <form method="get" class="sortform">
      <label for="orden"><?= count($products) === 1 ? '1 producto' : count($products) . ' productos' ?>, ordenar por</label>
      <select id="orden" name="orden" data-autosubmit>
        <option value="bajada"<?= $sort === 'bajada' ? ' selected' : '' ?>>Mayor bajada</option>
        <option value="precio"<?= $sort === 'precio' ? ' selected' : '' ?>>Precio más bajo</option>
        <option value="nombre"<?= $sort === 'nombre' ? ' selected' : '' ?>>Nombre</option>
      </select>
      <noscript><button type="submit">Aplicar</button></noscript>
    </form>
    <?php endif; ?>
  </div>

  <?php if ($products): ?>
    <div class="grid"><?php foreach ($products as $p): partial('pcard', ['p' => $p]); endforeach; ?></div>
  <?php elseif (!$posts): ?>
    <div class="panel"><p class="empty">Todavía no hay productos en esta categoría.</p></div>
  <?php endif; ?>

  <?php if ($posts): ?>
    <div class="section-title"<?= $products ? ' style="margin-top: 28px"' : '' ?>><h2>Guías, reseñas y comparativas</h2></div>
    <div class="acards"><?php foreach ($posts as $post): partial('acard', ['post' => $post]); endforeach; ?></div>
  <?php endif; ?>
</div>
