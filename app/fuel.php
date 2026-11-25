<?php
declare(strict_types=1);

/**
 * Precios de carburantes de las gasolineras españolas, con los datos abiertos del Ministerio para la
 * Transición Ecológica. cron/gasolina.php los descarga; aquí están las utilidades que usan las páginas.
 */

const FUEL_SOURCE_URL = 'https://sedeaplicaciones.minetur.gob.es/ServiciosRESTCarburantes/PreciosCarburantes/EstacionesTerrestres/';

/** Carburantes que se muestran: clave interna => [nombre corto, campo del Ministerio]. */
const FUELS = [
    'g95' => ['Gasolina 95', 'Precio Gasolina 95 E5'],
    'diesel' => ['Diésel', 'Precio Gasoleo A'],
    'g98' => ['Gasolina 98', 'Precio Gasolina 98 E5'],
    'diesel_plus' => ['Diésel premium', 'Precio Gasoleo Premium'],
    'glp' => ['Autogás (GLP)', 'Precio Gases licuados del petróleo'],
];

/** Los tres que tienen pestaña y gráfico. */
const FUELS_MAIN = ['g95', 'diesel', 'g98'];

/** Provincias por su código INE, que es el que usa el Ministerio. */
const FUEL_PROVINCES = [
    1 => 'Araba/Álava', 2 => 'Albacete', 3 => 'Alicante', 4 => 'Almería', 5 => 'Ávila', 6 => 'Badajoz', 7 => 'Illes Balears',
    8 => 'Barcelona', 9 => 'Burgos', 10 => 'Cáceres', 11 => 'Cádiz', 12 => 'Castellón', 13 => 'Ciudad Real', 14 => 'Córdoba',
    15 => 'A Coruña', 16 => 'Cuenca', 17 => 'Girona', 18 => 'Granada', 19 => 'Guadalajara', 20 => 'Gipuzkoa', 21 => 'Huelva',
    22 => 'Huesca', 23 => 'Jaén', 24 => 'León', 25 => 'Lleida', 26 => 'La Rioja', 27 => 'Lugo', 28 => 'Madrid', 29 => 'Málaga',
    30 => 'Murcia', 31 => 'Navarra', 32 => 'Ourense', 33 => 'Asturias', 34 => 'Palencia', 35 => 'Las Palmas', 36 => 'Pontevedra',
    37 => 'Salamanca', 38 => 'Santa Cruz de Tenerife', 39 => 'Cantabria', 40 => 'Segovia', 41 => 'Sevilla', 42 => 'Soria',
    43 => 'Tarragona', 44 => 'Teruel', 45 => 'Toledo', 46 => 'Valencia', 47 => 'Valladolid', 48 => 'Bizkaia', 49 => 'Zamora',
    50 => 'Zaragoza', 51 => 'Ceuta', 52 => 'Melilla',
];

/** Canarias, Ceuta y Melilla: sus carburantes pagan menos impuestos, así que se listan aparte de la península y Baleares. */
const FUEL_LOW_TAX = [35, 38, 51, 52];

/** Filtro SQL de gasolineras de la península y Baleares ($in = false) o de Canarias, Ceuta y Melilla ($in = true). */
function fuel_low_tax_where(bool $in): string
{
    return 's.province_id ' . ($in ? 'IN' : 'NOT IN') . ' (' . implode(', ', FUEL_LOW_TAX) . ')';
}

/** Las más baratas, separadas: ['main' => península y Baleares, 'low' => Canarias, Ceuta y Melilla], con la media de cada grupo. */
function fuel_cheapest_split(string $fuel, int $main, int $low): array
{
    $out = [];
    foreach (['main' => [false, $main], 'low' => [true, $low]] as $k => [$in, $n]) {
        $where = fuel_low_tax_where($in);
        $out[$k] = ['stations' => fuel_stations($where, [], $fuel, $n), 'avg' => fuel_area_stats($where, [], $fuel)['avg']];
    }
    return $out;
}

