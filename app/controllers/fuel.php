<?php
declare(strict_types=1);

/* ---------- Gasolina: portada, provincia, municipio, gasolinera, cerca de mí, búsqueda y avisos ---------- */

function fuel_require(): void
{
    if (!fuel_enabled()) {
        not_found();
    }
}

/** Última media de cada carburante en una zona y su cambio frente a hace 7 días. */
function fuel_today(int $province): array
{
    $day = (string) val('SELECT MAX(day) FROM fuel_stats WHERE province_id = ?', [$province]);
    $out = [];
    foreach (FUELS_MAIN as $f) {
        $now = $day ? fuel_avg_on($province, $f, substr($day, 0, 10)) : null;
        $week = $day ? fuel_avg_on($province, $f, date('Y-m-d', strtotime(substr($day, 0, 10)) - 7 * 86400)) : null;
        $out[$f] = ['avg' => $now, 'diff7' => $now !== null && $week !== null ? $now - $week : null];
    }
    return $out;
}

function fuel_home(): void
{
    fuel_require();
    $fuel = fuel_key((string) input('c', 'g95'));
    $day = substr((string) val('SELECT MAX(day) FROM fuel_stats WHERE province_id = 0'), 0, 10);
    $provinces = [];
    foreach (rows('SELECT province_id, avg, min, stations FROM fuel_stats WHERE day = ? AND fuel = ? AND province_id > 0 ORDER BY avg', [$day, $fuel]) as $r) {
        $provinces[] = $r + ['name' => FUEL_PROVINCES[(int) $r['province_id']] ?? ''];
    }
    $GLOBALS['track'] = ['fuel', 0];
    render('fuel-home', [
        'meta' => meta([
            'title' => 'Precio de la gasolina hoy en España, por provincia y gasolinera',
            'description' => 'Precio medio de la gasolina y el diésel hoy, las gasolineras más baratas de cada municipio y si es buen momento para repostar. Datos oficiales del Ministerio.',
            'canonical' => absolute_url('gasolina'),
            'jsonld' => [jsonld_breadcrumbs([['Inicio', ''], ['Gasolina', 'gasolina']])],
        ]),
        'fuel' => $fuel,
        'today' => fuel_today(0),
        'verdict' => fuel_verdict(0, $fuel),
        'chart' => fuel_series(0),
        'provinces' => $provinces,
        'cheapest' => fuel_stations('1 = 1', [], $fuel, 10),
        'updated' => setting('fuel_last_run'),
        'fuelChart' => true,
    ]);
}

function fuel_province(string $pslug): void
{
    fuel_require();
    $pid = fuel_province_by_slug($pslug);
    if (!$pid) {
        not_found();
    }
    $fuel = fuel_key((string) input('c', 'g95'));
    $name = FUEL_PROVINCES[$pid];
    $GLOBALS['track'] = ['fprov', $pid];
    render('fuel-place', [
        'meta' => meta([
            'title' => "Gasolineras más baratas en $name hoy",
            'description' => "Precio medio de la gasolina y el diésel en $name hoy y las gasolineras más baratas de la provincia, con datos oficiales actualizados cada hora.",
            'canonical' => absolute_url('gasolina/' . $pslug),
            'jsonld' => [jsonld_breadcrumbs([['Inicio', ''], ['Gasolina', 'gasolina'], [$name, 'gasolina/' . $pslug]])],
        ]),
        'kind' => 'province',
        'placeName' => $name,
        'pid' => $pid,
        'muni' => null,
        'fuel' => $fuel,
        'today' => fuel_today($pid),
        'national' => fuel_today(0),
        'verdict' => fuel_verdict($pid, $fuel),
        'chart' => fuel_series($pid),
        'stations' => fuel_stations('s.province_id = ?', [$pid], $fuel, 25),
        'stats' => fuel_area_stats('s.province_id = ?', [$pid], $fuel),
        'munis' => rows('SELECT * FROM fuel_munis WHERE province_id = ? AND stations > 0 ORDER BY name', [$pid]),
        'updated' => setting('fuel_last_run'),
        'fuelChart' => true,
    ]);
}

