<?php /** @var array $niche @var array $errors */ ?>
<p class="back"><a href="<?= e(url('admin/nichos')) ?>">Volver a nichos</a></p>
<h1><?= $niche['id'] ? 'Editar nicho' : 'Nuevo nicho' ?></h1>
<?php if ($errors): ?><div class="adm-flash adm-flash--error"><?php foreach ($errors as $er): ?><p><?= e($er) ?></p><?php endforeach; ?></div><?php endif; ?>
<form method="post" class="form card">
  <?= csrf_field() ?>
  <label>Nombre<input name="name" required maxlength="120" value="<?= e($niche['name']) ?>" data-slug-source></label>
  <label>Dirección web <small>Se rellena sola. Solo letras, números y guiones.</small><input name="slug" value="<?= e($niche['slug']) ?>" data-slug-target pattern="[a-z0-9\-]*"></label>
  <label>Presentación <small>Una o dos frases. Aparece arriba en la página del nicho.</small><textarea name="intro" rows="3"><?= e($niche['intro']) ?></textarea></label>
  <div class="row">
    <label>Color<input type="color" name="color" value="<?= e($niche['color']) ?>"></label>
    <label>Orden en el menú<input type="number" name="sort_order" value="<?= (int) $niche['sort_order'] ?>"></label>
  </div>
  <div class="actions"><button class="btn btn--primary" type="submit">Guardar</button></div>
</form>
<?php if ($niche['id']): ?>
  <form method="post" action="<?= e(url('admin/nichos/' . $niche['id'] . '/borrar')) ?>" class="danger-zone" data-confirm="¿Borrar este nicho?">
    <?= csrf_field() ?><button class="btn btn--danger" type="submit">Borrar nicho</button>
  </form>
<?php endif; ?>
