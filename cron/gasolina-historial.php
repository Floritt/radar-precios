<?php
/**
 * Rellena el historial de precios con los datos históricos del Ministerio (EstacionesTerrestresHist/dd-mm-aaaa):
 * el precio de cada gasolinera cada día y las medias por provincia y de España. No toca el listado de hoy.
 * php cron/gasolina-historial.php [--desde=2026-01-01] [--hasta=AAAA-MM-DD] [--dias=5] [--carpeta=ruta]
 * Cada pasada procesa --dias días a partir del siguiente pendiente (se guarda en fuel_hist_next), así que se puede
 * programar cada pocos minutos hasta que diga «Historial completo». Los días que ya tienen medias se saltan.
 * --carpeta lee AAAA-MM-DD.json de una carpeta en lugar de descargarlos (pruebas).
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
chdir(dirname(__DIR__));
require 'app/bootstrap.php';
ensure_schema();
ini_set('memory_limit', '768M');
set_time_limit(900);

$from = date('Y') . '-01-01';
$to = date('Y-m-d', strtotime('yesterday'));
$perRun = 5;
$dir = null;
foreach ($argv as $arg) {
    if (preg_match('/^--desde=(\d{4}-\d{2}-\d{2})$/', $arg, $m)) {
        $from = $m[1];
    }
    if (preg_match('/^--hasta=(\d{4}-\d{2}-\d{2})$/', $arg, $m)) {
        $to = $m[1];
    }
    if (preg_match('/^--dias=(\d{1,2})$/', $arg, $m)) {
        $perRun = max(1, (int) $m[1]);
    }
    if (str_starts_with($arg, '--carpeta=')) {
        $dir = rtrim(substr($arg, 10), '/');
    }
}

$lock = fopen(ROOT . '/storage/gasolina-historial.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "Ya hay una pasada en marcha\n";
    exit;
}

$next = (string) setting('fuel_hist_next', '');
if ($next === '' || $next < $from) {
    $next = $from;
}
$done = 0;
while ($done < $perRun && $next <= $to) {
    $day = $next;
    if (val('SELECT 1 FROM fuel_stats WHERE day = ? AND province_id = 0 LIMIT 1', [$day])) {
        echo "$day ya estaba\n";
    } else {
        if ($dir) {
            $raw = is_file("$dir/$day.json") ? (string) file_get_contents("$dir/$day.json") : '';
            $code = $raw === '' ? 404 : 200;
        } else {
            $ch = curl_init(str_replace('/EstacionesTerrestres/', '/EstacionesTerrestresHist/', FUEL_SOURCE_URL) . date('d-m-Y', strtotime($day)));
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 240,
                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
                CURLOPT_USERAGENT => 'CuandoComprar/1.0 (+' . absolute_url('gasolina') . ')',
                CURLOPT_ENCODING => '',
            ]);
            $raw = (string) curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
        }
        $list = $code === 200 ? (json_decode($raw, true)['ListaEESSPrecio'] ?? []) : [];
        unset($raw);
        if (count($list) < ($dir ? 1 : 5000)) {
            // Se reintenta en la siguiente pasada; a los 3 fallos seguidos el día se salta.
            $fails = (int) setting('fuel_hist_fail', '0') + 1;
            echo "$day: error (HTTP $code, " . count($list) . " gasolineras), intento $fails\n";
            if ($fails < 3) {
                save_setting('fuel_hist_fail', (string) $fails);
                break;
            }
            echo "$day: se salta\n";
        } else {
            [$daily, $stats] = fuel_build_day($list, $day);
            unset($list);
            fuel_upsert('fuel_daily', ['station_id', 'day', 'g95', 'diesel', 'g98'], ['station_id', 'day'], $daily);
            fuel_upsert('fuel_stats', ['day', 'province_id', 'fuel', 'avg', 'min', 'stations'], ['day', 'province_id', 'fuel'], $stats);
            $es = array_values(array_filter($stats, fn ($r) => $r['province_id'] === 0 && $r['fuel'] === 'g95'));
            echo "$day: ", count($daily), ' gasolineras', $es ? ', gasolina 95 a ' . number_format((float) $es[0]['avg'], 3, ',', '') : '', "\n";
        }
    }
    save_setting('fuel_hist_fail', '0');
    $next = date('Y-m-d', strtotime($day . ' +1 day'));
    save_setting('fuel_hist_next', $next);
    $done++;
}
if ($next > $to) {
    echo "Historial completo hasta $to\n";
}
