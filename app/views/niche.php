<?php
/** @var array $niche @var array $products @var array $posts @var string $sort */
?>
<div class="page-head">
  <div class="wrap page-head__row">
    <div class="page-head__icon">
      <?= niche_icon($niche['slug']) ?>
      <div>
        <h1><?= e($niche['name']) ?></h1>
        <?php if ($niche['intro']): ?><p><?= e($niche['intro']) ?></p><?php endif; ?>
      </div>
    </div>
    <?php if (count(array_filter($products, fn($x) => $x['best_price'] !== null)) > 1): ?>
    <form method="get" class="sortform">
      <label for="orden"><?= count($products) ?> productos, ordenar por</label>
      <select id="orden" name="orden" data-autosubmit>
        <option value="bajada"<?= $sort === 'bajada' ? ' selected' : '' ?>>Mayor bajada</option>
        <option value="precio"<?= $sort === 'precio' ? ' selected' : '' ?>>Precio más bajo</option>
        <option value="nombre"<?= $sort === 'nombre' ? ' selected' : '' ?>>Nombre</option>
      </select>
      <noscript><button type="submit">Aplicar</button></noscript>
    </form>
    <?php endif; ?>
  </div>
</div>
<div class="wrap page">
  <?php if ($posts): ?>
  <section class="block">
    <h2>Guías y comparativas</h2>
    <div class="acards"><?php foreach ($posts as $post): partial('acard', ['post' => $post, 'label' => 'type']); endforeach; ?></div>
  </section>
  <?php endif; ?>

  <?php if ($products): ?>
  <section class="block">
    <?php if (($niche['slug'] ?? '') === CAR_NICHE): usort($products, fn ($a, $b) => ($b['slug'] === 'baliza-v16-conectada') <=> ($a['slug'] === 'baliza-v16-conectada')); ?>
    <h2>Accesorios</h2>
    <div class="car-grid car-grid--strip"><?php foreach ($products as $p): partial('car-card', ['p' => $p]); endforeach; ?></div>
    <?php else: ?>
    <h2>Productos que seguimos</h2>
    <div class="grid"><?php foreach ($products as $p): partial('pcard', ['p' => $p]); endforeach; ?></div>
    <?php endif; ?>
  </section>
  <?php elseif (!$posts): ?>
    <p class="empty">Todavía no hay nada en esta categoría.</p>
  <?php endif; ?>
</div>
