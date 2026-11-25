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
        'cheapest' => fuel_cheapest_split($fuel, 10, 5),
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
    foreach (rows('SELECT day, g95, diesel, g98 FROM fuel_daily WHERE station_id = ? AND day >= ? ORDER BY day', [(int) $s['id'], date('Y-m-d', time() - 366 * 86400)]) as $r) {
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
        // Un correo ya comprobado no tiene que volver a confirmar.
        $verified = fuel_subscriber_verified($email);
        fuel_subscriber($email);
        insert('fuel_alerts', ['station_id' => (int) $s['id'], 'fuel' => $fuel, 'target_price' => round($target, 3), 'email' => $email, 'code' => $code, 'status' => $verified ? 'active' : 'pending', 'created_at' => now(), 'activated_at' => $verified ? now() : null]);
        if (!$verified && !fuel_alert_mail_confirm($s, $fuel, $target, $email, $code)) {
            q('DELETE FROM fuel_alerts WHERE code = ?', [$code]);
            $error = 'No hemos podido enviarte el correo de confirmación. Vuelve a intentarlo en unos minutos.';
        }
    }
    header('X-Robots-Tag: noindex');
    render('fuel-alert', [
        'meta' => meta(['title' => 'Tu aviso de gasolina', 'noindex' => true]),
        'state' => $error ? 'error' : (!empty($verified) ? 'confirmed' : 'created'),
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
    if ($a) {
        fuel_subscriber_verify((string) $a['email']);
    }
    header('X-Robots-Tag: noindex');
    render('fuel-alert', [
        'manage' => $a ? fuel_manage_url((string) $a['email']) : null,
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
        if (input('todos')) {
            q('DELETE FROM fuel_alerts WHERE email = ?', [$a['email']]);
            q('DELETE FROM zone_alerts WHERE email = ?', [$a['email']]);
        } else {
            q('DELETE FROM fuel_alerts WHERE code = ?', [$code]);
        }
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

/* ---------- Avisos por zona ---------- */

/** Zonas del formulario: las que se pasan primero (municipio, provincia) y después España y las provincias. */
function fuel_zone_choices(array $first = []): array
{
    $out = [];
    foreach ($first as $key) {
        if (str_starts_with($key, 'c')) {
            continue; // El código postal va en su propia casilla.
        }
        if ($z = fuel_zone($key)) {
            $out[$key] = str_starts_with($key, 'p') ? 'Provincia de ' . $z['name'] : ($key === 'es' ? 'Toda España' : $z['name']);
        }
    }
    return $out + fuel_zone_options();
}

function fuel_zone_form_defaults(): array
{
    return ['zone' => 'es', 'cp' => '', 'fuel' => '', 'mode' => 'change', 'down' => true, 'up' => true, 'step' => '0.02', 'target' => '', 'email' => ''];
}

/** El formulario relleno con un aviso guardado, para cambiarlo. */
function fuel_zone_form_from_alert(array $a): array
{
    $zone = (string) $a['zone'];
    return [
        'zone' => str_starts_with($zone, 'c') ? 'cp' : $zone,
        'cp' => str_starts_with($zone, 'c') ? substr($zone, 1) : '',
        'fuel' => fuel_key((string) $a['fuel']),
        'mode' => (string) ($a['mode'] ?: 'change'),
        'down' => $a['dir'] !== 'up',
        'up' => $a['dir'] !== 'down',
        'step' => number_format((float) $a['step'], 2, '.', ''),
        'target' => $a['target'] !== null ? number_format((float) $a['target'], 3, ',', '') : '',
        'email' => (string) $a['email'],
    ];
}

/**
 * Lee y comprueba el formulario de aviso por zona. Devuelve [error o null, formulario, datos del aviso, zona].
 * Sin $needEmail (página de gestión) el correo es el del enlace privado.
 */
function fuel_zone_read_form(bool $needEmail, string $email = ''): array
{
    $f = fuel_zone_form_defaults();
    $f['zone'] = (string) input('zona', 'es');
    $f['cp'] = (string) preg_replace('/\D/', '', (string) input('cp', ''));
    $f['fuel'] = (string) input('c', '');
    $f['mode'] = array_key_exists((string) input('modo', 'change'), ZONE_MODES) ? (string) input('modo', 'change') : 'change';
    $f['down'] = (bool) input('baja', '');
    $f['up'] = (bool) input('sube', '');
    $f['step'] = (string) input('paso', '0.02');
    $f['target'] = trim((string) input('precio', ''));
    $f['email'] = $needEmail ? mb_strtolower(trim(mb_substr((string) input('correo', ''), 0, 190))) : $email;
    $zone = $f['zone'] === 'cp' ? (preg_match('/^\d{5}$/', $f['cp']) ? fuel_zone('c' . $f['cp']) : null) : fuel_zone($f['zone']);
    $fuel = $f['fuel'];
    $step = round((float) $f['step'], 3);
    $t = str_replace(',', '.', $f['target']);
    $target = is_numeric($t) ? round((float) $t, 3) : null;
    $avg = $zone && in_array($fuel, FUELS_MAIN, true) ? fuel_zone_avg($zone, $fuel) : null;
    $error = null;
    if (!mail_enabled()) {
        $error = 'Los avisos por correo no están disponibles ahora mismo. Vuelve a intentarlo más tarde.';
    } elseif (!$zone && $f['zone'] === 'cp') {
        $error = preg_match('/^\d{5}$/', $f['cp']) ? 'No hay gasolineras con el código postal ' . $f['cp'] . '. Prueba con tu municipio o tu provincia.' : 'Escribe el código postal con sus 5 cifras, por ejemplo 08201.';
    } elseif (!$zone) {
        $error = 'Elige la zona del aviso.';
    } elseif (!in_array($fuel, FUELS_MAIN, true)) {
        $error = 'Elige el carburante del aviso: gasolina 95, diésel o gasolina 98.';
    } elseif ($avg === null) {
        $error = 'Ahora mismo no hay gasolineras con ' . mb_strtolower(fuel_name($fuel)) . ' ' . $zone['in'] . '. Elige otro carburante u otra zona.';
    } elseif ($f['mode'] === 'change' && !$f['down'] && !$f['up']) {
        $error = 'Marca si quieres el aviso cuando baje, cuando suba o en los dos casos.';
    } elseif ($f['mode'] === 'change' && !in_array($step, ZONE_STEPS, false)) {
        $error = 'Elige cuántos céntimos tiene que cambiar el precio.';
    } elseif ($f['mode'] === 'below' && ($target === null || $target < 0.5 || $target > 3)) {
        $error = 'Escribe el precio en euros por litro, por ejemplo 1,45.';
    } elseif ($needEmail && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Escribe un correo válido para poder avisarte.';
    }
    $data = null;
    if (!$error) {
        $data = [
            'zone' => $zone['key'],
            'fuel' => $fuel,
            'mode' => $f['mode'],
            'dir' => $f['mode'] !== 'change' ? 'both' : ($f['down'] && $f['up'] ? 'both' : ($f['down'] ? 'down' : 'up')),
            'step' => in_array($step, ZONE_STEPS, false) ? $step : 0.02,
            'target' => $f['mode'] === 'below' ? $target : null,
            'ref_price' => $avg,
            'ref_at' => now(),
            'armed' => 1,
        ];
    }
    return [$error, $f, $data, $zone];
}

/** Página de avisos por zona: explica cómo funcionan, muestra el formulario y lo recibe. */
function fuel_zone_alerts_page(): void
{
    fuel_require();
    $form = fuel_zone_form_defaults();
    $form['zone'] = (string) input('zona', 'es');
    $form['cp'] = (string) preg_replace('/\D/', '', (string) input('cp', ''));
    $form['fuel'] = (string) input('c', '');
    if ($form['cp'] !== '' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $form['zone'] = 'cp';
    }
    $first = [];
    if (preg_match('/^m\d+$/', $form['zone']) && ($z = row('SELECT province_id FROM fuel_munis WHERE id = ?', [(int) substr($form['zone'], 1)]))) {
        $first = [$form['zone'], 'p' . (int) $z['province_id']];
    }
    $state = 'form';
    $error = null;
    $zone = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        [$error, $form, $data, $zone] = fuel_zone_read_form(true);
        if (!$error && rate_limited('zonealert', 20, 86400)) {
            $error = 'Has creado muchos avisos hoy. Vuelve a intentarlo mañana.';
        }
        if (!$error && (int) val("SELECT COUNT(*) FROM zone_alerts WHERE email = ? AND status IN ('pending', 'active')", [$form['email']]) >= 10) {
            $error = 'Ese correo ya tiene 10 avisos por zona. Borra alguno con el enlace «Gestionar mis avisos» de nuestros correos.';
        }
        if (!$error) {
            q("DELETE FROM zone_alerts WHERE zone = ? AND fuel = ? AND mode = ? AND email = ? AND status IN ('pending', 'active')", [$data['zone'], $data['fuel'], $data['mode'], $form['email']]);
            $verified = fuel_subscriber_verified($form['email']);
            fuel_subscriber($form['email']);
            $a = $data + ['email' => $form['email'], 'code' => bin2hex(random_bytes(16)), 'status' => $verified ? 'active' : 'pending', 'created_at' => now(), 'activated_at' => $verified ? now() : null, 'sent_count' => 0];
            insert('zone_alerts', $a);
            if ($verified) {
                fuel_zone_alert_mail_created($a, $zone);
                $state = 'active';
            } elseif (fuel_zone_alert_mail_confirm($a, $zone)) {
                $state = 'created';
            } else {
                q('DELETE FROM zone_alerts WHERE code = ?', [$a['code']]);
                $error = 'No hemos podido enviarte el correo de confirmación. Vuelve a intentarlo en unos minutos.';
            }
        }
        if ($zone && str_starts_with($zone['key'], 'm') && !$first) {
            $first = [$zone['key']];
        }
    }
    $GLOBALS['track'] = ['falert', 0];
    render('fuel-avisos', [
        'meta' => meta([
            'title' => 'Avisos de precio de la gasolina por zona',
            'description' => 'Te avisamos por correo cuando la gasolina o el diésel bajen o suban en tu código postal, tu municipio, tu provincia o toda España, cuando alguna gasolinera baje de tu precio o cada lunes con un resumen. Gratis.',
            'canonical' => absolute_url('gasolina/avisos'),
            'noindex' => $_SERVER['REQUEST_METHOD'] === 'POST' || $first !== [] || $form['cp'] !== '',
            'jsonld' => [jsonld_breadcrumbs([['Inicio', ''], ['Gasolina', 'gasolina'], ['Avisos', 'gasolina/avisos']])],
        ]),
        'state' => $state,
        'error' => $error,
        'form' => $form,
        'zones' => fuel_zone_choices($first),
        'zone' => $zone,
        'mailOn' => mail_enabled(),
    ]);
}

function fuel_zone_alert_confirm(string $code): void
{
    $a = row('SELECT * FROM zone_alerts WHERE code = ?', [$code]);
    $zone = $a ? fuel_zone((string) $a['zone']) : null;
    if ($a && $zone && $a['status'] === 'pending') {
        $avg = fuel_zone_avg($zone, fuel_key((string) $a['fuel']));
        update('zone_alerts', ['status' => 'active', 'activated_at' => now(), 'ref_price' => $avg ?? $a['ref_price'], 'ref_at' => now()], (int) $a['id']);
        $a['ref_price'] = $avg ?? $a['ref_price'];
    }
    if ($a) {
        fuel_subscriber_verify((string) $a['email']);
    }
    header('X-Robots-Tag: noindex');
    render('fuel-zone-alert', [
        'meta' => meta(['title' => 'Aviso de gasolina', 'noindex' => true]),
        'state' => $a && $zone ? 'confirmed' : 'gone',
        'a' => $a,
        'zone' => $zone,
        'code' => $code,
        'manage' => $a ? fuel_manage_url((string) $a['email']) : null,
    ]);
}

function fuel_zone_alert_unsubscribe(string $code): void
{
    $a = row('SELECT * FROM zone_alerts WHERE code = ?', [$code]);
    $state = $a ? 'ask' : 'gone';
    if ($a && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        if (input('todos')) {
            q('DELETE FROM zone_alerts WHERE email = ?', [$a['email']]);
            q('DELETE FROM fuel_alerts WHERE email = ?', [$a['email']]);
        } else {
            q('DELETE FROM zone_alerts WHERE code = ?', [$code]);
        }
        $state = 'removed';
    }
    header('X-Robots-Tag: noindex');
    render('fuel-zone-alert', [
        'meta' => meta(['title' => 'Borrar aviso de gasolina', 'noindex' => true]),
        'state' => $state,
        'a' => $a,
        'zone' => $a ? fuel_zone((string) $a['zone']) : null,
        'code' => $code,
        'manage' => $a ? fuel_manage_url((string) $a['email']) : null,
    ]);
}

/**
 * «Mis avisos»: el enlace privado de cada correo. Lista sus avisos por zona y por gasolinera y permite cambiarlos,
 * borrarlos o añadir otros sin volver a confirmar (el enlace solo llega a ese buzón).
 */
function fuel_manage(string $token): void
{
    fuel_require();
    header('X-Robots-Tag: noindex');
    $sub = row('SELECT * FROM subscribers WHERE token = ?', [$token]);
    $meta = meta(['title' => 'Mis avisos de gasolina', 'noindex' => true]);
    if (!$sub) {
        http_response_code(404);
        render('fuel-manage', ['meta' => $meta, 'sub' => null]);
        return;
    }
    $email = (string) $sub['email'];
    $self = 'gasolina/mis-avisos/' . $token;
    if (!$sub['verified_at']) {
        fuel_subscriber_verify($email);
    }
    // Quien abre el enlace privado demuestra que el correo es suyo: los avisos pendientes quedan activos.
    q("UPDATE zone_alerts SET status = 'active', activated_at = ? WHERE email = ? AND status = 'pending'", [now(), $email]);
    q("UPDATE fuel_alerts SET status = 'active', activated_at = ? WHERE email = ? AND status = 'pending'", [now(), $email]);

    $error = null;
    $form = fuel_zone_form_defaults();
    $editId = 0;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $action = (string) input('accion', '');
        $id = (int) input('id', 0);
        if ($action === 'borrar-zona') {
            q('DELETE FROM zone_alerts WHERE id = ? AND email = ?', [$id, $email]);
            flash('Aviso borrado.');
            redirect($self);
        }
        if ($action === 'borrar-gasolinera') {
            q('DELETE FROM fuel_alerts WHERE id = ? AND email = ?', [$id, $email]);
            flash('Aviso borrado.');
            redirect($self);
        }
        if ($action === 'borrar-todo') {
            q('DELETE FROM zone_alerts WHERE email = ?', [$email]);
            q('DELETE FROM fuel_alerts WHERE email = ?', [$email]);
            flash('Hemos borrado todos tus avisos de gasolina. No te enviaremos más correos.');
            redirect($self);
        }
        if ($action === 'precio-gasolinera') {
            $t = str_replace(',', '.', trim((string) input('precio', '')));
            if (is_numeric($t) && (float) $t >= 0.5 && (float) $t <= 3 && row('SELECT id FROM fuel_alerts WHERE id = ? AND email = ?', [$id, $email])) {
                update('fuel_alerts', ['target_price' => round((float) $t, 3), 'status' => 'active', 'sent_at' => null], $id);
                flash('Aviso cambiado: te escribiremos cuando baje de ' . fuel_money(round((float) $t, 3)) . '.');
                redirect($self);
            }
            $error = 'Escribe el precio en euros por litro, por ejemplo 1,45.';
        }
        if ($action === 'nuevo' || $action === 'cambiar') {
            [$error, $form, $data, $zone] = fuel_zone_read_form(false, $email);
            $old = $action === 'cambiar' ? row('SELECT * FROM zone_alerts WHERE id = ? AND email = ?', [$id, $email]) : null;
            if (!$error && $action === 'nuevo' && (int) val("SELECT COUNT(*) FROM zone_alerts WHERE email = ? AND status IN ('pending', 'active')", [$email]) >= 10) {
                $error = 'Ya tienes 10 avisos por zona. Borra alguno para crear otro.';
            }
            if (!$error && $action === 'cambiar' && !$old) {
                $error = 'Ese aviso ya no existe.';
            }
            if (!$error) {
                if ($old) {
                    update('zone_alerts', $data + ['status' => 'active', 'sent_at' => null], (int) $old['id']);
                    flash('Aviso cambiado: te escribiremos ' . fuel_zone_rule($data, $zone) . '.');
                } else {
                    insert('zone_alerts', $data + ['email' => $email, 'code' => bin2hex(random_bytes(16)), 'status' => 'active', 'created_at' => now(), 'activated_at' => now(), 'sent_count' => 0]);
                    flash('Aviso creado: te escribiremos ' . fuel_zone_rule($data, $zone) . '.');
                }
                redirect($self);
            }
            $editId = $action === 'cambiar' ? $id : -1;
        }
    }
    $zoneAlerts = rows('SELECT * FROM zone_alerts WHERE email = ? ORDER BY created_at', [$email]);
    foreach ($zoneAlerts as &$za) {
        $za['zoneInfo'] = fuel_zone((string) $za['zone']);
    }
    unset($za);
    $stationAlerts = rows('SELECT a.*, s.name, s.address, s.g95, s.diesel, s.g98, s.id AS sid, m.name AS muni_name FROM fuel_alerts a
        JOIN fuel_stations s ON s.id = a.station_id LEFT JOIN fuel_munis m ON m.id = s.muni_id WHERE a.email = ? ORDER BY a.created_at', [$email]);
    render('fuel-manage', [
        'meta' => $meta,
        'sub' => $sub,
        'self' => $self,
        'flash' => flash(),
        'error' => $error,
        'form' => $form,
        'editId' => $editId,
        'zoneAlerts' => $zoneAlerts,
        'stationAlerts' => $stationAlerts,
    ]);
}