function fuel_enabled(): bool
{
    static $on = null;
    if ($on === null) {
        try {
            $on = (int) val('SELECT COUNT(*) FROM fuel_stations WHERE active = 1') > 0;
        } catch (Throwable $e) {
            $on = false;
        }
    }
    return $on;
}

function fuel_key(?string $k): string
{
    return in_array($k, FUELS_MAIN, true) ? $k : 'g95';
}

function fuel_name(string $k): string
{
    return FUELS[$k][0] ?? $k;
}

/** «la gasolina 95» o «el diésel», con su artículo. */
function fuel_the(string $k): string
{
    return (in_array($k, ['diesel', 'diesel_plus', 'glp'], true) ? 'el ' : 'la ') . mb_strtolower(fuel_name($k));
}

/** «de la gasolina 95» o «del diésel». */
function fuel_of(string $k): string
{
    $the = fuel_the($k);
    return str_starts_with($the, 'el ') ? 'del ' . substr($the, 3) : 'de ' . $the;
}

/** «1 céntimo», «2 céntimos». */
function fuel_step_text(float $step): string
{
    $c = (int) round($step * 100);
    return $c . ($c === 1 ? ' céntimo' : ' céntimos');
}

/** «25 de noviembre», sin año si es el de ahora. */
function fuel_day_text(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }
    $t = strtotime($datetime);
    return (int) date('j', $t) . ' de ' . MONTHS_ES[(int) date('n', $t) - 1] . (date('Y', $t) === date('Y') ? '' : ' de ' . date('Y', $t));
}

/** 1,649 €/l */
function fuel_money($v, bool $unit = true): string
{
    return $v === null || $v === '' ? '' : number_format((float) $v, 3, ',', '.') . ($unit ? ' €/l' : ' €');
}

/** Diferencia en céntimos, con signo: «+2,1 cts» o «−0,8 cts». */
function fuel_cents(float $diff): string
{
    $c = round($diff * 100, 1);
    return ($c > 0 ? '+' : ($c < 0 ? '−' : '')) . number_format(abs($c), 1, ',', '.') . ' cts';
}

function fuel_province_slug(int $id): string
{
    return slugify(FUEL_PROVINCES[$id] ?? (string) $id);
}

function fuel_province_by_slug(string $slug): ?int
{
    foreach (FUEL_PROVINCES as $id => $name) {
        if (slugify($name) === $slug) {
            return $id;
        }
    }
    return null;
}

/** «Hospitalet de Llobregat (L')» → «L'Hospitalet de Llobregat». */
function fuel_pretty_place(string $name): string
{
    $name = trim(preg_replace('/\s+/', ' ', $name));
    if (preg_match("/^(.*) \((el|la|los|las|l'|lo|els|les|o|a|os|as|es|sa|ses|s')\)$/iu", $name, $m)) {
        $art = mb_convert_case($m[2], MB_CASE_TITLE);
        $name = str_ends_with($art, "'") ? $art . $m[1] : $art . ' ' . $m[1];
    }
    return $name;
}

/** Textos en mayúsculas del Ministerio («AVENIDA CASTILLA LA MANCHA, 26») a tipo frase. */
function fuel_tidy(string $text): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text));
    if ($text === '' || mb_strtoupper($text) !== $text || mb_strlen($text) <= 3) {
        return $text;
    }
    $out = mb_convert_case(mb_strtolower($text), MB_CASE_TITLE);
    return preg_replace_callback('/\b(De|Del|La|Las|Los|El|Y|En|S\/n|Km)\b/u', fn ($m) => mb_strtolower($m[1]), $out) ?? $out;
}

function fuel_station_path(array $s): string
{
    return 'gasolina/gasolinera/' . (int) $s['id'] . '-' . (slugify((string) $s['name'] . ' ' . (string) ($s['muni_name'] ?? '')) ?: 'gasolinera');
}

function fuel_station_url(array $s): string
{
    return url(fuel_station_path($s));
}

function fuel_muni_path(array $m): string
{
    return 'gasolina/' . fuel_province_slug((int) $m['province_id']) . '/' . $m['slug'];
}

function fuel_muni_url(array $m): string
{
    return url(fuel_muni_path($m));
}

