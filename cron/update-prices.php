<?php
/**
 * Actualiza los precios. Prográmalo en Hostinger (hPanel > Avanzado > Cron Jobs), por ejemplo cada hora:
 *   php /home/TU_USUARIO/domains/TU_DOMINIO/public_html/cron/update-prices.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo se ejecuta desde el cron del servidor.');
}

require dirname(__DIR__) . '/app/bootstrap.php';
require APP . '/cron.php';

$max = isset($argv[1]) ? (int) $argv[1] : 0;
echo run_price_update($max) . PHP_EOL;
