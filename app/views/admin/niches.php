<?php /** @var array $niches */ ?>
<div class="head">
  <h1>Nichos</h1>
  <a class="btn btn--primary" href="<?= e(url('admin/nichos/nuevo')) ?>">Añadir nicho</a>
</div>
<p class="muted">Cada nicho tiene su color, que identifica sus etiquetas de precio en la web. El orden decide cómo aparecen en el menú.</p>
<table class="table">
  <thead><tr><th>Nombre</th><th>Dirección</th><th class="r">Productos</th><th class="r">Orden</th></tr></thead>
  <tbody>
  <?php foreach ($niches as $n): ?>
    <tr>
      <td><span class="swatch" style="background: <?= e($n['color']) ?>"></span> <a href="<?= e(url('admin/nichos/' . $n['id'])) ?>"><?= e($n['name']) ?></a></td>
      <td class="muted">/nicho/<?= e($n['slug']) ?></td>
      <td class="r"><a href="<?= e(url('admin/productos?nicho=' . $n['id'])) ?>"><?= (int) $n['product_count'] ?></a></td>
      <td class="r"><?= (int) $n['sort_order'] ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
