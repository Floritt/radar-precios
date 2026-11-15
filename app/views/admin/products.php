<?php /** @var array $products @var int $nicheId @var string $q */ ?>
<div class="head">
  <h1>Productos</h1>
  <a class="btn btn--primary" href="<?= e(url('admin/productos/nuevo' . ($nicheId ? '?nicho=' . $nicheId : ''))) ?>">Añadir producto</a>
</div>
<form method="get" class="filters">
  <select name="nicho" data-autosubmit>
    <option value="">Todos los nichos</option>
    <?php foreach (all_niches() as $n): ?><option value="<?= (int) $n['id'] ?>"<?= $nicheId === (int) $n['id'] ? ' selected' : '' ?>><?= e($n['name']) ?></option><?php endforeach; ?>
  </select>
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Nombre o ASIN">
  <button class="btn" type="submit">Filtrar</button>
</form>
<div class="table-wrap">
<table class="table">
  <thead><tr><th>Producto</th><th>Nicho</th><th>Mejor precio</th><th>Amazon</th><th>Estado</th></tr></thead>
  <tbody>
  <?php foreach ($products as $p): ?>
    <tr>
      <td><a href="<?= e(url('admin/productos/' . $p['id'])) ?>"><?= e($p['name']) ?></a><?php if ($p['is_demo']): ?> <span class="pill pill--warn">ejemplo</span><?php endif; ?><?php if ($p['at_low']): ?> <span class="pill pill--promo">mínimo</span><?php endif; ?></td>
      <td><?php if ($p['niche_name']): ?><span class="swatch" style="background: <?= e($p['niche_color']) ?>"></span> <?= e($p['niche_name']) ?><?php endif; ?></td>
      <td><?= $p['best_price'] !== null ? e(money($p['best_price'])) . ' <span class="muted">' . e(STORES[$p['best_store']] ?? '') . '</span>' : '<span class="muted">sin precio</span>' ?></td>
      <td class="muted"><?= $p['asin'] ? e($p['asin']) . ($p['amazon_error'] ? ' <span class="pill pill--err" title="' . e($p['amazon_error']) . '">aviso</span>' : '') : 'sin ASIN' ?></td>
      <td><?= $p['status'] === 'published' ? 'Publicado' : '<span class="muted">Borrador</span>' ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$products): ?><tr><td colspan="5" class="muted">No hay productos con estos filtros.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
