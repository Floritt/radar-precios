<?php /** @var array $post @var array $errors @var array $niches @var array $products */
$isReview = $post['type'] === 'review';
$publishedLocal = $post['published_at'] ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : '';
?>
<p class="back"><a href="<?= e(url('admin/articulos')) ?>">Volver a artículos</a></p>
<div class="head">
  <h1><?= $post['id'] ? 'Editar' : ($isReview ? 'Nueva reseña' : 'Nuevo artículo') ?></h1>
  <?php if ($post['id']): ?><a class="btn" href="<?= e(post_url($post)) ?>" target="_blank" rel="noopener"><?= $post['status'] === 'published' ? 'Ver en la web' : 'Vista previa' ?></a><?php endif; ?>
</div>
<?php if ($errors): ?><div class="adm-flash adm-flash--error"><?php foreach ($errors as $er): ?><p><?= e($er) ?></p><?php endforeach; ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="form editor">
  <?= csrf_field() ?>
  <div class="editor__main">
    <section class="card">
      <label>Título<input name="title" required maxlength="220" value="<?= e($post['title']) ?>" data-slug-source class="input-lg"></label>
      <label>Dirección web<input name="slug" value="<?= e($post['slug']) ?>" data-slug-target pattern="[a-z0-9\-]*"></label>
      <label>Entradilla <small>Dos o tres frases. Aparece bajo el título, en los listados y en Google.</small><textarea name="excerpt" rows="3"><?= e($post['excerpt']) ?></textarea></label>
      <label>Texto
        <small>Markdown: ## Subtítulo, **negrita**, - listas, [enlace](https://...). Para insertar un producto con su precio y botones, escribe en una línea aparte <code>[producto:direccion-del-producto]</code>. Para una tabla comparativa: <code>[comparativa:producto-uno,producto-dos]</code>.</small>
        <textarea name="body" rows="24" class="mono"><?= e($post['body']) ?></textarea>
      </label>
    </section>

    <section class="card" data-review-only<?= $isReview ? '' : ' hidden' ?>>
      <h2>Resumen de la reseña</h2>
      <div class="row">
        <label>Nota sobre 10<input name="rating" inputmode="decimal" value="<?= $post['rating'] !== null ? e(str_replace('.', ',', (string) (float) $post['rating'])) : '' ?>" placeholder="8,5"></label>
        <label>Producto analizado
          <select name="product_id">
            <option value="">Ninguno</option>
            <?php foreach ($products as $pr): ?><option value="<?= (int) $pr['id'] ?>"<?= (int) $post['product_id'] === (int) $pr['id'] ? ' selected' : '' ?>><?= e($pr['name']) ?></option><?php endforeach; ?>
          </select>
        </label>
      </div>
      <label>Veredicto en una frase<textarea name="verdict" rows="2"><?= e($post['verdict']) ?></textarea></label>
      <div class="row">
        <label>Lo mejor <small>Una idea por línea</small><textarea name="pros" rows="5"><?= e($post['pros']) ?></textarea></label>
        <label>Lo peor <small>Una idea por línea</small><textarea name="cons" rows="5"><?= e($post['cons']) ?></textarea></label>
      </div>
    </section>
  </div>

  <aside class="editor__side">
    <section class="card">
      <label>Tipo
        <select name="type" data-type-select>
          <?php foreach (POST_TYPES as $k => $label): ?><option value="<?= e($k) ?>"<?= $post['type'] === $k ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Nicho
        <select name="niche_id">
          <option value="">Sin nicho</option>
          <?php foreach ($niches as $n): ?><option value="<?= (int) $n['id'] ?>"<?= (int) $post['niche_id'] === (int) $n['id'] ? ' selected' : '' ?>><?= e($n['name']) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Autor<input name="author" maxlength="120" value="<?= e($post['author']) ?>"></label>
      <label>Fecha de publicación <small>Si pones una fecha futura, se publica sola ese día.</small><input type="datetime-local" name="published_at" value="<?= e($publishedLocal) ?>"></label>
      <label class="check"><input type="radio" name="status" value="draft"<?= $post['status'] !== 'published' ? ' checked' : '' ?>> Borrador</label>
      <label class="check"><input type="radio" name="status" value="published"<?= $post['status'] === 'published' ? ' checked' : '' ?>> Publicado</label>
    </section>
    <section class="card">
      <label>Imagen de portada <small>Horizontal, mínimo 1200 px de ancho. Opcional.</small><input type="file" name="cover" accept="image/jpeg,image/png,image/webp"></label>
      <?php if ($post['cover_path']): ?><div class="thumb"><img src="<?= e(upload_url($post['cover_path'])) ?>" alt=""><label class="check"><input type="checkbox" name="remove_cover" value="1"> Quitar portada</label></div><?php endif; ?>
    </section>
    <div class="actions"><button class="btn btn--primary btn--block" type="submit">Guardar</button></div>
  </aside>
</form>

<?php if ($post['id']): ?>
  <form method="post" action="<?= e(url('admin/articulos/' . $post['id'] . '/borrar')) ?>" class="danger-zone" data-confirm="¿Borrar este artículo?">
    <?= csrf_field() ?><button class="btn btn--danger" type="submit">Borrar</button>
  </form>
<?php endif; ?>