function fuel_muni(string $pslug, string $mslug): void
{
    fuel_require();
    $pid = fuel_province_by_slug($pslug);
    $m = $pid ? row('SELECT * FROM fuel_munis WHERE province_id = ? AND slug = ?', [$pid, $mslug]) : null;
    if (!$m) {
        not_found();
    }
    $fuel = fuel_key((string) input('c', 'g95'));
    $prov = FUEL_PROVINCES[$pid];
    $stations = fuel_stations('s.muni_id = ?', [(int) $m['id']], $fuel, 300);
    $GLOBALS['track'] = ['fmuni', (int) $m['id']];
    render('fuel-place', [
        'meta' => meta([
            'title' => "Gasolineras más baratas en {$m['name']} hoy",
            'description' => "Precio de la gasolina y el diésel hoy en las " . (int) $m['stations'] . " gasolineras de {$m['name']} ($prov), de la más barata a la más cara. Datos oficiales actualizados cada hora.",
            'canonical' => absolute_url('gasolina/' . $pslug . '/' . $mslug),
            'noindex' => (int) $m['stations'] === 0,
            'jsonld' => [jsonld_breadcrumbs([['Inicio', ''], ['Gasolina', 'gasolina'], [$prov, 'gasolina/' . $pslug], [$m['name'], 'gasolina/' . $pslug . '/' . $mslug]])],
        ]),
        'kind' => 'muni',
        'placeName' => $m['name'],
        'pid' => $pid,
        'muni' => $m,
        'fuel' => $fuel,
        'today' => fuel_today($pid),
        'national' => fuel_today(0),
        'verdict' => fuel_verdict($pid, $fuel),
        'chart' => null,
        'stations' => $stations,
        'stats' => fuel_area_stats('s.muni_id = ?', [(int) $m['id']], $fuel),
        'munis' => [],
        'updated' => setting('fuel_last_run'),
        'fuelChart' => false,
    ]);
}

function fuel_station(string $id): void
{
    fuel_require();
    $s = row('SELECT s.*, m.name AS muni_name, m.slug AS muni_slug FROM fuel_stations s LEFT JOIN fuel_munis m ON m.id = s.muni_id WHERE s.id = ?', [(int) $id]);
    if (!$s) {
        not_found();
    }
    $path = fuel_station_path($s);
    if (ltrim(request_path(), '/') !== $path) {
        header('Location: ' . url($path), true, 301);
        exit;
    }
    $history = ['labels' => [], 'series' => []];
    $vals = [];
    foreach (rows('SELECT day, g95, diesel, g98 FROM fuel_daily WHERE station_id = ? AND day >= ? ORDER BY day', [(int) $s['id'], date('Y-m-d', time() - 180 * 86400)]) as $r) {
        $history['labels'][] = substr((string) $r['day'], 0, 10);
        foreach (FUELS_MAIN as $f) {
            $vals[$f][] = $r[$f] === null ? null : (float) $r[$f];
        }
    }
    foreach (FUELS_MAIN as $f) {
        if (!empty($vals[$f]) && array_filter($vals[$f], fn ($v) => $v !== null)) {
            $history['series'][] = ['key' => $f, 'name' => fuel_name($f), 'values' => $vals[$f]];
        }
    }
    // Posición en su municipio para cada carburante: «la 2.ª más barata de 7».
    $rank = [];
    foreach (FUELS_MAIN as $f) {
        if ($s[$f] === null) {
            continue;
        }
        $r = row("SELECT SUM(CASE WHEN $f < ? THEN 1 ELSE 0 END) AS below, COUNT(*) AS n, AVG($f) AS avg FROM fuel_stations WHERE muni_id = ? AND active = 1 AND $f IS NOT NULL", [$s[$f], (int) $s['muni_id']]);
        $rank[$f] = ['pos' => (int) $r['below'] + 1, 'n' => (int) $r['n'], 'avg' => (float) $r['avg']];
    }
    $pslug = fuel_province_slug((int) $s['province_id']);
    $GLOBALS['track'] = ['fstat', (int) $s['id']];
    render('fuel-station', [
        'meta' => meta([
            'title' => "{$s['name']} en {$s['muni_name']}: precio de la gasolina hoy",
            'description' => "Precio de hoy de la gasolina y el diésel en {$s['name']}, {$s['address']} ({$s['muni_name']}), su historial y cómo está frente a las demás gasolineras del municipio.",
            'canonical' => absolute_url($path),
            'noindex' => !(int) $s['active'],
            'jsonld' => [jsonld_breadcrumbs([['Inicio', ''], ['Gasolina', 'gasolina'], [FUEL_PROVINCES[(int) $s['province_id']] ?? '', 'gasolina/' . $pslug], [(string) $s['muni_name'], 'gasolina/' . $pslug . '/' . $s['muni_slug']], [(string) $s['name'], $path]])],
        ]),
        's' => $s,
        'pslug' => $pslug,
        'rank' => $rank,
        'chart' => $history,
        'mailOn' => mail_enabled(),
        'fuelChart' => count($history['labels']) > 1,
    ]);
}

