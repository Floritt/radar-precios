<?php
/**
 * Comprobaciones de mantenimiento desde consola: php cron/ops.php
 * Muestra el estado de la instalación y, si aún no hay usuario del panel, un enlace de alta de un solo uso.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
chdir(dirname(__DIR__));
require 'app/bootstrap.php';

$tokenFile = 'storage/setup_token.txt';
if ((int) val('SELECT COUNT(*) FROM users') === 0) {
    if (!is_file($tokenFile)) {
        $t = bin2hex(random_bytes(20));
        file_put_contents($tokenFile, $t);
        save_setting('setup_token_hash', hash('sha256', $t));
    }
    echo 'ALTA ', rtrim((string) config('base_url'), '/'), '/admin/crear-cuenta?token=', trim((string) file_get_contents($tokenFile)), "\n";
} else {
    @unlink($tokenFile);
    echo "Usuario del panel ya creado\n";
}

ensure_schema();
echo 'Esquema v', setting('schema_version'), ' | avisos activos ', val("SELECT COUNT(*) FROM alerts WHERE status = 'active'"), ' | peticiones en cola ', val("SELECT COUNT(*) FROM track_requests WHERE status = 'pending'"), "\n";
echo 'PHP ', PHP_VERSION, ' | productos ', val('SELECT COUNT(*) FROM products'), ' | nichos ', val('SELECT COUNT(*) FROM niches'),
    ' | ultima tarea de precios: ', setting('cron_last_run', 'nunca'), "\n";

$base = rtrim((string) config('base_url'), '/');
foreach (['/', '/seguir', '/producto/dixit', '/blog', '/comparativas', '/admin/entrar', '/config.php', '/storage/install.lock', '/install/cli.php', '/.git/config'] as $u) {
    $h = @get_headers($base . $u);
    echo $u, ' ', $h ? $h[0] : 'sin respuesta', "\n";
}
