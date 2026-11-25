<?php
/**
 * Descarga los precios de todas las gasolineras del Ministerio, guarda el precio del día de cada una,
 * recalcula las medias por provincia y envía los avisos que toque.
 * php cron/gasolina.php [--archivo=ruta.json] [--dia=AAAA-MM-DD]
 * Pensado para ejecutarse cada hora. --archivo y --dia sirven para pruebas.
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
set_time_limit(600);

$file = null;
$day = date('Y-m-d');
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--archivo=')) {
        $file = substr($arg, 10);
    }
    if (str_starts_with($arg, '--dia=') && preg_match('/^\d{4}-\d{2}-\d{2}$/', substr($arg, 6))) {
        $day = substr($arg, 6);
    }
}

$lock = fopen(ROOT . '/storage/gasolina.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "Ya hay una importación en marcha\n";
    exit;
}

if ($file) {
    $raw = (string) file_get_contents($file);
} else {
    $ch = curl_init(FUEL_SOURCE_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 180,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_USERAGENT => 'CuandoComprar/1.0 (+' . absolute_url('gasolina') . ')',
        CURLOPT_ENCODING => '',
    ]);
    $raw = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($code !== 200) {
        save_setting('fuel_last_result', "Error HTTP $code al descargar");
        echo "Error HTTP $code\n";
        exit(1);
    }
}
$data = json_decode($raw, true);
unset($raw);
$list = $data['ListaEESSPrecio'] ?? [];
if (count($list) < ($file ? 1 : 5000)) {
    save_setting('fuel_last_result', 'Respuesta incompleta: ' . count($list) . ' gasolineras');
    echo 'Respuesta incompleta: ', count($list), "\n";
    exit(1);
}

$num = function ($v): ?float {
    $v = trim((string) $v);
    if ($v === '') {
        return null;
    }
    $f = (float) str_replace(',', '.', $v);
    return $f > 0.3 && $f < 5 ? round($f, 3) : null; // Fuera de este rango es un error de la fuente.
};

$stations = [];
$munis = [];
$at = now();
foreach ($list as $r) {
    if (($r['Tipo Venta'] ?? 'P') !== 'P') {
        continue; // Venta restringida a socios o flotas: no la puede usar cualquiera.
    }
    $id = (int) ($r['IDEESS'] ?? 0);
    $muni = (int) ($r['IDMunicipio'] ?? 0);
    $prov = (int) ($r['IDProvincia'] ?? 0);
    if ($id <= 0 || $muni <= 0 || !isset(FUEL_PROVINCES[$prov])) {
        continue;
    }
    $row = [
        'id' => $id,
        'name' => mb_substr(fuel_tidy((string) ($r['Rótulo'] ?? '')) ?: 'Gasolinera', 0, 120),
        'address' => mb_substr(fuel_tidy((string) ($r['Dirección'] ?? '')), 0, 200),
        'cp' => mb_substr((string) ($r['C.P.'] ?? ''), 0, 5),
        'locality' => mb_substr(fuel_tidy((string) ($r['Localidad'] ?? '')), 0, 120),
        'muni_id' => $muni,
        'province_id' => $prov,
        'lat' => trim((string) ($r['Latitud'] ?? '')) === '' ? null : (float) str_replace(',', '.', (string) $r['Latitud']),
        'lng' => trim((string) ($r['Longitud (WGS84)'] ?? '')) === '' ? null : (float) str_replace(',', '.', (string) $r['Longitud (WGS84)']),
        'schedule' => mb_substr((string) ($r['Horario'] ?? ''), 0, 200),
        'active' => 1,
        'updated_at' => $at,
    ];
    foreach (FUELS as $k => [, $field]) {
        $row[$k] = $num($r[$field] ?? '');
    }
    $stations[$id] = $row;
    if (!isset($munis[$muni])) {
        $munis[$muni] = ['id' => $muni, 'province_id' => $prov, 'name' => mb_substr(fuel_pretty_place((string) ($r['Municipio'] ?? '')), 0, 120), 'stations' => 0];
    }
    $munis[$muni]['stations']++;
}
unset($list, $data);

// Slugs de municipio únicos dentro de cada provincia.
$taken = [];
ksort($munis);
foreach ($munis as &$m) {
    $slug = slugify($m['name']) ?: 'municipio';
    $key = $m['province_id'] . '/' . $slug;
    if (isset($taken[$key])) {
        $slug .= '-' . $m['id'];
    }
    $taken[$m['province_id'] . '/' . $slug] = true;
    $m['slug'] = $slug;
}
unset($m);

$pdo = db();
$pdo->beginTransaction();
try {
    fuel_upsert('fuel_stations', ['id', 'name', 'address', 'cp', 'locality', 'muni_id', 'province_id', 'lat', 'lng', 'schedule', 'g95', 'diesel', 'g98', 'diesel_plus', 'glp', 'active', 'updated_at'], ['id'], array_values($stations));
    // Las que ya no aparecen en el listado se dejan de mostrar.
    q('UPDATE fuel_stations SET active = 0 WHERE updated_at < ?', [$at]);
    q('UPDATE fuel_munis SET stations = 0');
    fuel_upsert('fuel_munis', ['id', 'province_id', 'name', 'slug', 'stations'], ['id'], array_values($munis));
    // Precio del día de cada gasolinera: la última lectura del día es la que queda.
    $daily = [];
    foreach ($stations as $s) {
        if ($s['g95'] !== null || $s['diesel'] !== null || $s['g98'] !== null) {
            $daily[] = ['station_id' => $s['id'], 'day' => $day, 'g95' => $s['g95'], 'diesel' => $s['diesel'], 'g98' => $s['g98']];
        }
    }
    fuel_upsert('fuel_daily', ['station_id', 'day', 'g95', 'diesel', 'g98'], ['station_id', 'day'], $daily);
    // Medias del día por provincia y de España (provincia 0).
    $stats = [];
    foreach (FUELS_MAIN as $f) {
        $acc = [];
        foreach ($stations as $s) {
            if ($s[$f] === null) {
                continue;
            }
            foreach ([0, $s['province_id']] as $p) {
                $acc[$p]['sum'] = ($acc[$p]['sum'] ?? 0) + $s[$f];
                $acc[$p]['n'] = ($acc[$p]['n'] ?? 0) + 1;
                $acc[$p]['min'] = min($acc[$p]['min'] ?? 99, $s[$f]);
            }
        }
        foreach ($acc as $p => $a) {
            $stats[] = ['day' => $day, 'province_id' => $p, 'fuel' => $f, 'avg' => round($a['sum'] / $a['n'], 3), 'min' => $a['min'], 'stations' => $a['n']];
        }
    }
    fuel_upsert('fuel_stats', ['day', 'province_id', 'fuel', 'avg', 'min', 'stations'], ['day', 'province_id', 'fuel'], $stats);
    q('DELETE FROM fuel_daily WHERE day < ?', [date('Y-m-d', strtotime($day) - 400 * 86400)]);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    save_setting('fuel_last_result', 'Error: ' . mb_substr($e->getMessage(), 0, 200));
    echo 'Error: ', $e->getMessage(), "\n";
    exit(1);
}

$sent = fuel_check_alerts();
$sentZone = fuel_check_zone_alerts();
$result = count($stations) . ' gasolineras, ' . count($munis) . ' municipios, avisos enviados: ' . $sent . ' de gasolinera y ' . $sentZone . ' de zona';
save_setting('fuel_last_run', now());
save_setting('fuel_last_result', $result);
echo "Día $day: $result\n";