/** «Cerca de mí»: el navegador manda la posición, se usa para esta búsqueda y no se guarda. */
function fuel_near(): void
{
    fuel_require();
    $lat = (float) input('lat', '0');
    $lng = (float) input('lng', '0');
    $fuel = fuel_key((string) input('c', 'g95'));
    $radius = in_array((int) input('km', '10'), [5, 10, 25], true) ? (int) input('km', '10') : 10;
    $list = [];
    if ($lat > 27 && $lat < 44.5 && $lng > -18.5 && $lng < 4.5) {
        $dLat = $radius / 111;
        $dLng = $radius / (111 * max(0.2, cos(deg2rad($lat))));
        foreach (fuel_stations('s.lat BETWEEN ? AND ? AND s.lng BETWEEN ? AND ?', [$lat - $dLat, $lat + $dLat, $lng - $dLng, $lng + $dLng], $fuel, 600) as $s) {
            $km = fuel_km($lat, $lng, (float) $s['lat'], (float) $s['lng']);
            if ($km <= $radius) {
                $list[] = $s + ['km' => $km];
            }
        }
    }
    $GLOBALS['track'] = ['fnear', 0];
    header('X-Robots-Tag: noindex');
    render('fuel-near', [
        'meta' => meta(['title' => 'Gasolineras baratas cerca de ti', 'noindex' => true]),
        'stations' => array_slice($list, 0, 40),
        'fuel' => $fuel,
        'radius' => $radius,
        'lat' => $lat,
        'lng' => $lng,
        'valid' => $lat !== 0.0,
    ]);
}

function fuel_search(): void
{
    fuel_require();
    $q = trim(mb_substr((string) input('q', ''), 0, 80));
    record_search('gasolina', $q);
    $results = [];
    if (preg_match('/^\d{5}$/', $q)) {
        $m = row('SELECT m.* FROM fuel_stations s JOIN fuel_munis m ON m.id = s.muni_id WHERE s.cp = ? AND s.active = 1 LIMIT 1', [$q]);
        if ($m) {
            redirect(fuel_muni_path($m));
        }
    } elseif (mb_strlen($q) >= 2) {
        $slug = slugify($q);
        $exact = rows('SELECT * FROM fuel_munis WHERE slug = ? AND stations > 0', [$slug]);
        if (count($exact) === 1) {
            redirect(fuel_muni_path($exact[0]));
        }
        $results = rows('SELECT * FROM fuel_munis WHERE slug LIKE ? AND stations > 0 ORDER BY stations DESC, name LIMIT 30', ['%' . $slug . '%']);
        if (!$results && ($pid = fuel_province_by_slug($slug))) {
            redirect('gasolina/' . fuel_province_slug($pid));
        }
    }
    $GLOBALS['track'] = ['fsearch', 0];
    header('X-Robots-Tag: noindex');
    render('fuel-search', [
        'meta' => meta(['title' => 'Buscar gasolineras', 'noindex' => true]),
        'q' => $q,
        'results' => $results,
    ]);
}

