<?php /** @var array $errors @var array $form @var string $token */ ?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Crear tu cuenta | <?= e(site_name()) ?></title>
<link rel="icon" href="<?= e(asset('img/icon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="adm adm-login">
<main class="login-card">
  <h1>Crea tu cuenta</h1>
  <p>Con este correo y contraseña entrarás al panel. El enlace deja de funcionar en cuanto la crees.</p>
  <?php if ($errors): ?><div class="adm-flash adm-flash--error" role="alert"><?php foreach ($errors as $er): ?><p><?= e($er) ?></p><?php endforeach; ?></div><?php endif; ?>
  <form method="post" class="form" action="<?= e(url('admin/crear-cuenta') . '?token=' . rawurlencode($token)) ?>">
    <?= csrf_field() ?>
    <label>Tu nombre <small>Sale como autor de los artículos.</small><input name="name" value="<?= e($form['name']) ?>" autocomplete="name"></label>
    <label>Correo electrónico<input type="email" name="email" required autocomplete="username" value="<?= e($form['email']) ?>"></label>
    <label>Contraseña <small>Mínimo 10 caracteres.</small><input type="password" name="password" required minlength="10" autocomplete="new-password"></label>
    <label>Repite la contraseña<input type="password" name="password2" required minlength="10" autocomplete="new-password"></label>
    <button class="btn btn--primary" type="submit">Crear cuenta</button>
  </form>
</main>
</body>
</html>
