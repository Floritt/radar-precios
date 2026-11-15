<?php /** @var array $posts @var string $type */ ?>
<div class="head">
  <h1>Artículos y reseñas</h1>
  <div class="btns">
    <a class="btn btn--primary" href="<?= e(url('admin/articulos/nuevo?tipo=review')) ?>">Nueva reseña</a>
    <a class="btn" href="<?= e(url('admin/articulos/nuevo?tipo=comparison')) ?>">Nueva comparativa</a>
    <a class="btn" href="<?= e(url('admin/articulos/nuevo')) ?>">Nuevo artículo</a>
  </div>
</div>
<nav class="tabs">
  <a href="<?= e(url('admin/articulos')) ?>"<?= $type === '' ? ' aria-current="page"' : '' ?>>Todos</a>
  <?php foreach (POST_TYPES as $k => $label): ?><a href="<?= e(url('admin/articulos?tipo=' . $k)) ?>"<?= $type === $k ? ' aria-current="page"' : '' ?>><?= e($label) ?></a><?php endforeach; ?>
</nav>
<div class="table-wrap">
<table class="table">
  <thead><tr><th>Título</th><th>Tipo</th><th>Nicho</th><th>Estado</th><th>Fecha</th></tr></thead>
  <tbody>
  <?php foreach ($posts as $po): ?>
    <tr>
      <td><a href="<?= e(url('admin/articulos/' . $po['id'])) ?>"><?= e($po['title']) ?></a><?php if ($po['is_demo']): ?> <span class="pill pill--warn">ejemplo</span><?php endif; ?></td>
      <td><?= e(POST_TYPES[$po['type']] ?? $po['type']) ?></td>
      <td class="muted"><?= e($po['niche_name'] ?? '') ?></td>
      <td><?= $po['status'] === 'published' ? ($po['published_at'] > now() ? 'Programado' : 'Publicado') : '<span class="muted">Borrador</span>' ?></td>
      <td class="muted"><?= e($po['published_at'] ? date('d/m/Y', strtotime($po['published_at'])) : '') ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$posts): ?><tr><td colspan="5" class="muted">Todavía no hay artículos.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