function fuel_alert_create(): void
{
    csrf_check();
    fuel_require();
    $s = row('SELECT s.*, m.name AS muni_name FROM fuel_stations s LEFT JOIN fuel_munis m ON m.id = s.muni_id WHERE s.id = ? AND s.active = 1', [(int) input('gasolinera', '0')]);
    if (!$s || !mail_enabled()) {
        not_found();
    }
    $chosen = (string) input('c', '');
    $fuel = fuel_key($chosen);
    $raw = str_replace([' ', '€', '/l'], '', (string) input('precio', ''));
    $raw = str_replace(',', '.', $raw);
    $target = is_numeric($raw) ? (float) $raw : 0.0;
    $email = mb_strtolower(trim(mb_substr((string) input('correo', ''), 0, 190)));
    $error = null;
    if (!in_array($chosen, FUELS_MAIN, true) || $s[$chosen] === null) {
        $error = 'Elige el carburante del aviso: gasolina 95, diésel o gasolina 98.';
    } elseif ($target < 0.5 || $target > 4) {
        $error = 'Escribe el precio por litro al que quieres repostar, por ejemplo 1,499.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Escribe un correo válido para poder avisarte.';
    } elseif (rate_limited('fuelalert', 20, 86400)) {
        $error = 'Has creado muchos avisos hoy. Vuelve a intentarlo mañana.';
    } elseif ((int) val("SELECT COUNT(*) FROM fuel_alerts WHERE email = ? AND status IN ('pending', 'active')", [$email]) >= 20) {
        $error = 'Ese correo ya tiene muchos avisos activos. Borra alguno desde el enlace de nuestros correos.';
    } else {
        q("DELETE FROM fuel_alerts WHERE station_id = ? AND fuel = ? AND email = ? AND status IN ('pending', 'active')", [(int) $s['id'], $fuel, $email]);
        $code = bin2hex(random_bytes(16));
        insert('fuel_alerts', ['station_id' => (int) $s['id'], 'fuel' => $fuel, 'target_price' => round($target, 3), 'email' => $email, 'code' => $code, 'status' => 'pending', 'created_at' => now()]);
        if (!fuel_alert_mail_confirm($s, $fuel, $target, $email, $code)) {
            q('DELETE FROM fuel_alerts WHERE code = ?', [$code]);
            $error = 'No hemos podido enviarte el correo de confirmación. Vuelve a intentarlo en unos minutos.';
        }
    }
    header('X-Robots-Tag: noindex');
    render('fuel-alert', [
        'meta' => meta(['title' => 'Tu aviso de gasolina', 'noindex' => true]),
        'state' => $error ? 'error' : 'created',
        'error' => $error,
        's' => $s,
        'fuel' => $fuel,
        'target' => $target,
        'email' => $email,
    ]);
}

function fuel_alert_confirm(string $code): void
{
    $a = row('SELECT a.*, s.name, s.id AS sid, m.name AS muni_name FROM fuel_alerts a JOIN fuel_stations s ON s.id = a.station_id LEFT JOIN fuel_munis m ON m.id = s.muni_id WHERE a.code = ?', [$code]);
    if ($a && $a['status'] === 'pending') {
        update('fuel_alerts', ['status' => 'active', 'activated_at' => now()], (int) $a['id']);
    }
    header('X-Robots-Tag: noindex');
    render('fuel-alert', [
        'meta' => meta(['title' => 'Aviso de gasolina', 'noindex' => true]),
        'state' => $a ? 'confirmed' : 'gone',
        'error' => null,
        's' => $a ? ['id' => $a['sid'], 'name' => $a['name'], 'muni_name' => $a['muni_name']] : null,
        'fuel' => $a ? fuel_key($a['fuel']) : 'g95',
        'target' => $a ? (float) $a['target_price'] : 0,
        'email' => null,
    ]);
}

function fuel_alert_unsubscribe(string $code): void
{
    $a = row('SELECT * FROM fuel_alerts WHERE code = ?', [$code]);
    $state = $a ? 'ask' : 'gone';
    if ($a && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        q('DELETE FROM fuel_alerts WHERE ' . (input('todos') ? 'email = ?' : 'code = ?'), [input('todos') ? $a['email'] : $code]);
        $state = 'removed';
    }
    header('X-Robots-Tag: noindex');
    render('fuel-alert', [
        'meta' => meta(['title' => 'Borrar aviso de gasolina', 'noindex' => true]),
        'state' => $state,
        'error' => null,
        's' => null,
        'fuel' => 'g95',
        'target' => 0,
        'email' => null,
        'code' => $code,
    ]);
}
