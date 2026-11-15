<?php
/**
 * Instalación desde consola o tarea cron, sin pasar por el navegador.
 * php install/cli.php --db-name=... --db-user=... --db-pass=... --base-url=https://... [--author=Joan] [--demo]
 * No crea el usuario del panel: deja un enlace de alta de un solo uso para que la dueña elija su contraseña.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
date_default_timezone_set('Europe/Madrid');
$root = dirname(__DIR__);
if (is_file($root . '/storage/install.lock')) {
    fwrite(STDERR, "Ya instalado (storage/install.lock existe).\n");
    exit(1);
}
$opt = getopt('', ['db-name:', 'db-user:', 'db-pass:', 'db-host::', 'db-port::', 'base-url:', 'site-name::', 'author::', 'demo']);
foreach (['db-name', 'db-user', 'db-pass', 'base-url'] as $req) {
    if (empty($opt[$req])) {
        fwrite(STDERR, "Falta --$req\n");
        exit(1);
    }
}
require __DIR__ . '/lib.php';
try {
    $token = install_run([
        'base_url' => $opt['base-url'], 'driver' => 'mysql',
        'db_host' => $opt['db-host'] ?? 'localhost', 'db_port' => $opt['db-port'] ?? '3306',
        'db_name' => $opt['db-name'], 'db_user' => $opt['db-user'], 'db_pass' => $opt['db-pass'],
        'site_name' => $opt['site-name'] ?? 'Radar de Precios', 'author' => $opt['author'] ?? '', 'demo' => isset($opt['demo']),
    ], null);
} catch (Throwable $e) {
    fwrite(STDOUT, 'ERROR: ' . preg_replace('/password[^,;)]*/i', 'password=***', $e->getMessage()) . "\n");
    exit(1);
}
echo "OK instalado. PHP " . PHP_VERSION . "\n";
if ($token) {
    echo 'ALTA: ' . rtrim($opt['base-url'], '/') . '/admin/crear-cuenta?token=' . $token . "\n";
}
