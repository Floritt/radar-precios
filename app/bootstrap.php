<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('APP', __DIR__);

$configFile = ROOT . '/config.php';
if (!is_file($configFile)) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Falta config.php. Ejecuta el instalador en /install/ o copia config.example.php.\n");
        exit(1);
    }
    header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/') . '/install/');
    exit;
}

$GLOBALS['config'] = require $configFile;

date_default_timezone_set(config('timezone', 'Europe/Madrid'));
mb_internal_encoding('UTF-8');

if (config('env') === 'local') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ROOT . '/storage/logs/php-' . date('Y-m') . '.log');
}

/** Lee un valor de config.php con notación de puntos: config('amazon.partner_tag'). */
function config(string $key, $default = null)
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

require APP . '/db.php';
require APP . '/helpers.php';
require APP . '/auth.php';
require APP . '/prices.php';
require APP . '/amazon.php';
require APP . '/telegram.php';
require APP . '/markdown.php';
require APP . '/seo.php';