function fuel_province_url(int $id): string
{
    return url('gasolina/' . fuel_province_slug($id));
}

/** Gasolineras activas con precio para un carburante, más baratas primero. */
function fuel_stations(string $where, array $params, string $fuel, int $limit = 200): array
{
    $col = fuel_key($fuel);
    return rows(
        "SELECT s.*, m.name AS muni_name, m.slug AS muni_slug FROM fuel_stations s
         LEFT JOIN fuel_munis m ON m.id = s.muni_id
         WHERE s.active = 1 AND s.$col IS NOT NULL AND $where ORDER BY s.$col ASC, s.name LIMIT $limit",
        $params
    );
}

/** Media, mínimo y número de gasolineras de un carburante en una zona, calculado al momento. */
function fuel_area_stats(string $where, array $params, string $fuel): array
{
    $col = fuel_key($fuel);
    $r = row("SELECT AVG($col) AS avg, MIN($col) AS min, MAX($col) AS max, COUNT(*) AS n FROM fuel_stations s WHERE s.active = 1 AND s.$col IS NOT NULL AND $where", $params);
    return ['avg' => $r && $r['n'] ? (float) $r['avg'] : null, 'min' => $r['min'] ?? null, 'max' => $r['max'] ?? null, 'n' => (int) ($r['n'] ?? 0)];
}

/** Serie diaria de la media (España si $province = 0) para el gráfico. */
function fuel_series(int $province, int $days = 180): array
{
    $from = date('Y-m-d', time() - $days * 86400);
    $out = ['labels' => [], 'series' => []];
    $data = [];
    foreach (rows('SELECT day, fuel, avg FROM fuel_stats WHERE province_id = ? AND day >= ? ORDER BY day', [$province, $from]) as $r) {
        $d = substr((string) $r['day'], 0, 10);
        $data[$r['fuel']][$d] = round((float) $r['avg'], 3);
        $out['labels'][$d] = true;
    }
    $out['labels'] = array_keys($out['labels']);
    foreach (FUELS_MAIN as $f) {
        if (!empty($data[$f])) {
            $out['series'][] = ['key' => $f, 'name' => fuel_name($f), 'values' => array_map(fn ($d) => $data[$f][$d] ?? null, $out['labels'])];
        }
    }
    return $out;
}

/** Media de un día concreto (o la más cercana anterior) de una zona y carburante. */
function fuel_avg_on(int $province, string $fuel, string $day): ?float
{
    $v = val('SELECT avg FROM fuel_stats WHERE province_id = ? AND fuel = ? AND day <= ? ORDER BY day DESC LIMIT 1', [$province, $fuel, $day]);
    return $v === null ? null : (float) $v;
}

/**
 * Veredicto sencillo para «¿es buen momento para repostar?»: compara la media de hoy con la de los últimos 90 días.
 * Devuelve [clave de color, titular, explicación] o null si aún no hay historia suficiente.
 */
function fuel_verdict(int $province, string $fuel): ?array
{
    $rows = rows('SELECT avg FROM fuel_stats WHERE province_id = ? AND fuel = ? AND day >= ? ORDER BY day', [$province, $fuel, date('Y-m-d', time() - 90 * 86400)]);
    if (count($rows) < 14) {
        return null;
    }
    $vals = array_map(fn ($r) => (float) $r['avg'], $rows);
    $now = end($vals);
    $min = min($vals);
    $max = max($vals);
    $pos = $max - $min < 0.005 ? 0.5 : ($now - $min) / ($max - $min);
    $the = fuel_the($fuel);
    $The = mb_strtoupper(mb_substr($the, 0, 1)) . mb_substr($the, 1);
    if ($pos <= 0.25) {
        return ['low', 'Buen momento para repostar', "$The está cerca de su precio más bajo de los últimos 90 días."];
    }
    if ($pos >= 0.75) {
        return ['high', 'Precio alto', "$The está cerca de su precio más alto de los últimos 90 días. Si puedes, llena solo lo necesario."];
    }
    return ['good', 'Precio normal', "$The está en la zona media de los últimos 90 días."];
}

