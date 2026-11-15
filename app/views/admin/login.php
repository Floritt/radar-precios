<?php /** @var ?string $error */ ?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Entrar | <?= e(site_name()) ?></title>
<link rel="icon" href="<?= e(asset('img/icon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="adm adm-login">
<main class="login-card">
  <h1><?= e(site_name()) ?></h1>
  <p>Entra para gestionar productos, precios y artículos.</p>
  <?php if ($error): ?><p class="adm-flash adm-flash--error" role="alert"><?= e($error) ?></p><?php endif; ?>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <label>Correo electrónico<input type="email" name="email" required autocomplete="username" value="<?= e(input('email')) ?>"></label>
    <label>Contraseña<input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn btn--primary" type="submit">Entrar</button>
  </form>
</main>
</body>
</html>
