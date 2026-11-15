<?php /** @var array $errors @var array $user */ ?>
<h1>Mi cuenta</h1>
<?php if ($errors): ?><div class="adm-flash adm-flash--error"><?php foreach ($errors as $er): ?><p><?= e($er) ?></p><?php endforeach; ?></div><?php endif; ?>
<form method="post" class="form card narrow">
  <?= csrf_field() ?>
  <p class="muted">Has entrado como <?= e($user['email']) ?>.</p>
  <label>Tu nombre <small>Aparece como autor de los artículos nuevos.</small><input name="name" value="<?= e($user['name']) ?>"></label>
  <label>Contraseña actual<input type="password" name="current" required autocomplete="current-password"></label>
  <label>Nueva contraseña <small>Mínimo 10 caracteres.</small><input type="password" name="new" required minlength="10" autocomplete="new-password"></label>
  <div class="actions"><button class="btn btn--primary" type="submit">Guardar</button></div>
</form>