/** Inserta o actualiza muchas filas de golpe, en MySQL o en SQLite. */
function fuel_upsert(string $table, array $cols, array $keys, array $rows): void
{
    if (!$rows) {
        return;
    }
    $mysql = config('db.driver') === 'mysql';
    $set = [];
    foreach (array_diff($cols, $keys) as $c) {
        $set[] = $mysql ? "$c = VALUES($c)" : "$c = excluded.$c";
    }
    $tail = $mysql ? ' ON DUPLICATE KEY UPDATE ' . implode(', ', $set) : ' ON CONFLICT(' . implode(', ', $keys) . ') DO UPDATE SET ' . implode(', ', $set);
    $one = '(' . implode(', ', array_fill(0, count($cols), '?')) . ')';
    foreach (array_chunk($rows, 400) as $chunk) {
        $params = [];
        foreach ($chunk as $r) {
            foreach ($cols as $c) {
                $params[] = $r[$c] ?? null;
            }
        }
        q("INSERT INTO $table (" . implode(', ', $cols) . ') VALUES ' . implode(', ', array_fill(0, count($chunk), $one)) . $tail, $params);
    }
}

/** Distancia en km entre dos puntos. */
function fuel_km(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $r = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

/* ---------- Avisos de precio por gasolinera ---------- */

function fuel_alert_mail_confirm(array $s, string $fuel, float $target, string $email, string $code): bool
{
    $url = absolute_url('gasolina/aviso/confirmar/' . $code);
    $what = fuel_the($fuel) . ' en ' . $s['name'] . ' (' . $s['muni_name'] . ')';
    $text = "Hola:\n\nHas pedido que te avisemos cuando $what baje de " . fuel_money($target) . ".\n\nConfirma el aviso en este enlace:\n$url\n\n"
        . "Si no has sido tú, no hagas nada: el aviso se borra solo a los 7 días.\n\n" . site_name();
    $html = mail_html(
        'Confirma tu aviso de gasolina',
        '<p style="margin:0 0 12px">Has pedido que te avisemos cuando <strong>' . e($what) . '</strong> baje de <strong>' . e(fuel_money($target)) . '</strong>.</p>'
            . '<p style="margin:0">Pulsa el botón para confirmar que este correo es tuyo.</p>',
        'Confirmar aviso',
        $url,
        'Si no has sido tú, no hagas nada: el aviso se borra solo a los 7 días.'
    );
    return send_mail($email, 'Confirma tu aviso: ' . fuel_name($fuel) . ' en ' . $s['name'], $text, $html);
}

/** Revisa los avisos activos después de cada importación. Devuelve cuántos ha enviado. */
function fuel_check_alerts(): int
{
    if (!mail_enabled()) {
        return 0;
    }
    q("DELETE FROM fuel_alerts WHERE status = 'pending' AND created_at < ?", [date('Y-m-d H:i:s', time() - 7 * 86400)]);
    q("DELETE FROM fuel_alerts WHERE status = 'sent' AND sent_at < ?", [date('Y-m-d H:i:s', time() - 30 * 86400)]);
    $sent = 0;
    foreach (rows("SELECT a.*, s.name, s.address, s.g95, s.diesel, s.g98, m.name AS muni_name FROM fuel_alerts a
        JOIN fuel_stations s ON s.id = a.station_id LEFT JOIN fuel_munis m ON m.id = s.muni_id
        WHERE a.status = 'active' AND s.active = 1") as $a) {
        $fuel = fuel_key($a['fuel']);
        $now = $a[$fuel] ?? null;
        if ($now === null || (float) $now > (float) $a['target_price']) {
            continue;
        }
        $url = absolute_url(fuel_station_path(['id' => $a['station_id'], 'name' => $a['name'], 'muni_name' => $a['muni_name']]));
        $off = absolute_url('gasolina/aviso/baja/' . $a['code']);
        $what = fuel_the($fuel) . ' en ' . $a['name'] . ' (' . $a['muni_name'] . ')';
        $text = mb_strtoupper(mb_substr($what, 0, 1)) . mb_substr($what, 1) . " está a " . fuel_money($now) . ", por debajo de tu aviso (" . fuel_money($a['target_price']) . ").\n\n"
            . "Mira los precios de ahora:\n$url\n\nEl precio puede cambiar durante el día: el válido es el del surtidor.\n"
            . "Este aviso no se repetirá. Para borrarlo: $off\n\n" . site_name();
        $html = mail_html(
            'Ha bajado la gasolina',
            '<p style="margin:0 0 12px"><strong>' . e(mb_strtoupper(mb_substr($what, 0, 1)) . mb_substr($what, 1)) . '</strong> está a <strong>' . e(fuel_money($now)) . '</strong>, por debajo de tu aviso (' . e(fuel_money($a['target_price'])) . ').</p>'
                . '<p style="margin:0">El precio puede cambiar durante el día: el válido es el del surtidor.</p>',
            'Ver los precios de ahora',
            $url,
            'Este aviso no se repetirá. <a href="' . e($off) . '" style="color:#1F3FD8">Borrar el aviso</a>.'
        );
        if (send_mail((string) $a['email'], 'Ha bajado: ' . fuel_name($fuel) . ' a ' . fuel_money($now), $text, $html)) {
            update('fuel_alerts', ['status' => 'sent', 'sent_at' => now(), 'sent_price' => $now], (int) $a['id']);
            $sent++;
        }
    }
    return $sent;
}

/* ---------- Avisos por zona: cuando la media de un municipio, una provincia o España baja o sube ---------- */

/** Cambio mínimo de la media, en euros por litro, que dispara un aviso. */
const ZONE_STEPS = [0.01, 0.02, 0.03, 0.05];

/**
 * Zona a partir de su clave: «es» (España), «p8» (provincia) o «m123» (municipio).
 * Devuelve nombre, frase «en …», ruta de su página y el filtro SQL de sus gasolineras, o null si no existe.
 */
function fuel_zone(string $key): ?array
{
    if ($key === 'es') {
        return ['key' => 'es', 'name' => 'España', 'in' => 'en España', 'path' => 'gasolina', 'where' => '1 = 1', 'params' => []];
    }
    if (preg_match('/^p(\d{1,2})$/', $key, $m) && isset(FUEL_PROVINCES[(int) $m[1]])) {
        $id = (int) $m[1];
        return ['key' => $key, 'name' => FUEL_PROVINCES[$id], 'in' => 'en la provincia de ' . FUEL_PROVINCES[$id], 'path' => 'gasolina/' . fuel_province_slug($id), 'where' => 's.province_id = ?', 'params' => [$id]];
    }
    if (preg_match('/^m(\d{1,9})$/', $key, $m) && ($mu = row('SELECT * FROM fuel_munis WHERE id = ?', [(int) $m[1]]))) {
        return ['key' => $key, 'name' => $mu['name'], 'in' => 'en ' . $mu['name'], 'path' => fuel_muni_path($mu), 'where' => 's.muni_id = ?', 'params' => [(int) $mu['id']]];
    }
    return null;
}

/** Opciones de zona para el formulario: España y las 52 provincias. */
function fuel_zone_options(): array
{
    $out = ['es' => 'Toda España'];
    $names = FUEL_PROVINCES;
    uasort($names, fn ($a, $b) => strcmp(slugify($a), slugify($b)));
    foreach ($names as $id => $name) {
        $out['p' . $id] = $name;
    }
    return $out;
}

/** Media de ahora de un carburante en una zona, con los precios de cada gasolinera. */
function fuel_zone_avg(array $zone, string $fuel): ?float
{
    $avg = fuel_area_stats($zone['where'], $zone['params'], $fuel)['avg'];
    return $avg === null ? null : round($avg, 3);
}

/** Medias de ahora de todas las zonas de golpe: ['es' => [...], 'p8' => [...], 'm123' => [...]]. */
function fuel_zone_avgs(): array
{
    $cols = 'AVG(g95) AS g95, AVG(diesel) AS diesel, AVG(g98) AS g98';
    $out = ['es' => row("SELECT $cols FROM fuel_stations WHERE active = 1") ?: []];
    foreach (rows("SELECT province_id AS k, $cols FROM fuel_stations WHERE active = 1 GROUP BY province_id") as $r) {
        $out['p' . $r['k']] = $r;
    }
    foreach (rows("SELECT muni_id AS k, $cols FROM fuel_stations WHERE active = 1 GROUP BY muni_id") as $r) {
        $out['m' . $r['k']] = $r;
    }
    return $out;
}

/** «baje», «suba» o «baje o suba». */
function fuel_zone_verb(string $dir): string
{
    return ['down' => 'baje', 'up' => 'suba'][$dir] ?? 'baje o suba';
}

/** «el precio medio del diésel en Sabadell baje 2 céntimos o más». */
function fuel_zone_rule(string $fuel, array $zone, string $dir, float $step): string
{
    return 'el precio medio ' . fuel_of($fuel) . ' ' . $zone['in'] . ' ' . fuel_zone_verb($dir) . ' ' . fuel_step_text($step) . ' o más';
}

function fuel_zone_alert_mail_confirm(array $zone, string $fuel, string $dir, float $step, string $email, string $code): bool
{
    $url = absolute_url('gasolina/avisos/confirmar/' . $code);
    $rule = fuel_zone_rule($fuel, $zone, $dir, $step);
    $text = "Hola:\n\nHas pedido que te avisemos cuando $rule.\n\nConfirma el aviso en este enlace:\n$url\n\n"
        . "Si no has sido tú, no hagas nada: el aviso se borra solo a los 7 días.\n\n" . site_name();
    $html = mail_html(
        'Confirma tu aviso de gasolina',
        '<p style="margin:0 0 12px">Has pedido que te avisemos cuando <strong>' . e($rule) . '</strong>.</p>'
            . '<p style="margin:0">Pulsa el botón para confirmar que este correo es tuyo.</p>',
        'Confirmar aviso',
        $url,
        'Si no has sido tú, no hagas nada: el aviso se borra solo a los 7 días.'
    );
    return send_mail($email, 'Confirma tu aviso: ' . fuel_name($fuel) . ' ' . $zone['in'], $text, $html);
}

/** Correo de «ha bajado» o «ha subido», con las tres gasolineras más baratas de la zona. */
function fuel_zone_alert_mail(array $a, array $zone, float $now, float $ref, bool $up): bool
{
    $fuel = fuel_key((string) $a['fuel']);
    $since = substr((string) $a['ref_at'], 0, 10) === date('Y-m-d') ? 'las ' . date('H:i', strtotime((string) $a['ref_at'])) . ' de hoy' : 'el ' . fuel_day_text((string) $a['ref_at']);
    $diff = fuel_cents($now - $ref);
    $page = absolute_url($zone['path'] . ($fuel === 'g95' ? '' : '?c=' . $fuel));
    $off = absolute_url('gasolina/avisos/baja/' . $a['code']);
    $best = fuel_stations($zone['where'], $zone['params'], $fuel, 3);
    $title = ($up ? 'Sube ' : 'Baja ') . fuel_the($fuel) . ' ' . $zone['in'];
    $intro = 'El precio medio ' . fuel_of($fuel) . " {$zone['in']} está a " . fuel_money($now) . ", $diff desde $since (" . fuel_money($ref) . ').';
    $tip = $up ? 'Si tienes que repostar pronto, estas son las más baratas ahora:' : 'Las más baratas ahora:';
    $lines = [];
    $rowsHtml = '';
    foreach ($best as $s) {
        $lines[] = '- ' . $s['name'] . ', ' . $s['address'] . ' (' . $s['muni_name'] . '): ' . fuel_money($s[$fuel]);
        $rowsHtml .= '<tr><td style="padding:8px 0;border-top:1px solid #DDE2F1"><a href="' . e(absolute_url(fuel_station_path($s))) . '" style="color:#10162F;font-weight:bold;text-decoration:none">' . e($s['name']) . '</a>'
            . '<br><span style="color:#535B7C;font-size:14px">' . e($s['address'] . ', ' . $s['muni_name']) . '</span></td>'
            . '<td style="padding:8px 0 8px 12px;border-top:1px solid #DDE2F1;text-align:right;font-weight:bold;white-space:nowrap">' . e(fuel_money($s[$fuel])) . '</td></tr>';
    }
    $foot = 'Te avisamos cuando la media ' . fuel_zone_verb((string) $a['dir']) . ' ' . fuel_step_text((float) $a['step']) . ' o más, como mucho una vez al día.';
    $text = "$intro\n\n$tip\n" . implode("\n", $lines) . "\n\nTodas las gasolineras:\n$page\n\n"
        . "El precio puede cambiar durante el día: el válido es el del surtidor.\n$foot\nPara borrar el aviso: $off\n\n" . site_name();
    $html = mail_html(
        $title,
        '<p style="margin:0 0 14px">' . e($intro) . '</p>'
            . ($rowsHtml ? '<p style="margin:0 0 6px;font-weight:bold">' . e($tip) . '</p><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:15px;line-height:1.4">' . $rowsHtml . '</table>' : '')
            . '<p style="margin:14px 0 0;font-size:14px;color:#535B7C">El precio puede cambiar durante el día: el válido es el del surtidor.</p>',
        'Ver todas las gasolineras',
        $page,
        e($foot) . ' <a href="' . e($off) . '" style="color:#1F3FD8">Borrar el aviso</a>.'
    );
    return send_mail((string) $a['email'], $title . ': ' . fuel_money($now) . ' (' . $diff . ')', $text, $html);
}

/**
 * Revisa los avisos por zona después de cada importación. Para un aviso de bajada, la referencia sube con el
 * precio (así avisa de la bajada desde el último máximo); para uno de subida, baja con él. Como mucho un
 * correo al día por aviso y $max correos por pasada. Devuelve cuántos ha enviado.
 */
function fuel_check_zone_alerts(int $max = 300): int
{
    if (!mail_enabled()) {
        return 0;
    }
    q("DELETE FROM zone_alerts WHERE status = 'pending' AND created_at < ?", [date('Y-m-d H:i:s', time() - 7 * 86400)]);
    $alerts = rows("SELECT * FROM zone_alerts WHERE status = 'active' ORDER BY id");
    if (!$alerts) {
        return 0;
    }
    $avgs = fuel_zone_avgs();
    $recent = date('Y-m-d H:i:s', time() - 20 * 3600);
    $zones = [];
    $sent = 0;
    foreach ($alerts as $a) {
        $fuel = fuel_key((string) $a['fuel']);
        $now = $avgs[$a['zone']][$fuel] ?? null;
        if ($now === null) {
            continue;
        }
        $now = round((float) $now, 3);
        if ($a['ref_price'] === null) {
            update('zone_alerts', ['ref_price' => $now, 'ref_at' => now()], (int) $a['id']);
            continue;
        }
        $ref = (float) $a['ref_price'];
        $step = (float) $a['step'] - 0.0005;
        $down = $a['dir'] !== 'up' && $now - $ref <= -$step;
        $up = $a['dir'] !== 'down' && $now - $ref >= $step;
        if ($down || $up) {
            if ((string) $a['sent_at'] > $recent || $sent >= $max) {
                continue;
            }
            $zone = $zones[$a['zone']] ??= fuel_zone((string) $a['zone']);
            if ($zone && fuel_zone_alert_mail($a, $zone, $now, $ref, $up)) {
                update('zone_alerts', ['ref_price' => $now, 'ref_at' => now(), 'sent_at' => now(), 'sent_price' => $now, 'sent_count' => (int) $a['sent_count'] + 1], (int) $a['id']);
                $sent++;
            }
            continue;
        }
        if (($a['dir'] === 'down' && $now > $ref) || ($a['dir'] === 'up' && $now < $ref)) {
            update('zone_alerts', ['ref_price' => $now, 'ref_at' => now()], (int) $a['id']);
        }
    }
    return $sent;
}
