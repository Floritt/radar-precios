<?php /** @var array $p @var array $errors @var array $manual @var array $niches */ ?>
<p class="back"><a href="<?= e(url('admin/productos')) ?>">Volver a productos</a></p>
<div class="head">
  <h1><?= $p['id'] ? e($p['name']) : 'Nuevo producto' ?></h1>
  <?php if ($p['id']): ?><a class="btn" href="<?= e(url('producto/' . $p['slug'])) ?>" target="_blank" rel="noopener">Ver ficha</a><?php endif; ?>
</div>
<?php if ($errors): ?><div class="adm-flash adm-flash--error"><?php foreach ($errors as $er): ?><p><?= e($er) ?></p><?php endforeach; ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="form">
  <?= csrf_field() ?>
  <section class="card">
    <h2>Datos del producto</h2>
    <label>Nombre <small>Como lo buscaría la gente: tipo de producto, característica clave y tamaño.</small><input name="name" required maxlength="200" value="<?= e($p['name']) ?>" data-slug-source></label>
    <label>Dirección web<input name="slug" value="<?= e($p['slug']) ?>" data-slug-target pattern="[a-z0-9\-]*"></label>
    <div class="row">
      <label>Nicho
        <select name="niche_id">
          <option value="">Sin nicho</option>
          <?php foreach ($niches as $n): ?><option value="<?= (int) $n['id'] ?>"<?= (int) $p['niche_id'] === (int) $n['id'] ? ' selected' : '' ?>><?= e($n['name']) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Marca<input name="brand" maxlength="120" value="<?= e($p['brand']) ?>"></label>
    </div>
    <label>Lo que hay que saber <small>Texto breve para la ficha. Admite Markdown: **negrita**, listas con guiones.</small><textarea name="summary" rows="5"><?= e($p['summary']) ?></textarea></label>
    <label>Foto propia <small>Opcional. JPG, PNG o WebP de hasta 4 MB. Si no subes ninguna, se usa la de Amazon cuando la API esté activa.</small><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
    <?php if (!empty($p['image_path'])): ?>
      <div class="thumb"><img src="<?= e(upload_url($p['image_path'])) ?>" alt=""><label class="check"><input type="checkbox" name="remove_image" value="1"> Quitar foto</label></div>
    <?php endif; ?>
  </section>

  <section class="card">
    <h2>Tiendas</h2>
    <label>Amazon: enlace o ASIN <small>Pega la dirección de la ficha de Amazon.es; se guarda el código de 10 caracteres.</small><input name="asin" value="<?= e($p['asin']) ?>" placeholder="https://www.amazon.es/dp/B0XXXXXXXX" data-asin></label>
    <p class="hint" data-asin-hint></p>
    <?php if (!empty($p['asin']) && $p['id']): ?>
      <div class="amazon-state">
        <?php if ($p['amazon_checked_at']): ?>
          <p><strong>Última consulta a Amazon:</strong> <?= e(date_short($p['amazon_checked_at'])) ?>.
            <?= $p['amazon_price'] !== null ? 'Precio: ' . e(money($p['amazon_price'])) . '.' : '' ?>
            <?= $p['amazon_error'] ? '<br><span class="err">' . e($p['amazon_error']) . '</span>' : '' ?></p>
          <?php if ($p['amazon_title']): ?><p class="muted">Título en Amazon: <?= e($p['amazon_title']) ?></p><?php endif; ?>
        <?php else: ?>
          <p class="muted">Amazon aún no se ha consultado para este producto.</p>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <?php if ($p['temu_url']): ?><label>Temu: enlace de afiliado<input name="temu_url" type="url" value="<?= e($p['temu_url']) ?>"></label><?php else: ?><input type="hidden" name="temu_url" value=""><?php endif; ?>
    <?php if ($p['shein_url']): ?><label>Shein: enlace de afiliado<input name="shein_url" type="url" value="<?= e($p['shein_url']) ?>"></label><?php else: ?><input type="hidden" name="shein_url" value=""><?php endif; ?>
    <div class="row">
      <label>Otra tienda: nombre<input name="other_name" maxlength="80" value="<?= e($p['other_name']) ?>" placeholder="PcComponentes"></label>
      <label>Otra tienda: enlace<input name="other_url" type="url" value="<?= e($p['other_url']) ?>"></label>
    </div>
  </section>

  <section class="card">
    <h2>Publicación</h2>
    <label class="check"><input type="checkbox" name="track" value="1"<?= $p['track'] ? ' checked' : '' ?>> Seguir el precio de Amazon cada día</label>
    <label class="check"><input type="radio" name="status" value="draft"<?= !in_array($p['status'], ['published', 'unlisted'], true) ? ' checked' : '' ?>> Borrador: solo lo ves tú</label>
    <label class="check"><input type="radio" name="status" value="unlisted"<?= $p['status'] === 'unlisted' ? ' checked' : '' ?>> Oculto: se ve con el enlace, pero no en categorías ni en Google</label>
    <label class="check"><input type="radio" name="status" value="published"<?= $p['status'] === 'published' ? ' checked' : '' ?>> Publicado en la web</label>
  </section>

  <div class="actions sticky"><button class="btn btn--primary" type="submit">Guardar producto</button></div>
