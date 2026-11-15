<?php
/**
 * Instalador. Crea config.php, las tablas, tu usuario del panel y los nichos iniciales.
 * Cuando termina, se bloquea solo (storage/install.lock). Puedes borrar esta carpeta después.
 */
declare(strict_types=1);

date_default_timezone_set('Europe/Madrid');
$root = dirname(__DIR__);
$lockFile = $root . '/storage/install.lock';
$configFile = $root . '/config.php';

function h($v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$guessBase = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(str_replace('/install', '', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

$checks = [
    'PHP 8.1 o superior' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'Extensión PDO MySQL' => extension_loaded('pdo_mysql'),
    'Extensión cURL (para Amazon y Telegram)' => extension_loaded('curl'),
    'Extensión mbstring' => extension_loaded('mbstring'),
    'Permiso de escritura en la carpeta raíz (para crear config.php)' => is_writable($root) || is_file($configFile),
    'Permiso de escritura en storage/' => is_writable($root . '/storage'),
    'Permiso de escritura en uploads/' => is_writable($root . '/uploads'),
];

$errors = [];
$done = false;
$form = [
    'base_url' => $guessBase, 'driver' => 'mysql', 'db_host' => 'localhost', 'db_port' => '3306', 'db_name' => '', 'db_user' => '',
    'site_name' => 'Radar de Precios', 'name' => '', 'email' => '', 'demo' => '1',
];

if (is_file($lockFile)) {
    $locked = true;
} else {
    $locked = false;
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        foreach ($form as $k => $v) {
            $form[$k] = trim((string) ($_POST[$k] ?? ($k === 'demo' ? '' : $v)));
        }
        $dbPass = (string) ($_POST['db_pass'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if (!filter_var($form['base_url'], FILTER_VALIDATE_URL)) $errors[] = 'La dirección de la web no es válida. Debe empezar por https://';
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Escribe un correo válido para entrar al panel.';
        if (mb_strlen($password) < 10) $errors[] = 'La contraseña del panel debe tener al menos 10 caracteres.';
        if ($form['site_name'] === '') $errors[] = 'Escribe el nombre de la web.';
        $driver = $form['driver'] === 'sqlite' ? 'sqlite' : 'mysql';
        if ($driver === 'mysql' && ($form['db_name'] === '' || $form['db_user'] === '')) $errors[] = 'Faltan el nombre o el usuario de la base de datos.';

        if (!$errors) {
            try {
                require __DIR__ . '/lib.php';
                install_run([
                    'base_url' => $form['base_url'], 'driver' => $driver, 'db_host' => $form['db_host'], 'db_port' => $form['db_port'],
                    'db_name' => $form['db_name'], 'db_user' => $form['db_user'], 'db_pass' => $dbPass,
                    'site_name' => $form['site_name'], 'author' => $form['name'], 'demo' => $form['demo'] === '1',
                ], ['email' => $form['email'], 'password' => $password]);
                $done = true;
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                if ($e instanceof PDOException) {
                    $msg = 'No se pudo conectar con la base de datos. Revisa servidor, nombre, usuario y contraseña. Detalle: ' . $msg;
                }
                $errors[] = $msg;
            }
        }
    }
}
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Instalación</title>
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="adm">
<main class="adm-main" style="max-width: 760px">
  <h1>Instalar la web</h1>

<?php if ($locked && !$done): ?>
  <div class="card">
    <p>La web ya está instalada. Por seguridad, el instalador está bloqueado.</p>
    <p><a class="btn btn--primary" href="../admin">Ir al panel</a></p>
    <p class="muted">Puedes borrar la carpeta <code>install</code> del servidor.</p>
  </div>

<?php elseif ($done): ?>
  <div class="card">
    <h2>Instalación terminada</h2>
    <p>Ya puedes entrar al panel con tu correo y contraseña.</p>
    <p><a class="btn btn--primary" href="../admin">Entrar al panel</a> <a class="btn" href="../">Ver la web</a></p>
    <h2>Pendiente</h2>
    <ol>
            <li>Completa tus datos legales en Panel, Ajustes.</li>
      <li>Programa la tarea cron que actualiza los precios (lo explica el README).</li>
      <li>Cuando Amazon te dé acceso a Creators API, pon tus claves en <code>config.php</code>.</li>
    </ol>
  </div>

<?php else: ?>
  <section class="card">
    <h2>Comprobaciones del servidor</h2>
    <ul class="status">
      <?php foreach ($checks as $label => $ok): ?><li class="<?= $ok ? 'ok' : 'off' ?>"><?= h($label) ?>: <?= $ok ? 'correcto' : '<strong class="err">falta</strong>' ?></li><?php endforeach; ?>
    </ul>
  </section>

  <?php if ($errors): ?><div class="adm-flash adm-flash--error"><?php foreach ($errors as $er): ?><p><?= h($er) ?></p><?php endforeach; ?></div><?php endif; ?>

  <form method="post" class="form" autocomplete="off">
    <section class="card">
      <h2>La web</h2>
      <label>Nombre de la web<input name="site_name" value="<?= h($form['site_name']) ?>" required></label>
      <label>Dirección <small>Tal como la escribirá la gente, sin barra final.</small><input name="base_url" type="url" value="<?= h($form['base_url']) ?>" required></label>
    </section>

    <section class="card">
      <h2>Base de datos</h2>
      <p class="muted">En Hostinger: hPanel, Bases de datos, Bases de datos MySQL. Crea una y copia aquí sus datos. El servidor suele ser <code>localhost</code>.</p>
      <input type="hidden" name="driver" value="<?= h($form['driver']) ?>">
      <div class="row">
        <label>Servidor<input name="db_host" value="<?= h($form['db_host']) ?>"></label>
        <label>Puerto<input name="db_port" value="<?= h($form['db_port']) ?>"></label>
      </div>
      <label>Nombre de la base de datos<input name="db_name" value="<?= h($form['db_name']) ?>"></label>
      <div class="row">
        <label>Usuario<input name="db_user" value="<?= h($form['db_user']) ?>"></label>
        <label>Contraseña<input name="db_pass" type="password"></label>
      </div>
    </section>

    <section class="card">
      <h2>Tu acceso al panel</h2>
      <label>Tu nombre <small>Aparecerá como autor de los artículos.</small><input name="name" value="<?= h($form['name']) ?>"></label>
      <label>Correo<input name="email" type="email" value="<?= h($form['email']) ?>" required></label>
      <label>Contraseña <small>Mínimo 10 caracteres.</small><input name="password" type="password" minlength="10" required autocomplete="new-password"></label>
    </section>

    <section class="card">
      <label class="check"><input type="checkbox" name="demo" value="1"<?= $form['demo'] === '1' ? ' checked' : '' ?>> Cargar productos y artículos de ejemplo</label>
      <p class="muted">Sirven para ver cómo queda la web. Llevan precios inventados y un aviso amarillo visible para todos; se borran con un clic desde el panel.</p>
    </section>

    <button class="btn btn--primary" type="submit">Instalar</button>
  </form>
<?php endif; ?>
</main>
</body>
</html>