</form>

<?php if ($p['id']): ?>
  <?php if (!empty($p['asin'])): ?>
    <form method="post" action="<?= e(url('admin/productos/' . $p['id'] . '/amazon')) ?>" class="inline-form">
      <?= csrf_field() ?><button class="btn" type="submit">Consultar Amazon ahora</button>
    </form>
  <?php endif; ?>

  <section class="card" id="precios">
    <h2>Precios de otras tiendas</h2>
    <p class="muted">Estas tiendas no tienen API de precios abierta: apunta aquí el precio que ves y el día. Los precios de Amazon no se pueden escribir a mano; llegan solos desde su API.</p>
    <form method="post" action="<?= e(url('admin/productos/' . $p['id'] . '/precio')) ?>" class="form row row--end">
      <?= csrf_field() ?>
      <label>Tienda
        <select name="store">
          <?php if ($p['temu_url']): ?><option value="temu">Temu</option><?php endif; ?>
          <?php if ($p['shein_url']): ?><option value="shein">Shein</option><?php endif; ?>
          <?php if ($p['other_url']): ?><option value="other"><?= e($p['other_name'] ?: 'Otra tienda') ?></option><?php endif; ?>
          <?php if (!$p['temu_url'] && !$p['shein_url'] && !$p['other_url']): ?><option value="" disabled selected>Primero guarda un enlace de tienda</option><?php endif; ?>
        </select>
      </label>
      <label>Precio (€)<input name="price" inputmode="decimal" required placeholder="19,99"></label>
      <label>Día<input type="date" name="date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>"></label>
      <button class="btn btn--primary" type="submit">Añadir precio</button>
    </form>
    <?php if ($manual): ?>
      <table class="table">
        <thead><tr><th>Día</th><th>Tienda</th><th class="r">Precio</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($manual as $m): ?>
          <tr>
            <td><?= e(date('d/m/Y', strtotime($m['captured_at']))) ?></td>
            <td><?= e($m['store'] === 'other' ? ($p['other_name'] ?: 'Otra tienda') : STORES[$m['store']]) ?></td>
            <td class="r"><?= e(money($m['price'])) ?></td>
            <td class="r"><form method="post" action="<?= e(url('admin/precios/' . $m['id'] . '/borrar')) ?>" data-confirm="¿Borrar este precio?"><?= csrf_field() ?><button class="link-danger" type="submit">Borrar</button></form></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

  <form method="post" action="<?= e(url('admin/productos/' . $p['id'] . '/borrar')) ?>" class="danger-zone" data-confirm="Se borrará el producto y todo su historial de precios. ¿Seguro?">
    <?= csrf_field() ?><button class="btn btn--danger" type="submit">Borrar producto</button>
  </form>
<?php endif; ?>
