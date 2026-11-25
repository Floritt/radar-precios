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

/** Serie diaria de la media (España si $province = 0) para el gráfico: por defecto, desde el 1 de enero del año pasado como mucho un año. */
function fuel_series(int $province, int $days = 366): array
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
            . fuel_mail_foot((string) $a['email'], $off, 'Este aviso no se repetirá.')[0] . "\n\n" . site_name();
        $html = mail_html(
            'Ha bajado la gasolina',
            '<p style="margin:0 0 12px"><strong>' . e(mb_strtoupper(mb_substr($what, 0, 1)) . mb_substr($what, 1)) . '</strong> está a <strong>' . e(fuel_money($now)) . '</strong>, por debajo de tu aviso (' . e(fuel_money($a['target_price'])) . ').</p>'
                . '<p style="margin:0">El precio puede cambiar durante el día: el válido es el del surtidor.</p>',
            'Ver los precios de ahora',
            $url,
            fuel_mail_foot((string) $a['email'], $off, 'Este aviso no se repetirá.')[1]
        );
        if (send_mail((string) $a['email'], 'Ha bajado: ' . fuel_name($fuel) . ' a ' . fuel_money($now), $text, $html)) {
            update('fuel_alerts', ['status' => 'sent', 'sent_at' => now(), 'sent_price' => $now], (int) $a['id']);
            $sent++;
        }
    }
    return $sent;
}

/* ---------- Avisos por zona: cuando la media de una zona cambia, cuando alguna gasolinera baja de un precio, o un resumen semanal ---------- */

/** Cambio mínimo de la media, en euros por litro, que dispara un aviso. */
const ZONE_STEPS = [0.01, 0.02, 0.03, 0.05];

/** Tipos de aviso por zona. */
const ZONE_MODES = ['change' => 'Si la media cambia', 'below' => 'Si alguna baja de un precio', 'weekly' => 'Resumen cada lunes'];

/**
 * Zona a partir de su clave: «es» (España), «p8» (provincia), «m123» (municipio) o «c08201» (código postal).
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
    if (preg_match('/^c(\d{5})$/', $key, $m)) {
        $mu = row('SELECT m.* FROM fuel_stations s JOIN fuel_munis m ON m.id = s.muni_id WHERE s.cp = ? AND s.active = 1 ORDER BY s.id LIMIT 1', [$m[1]]);
        if (!$mu) {
            return null;
        }
        return ['key' => $key, 'name' => 'CP ' . $m[1], 'in' => 'en el código postal ' . $m[1] . ' (' . $mu['name'] . ')', 'path' => fuel_muni_path($mu), 'where' => 's.cp = ?', 'params' => [$m[1]]];
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

/** Media de ahora de un carburante en una zona. */
function fuel_zone_avg(array $zone, string $fuel): ?float
{
    $avg = fuel_area_stats($zone['where'], $zone['params'], $fuel)['avg'];
    return $avg === null ? null : round($avg, 3);
}

/** Media de un carburante en una zona un día concreto, con el historial de cada gasolinera. */
function fuel_zone_avg_on(array $zone, string $fuel, string $day): ?float
{
    $fuel = fuel_key($fuel);
    $v = val("SELECT AVG(d.$fuel) FROM fuel_daily d JOIN fuel_stations s ON s.id = d.station_id WHERE {$zone['where']} AND d.day = ?", array_merge($zone['params'], [$day]));
    return $v === null || $v === false ? null : round((float) $v, 3);
}

/** Medias y mínimos de ahora de todas las zonas de golpe: ['es' => [...], 'p8' => [...], 'm123' => [...], 'c08201' => [...]]. */
function fuel_zone_avgs(): array
{
    $cols = 'AVG(g95) AS g95, AVG(diesel) AS diesel, AVG(g98) AS g98, MIN(g95) AS g95_min, MIN(diesel) AS diesel_min, MIN(g98) AS g98_min';
    $out = ['es' => row("SELECT $cols FROM fuel_stations WHERE active = 1") ?: []];
    foreach (rows("SELECT province_id AS k, $cols FROM fuel_stations WHERE active = 1 GROUP BY province_id") as $r) {
        $out['p' . $r['k']] = $r;
    }
    foreach (rows("SELECT muni_id AS k, $cols FROM fuel_stations WHERE active = 1 GROUP BY muni_id") as $r) {
        $out['m' . $r['k']] = $r;
    }
    foreach (rows("SELECT cp AS k, $cols FROM fuel_stations WHERE active = 1 AND cp <> '' GROUP BY cp") as $r) {
        $out['c' . $r['k']] = $r;
    }
    return $out;
}

/** «baje», «suba» o «baje o suba». */
function fuel_zone_verb(string $dir): string
{
    return ['down' => 'baje', 'up' => 'suba'][$dir] ?? 'baje o suba';
}

/**
 * Qué hace un aviso, para completar «Te avisaremos …»:
 * «cuando el precio medio del diésel en Sabadell baje 2 céntimos o más», «cuando alguna gasolinera en Sabadell
 * ponga el diésel a 1,450 €/l o menos» o «cada lunes con un resumen del diésel en Sabadell».
 */
function fuel_zone_rule(array $a, array $zone): string
{
    $fuel = fuel_key((string) $a['fuel']);
    $mode = (string) ($a['mode'] ?? 'change');
    if ($mode === 'below') {
        return 'cuando alguna gasolinera ' . $zone['in'] . ' ponga ' . fuel_the($fuel) . ' a ' . fuel_money($a['target']) . ' o menos';
    }
    if ($mode === 'weekly') {
        return 'cada lunes con un resumen ' . fuel_of($fuel) . ' ' . $zone['in'];
    }
    return 'cuando el precio medio ' . fuel_of($fuel) . ' ' . $zone['in'] . ' ' . fuel_zone_verb((string) $a['dir']) . ' ' . fuel_step_text((float) $a['step']) . ' o más';
}

/** Resumen corto de un aviso para listas: «Diésel en Sabadell: si baja o sube 2 céntimos». */
function fuel_zone_summary(array $a, ?array $zone): string
{
    $fuel = fuel_key((string) $a['fuel']);
    $where = $zone ? $zone['in'] : '';
    $mode = (string) ($a['mode'] ?? 'change');
    $what = $mode === 'below' ? 'si alguna baja de ' . fuel_money($a['target']) : ($mode === 'weekly' ? 'resumen cada lunes' : ['down' => 'si baja', 'up' => 'si sube'][$a['dir']] ?? 'si baja o sube');
    $what .= $mode === 'change' ? ' ' . fuel_step_text((float) $a['step']) : '';
    return fuel_name($fuel) . ' ' . $where . ': ' . $what;
}

/* ---------- Suscriptores: un enlace privado por correo para ver, cambiar, añadir o borrar sus avisos ---------- */

/** Ficha del correo (se crea si no existe). */
function fuel_subscriber(string $email): array
{
    $email = mb_strtolower(trim($email));
    $s = row('SELECT * FROM subscribers WHERE email = ?', [$email]);
    if (!$s) {
        insert('subscribers', ['email' => $email, 'token' => bin2hex(random_bytes(16)), 'created_at' => now()]);
        $s = row('SELECT * FROM subscribers WHERE email = ?', [$email]);
    }
    return $s;
}

/** Marca el correo como comprobado: ha pulsado un enlace que solo llega a ese buzón. */
function fuel_subscriber_verify(string $email): array
{
    $s = fuel_subscriber($email);
    if (!$s['verified_at']) {
        update('subscribers', ['verified_at' => now()], (int) $s['id']);
        $s['verified_at'] = now();
    }
    return $s;
}

/** Si el correo ya está comprobado: por su ficha o porque tiene algún aviso activo (que en su día confirmó). */
function fuel_subscriber_verified(string $email): bool
{
    $email = mb_strtolower(trim($email));
    return (bool) val('SELECT verified_at FROM subscribers WHERE email = ?', [$email])
        || (bool) val("SELECT 1 FROM zone_alerts WHERE email = ? AND status = 'active' LIMIT 1", [$email])
        || (bool) val("SELECT 1 FROM fuel_alerts WHERE email = ? AND status IN ('active', 'sent') LIMIT 1", [$email]);
}

function fuel_manage_url(string $email): string
{
    return absolute_url('gasolina/mis-avisos/' . fuel_subscriber($email)['token']);
}

/** Pie común de los correos de avisos: gestionar y borrar. */
function fuel_mail_foot(string $email, string $off, string $first = ''): array
{
    $manage = fuel_manage_url($email);
    $text = ($first !== '' ? "$first\n" : '') . "Gestionar tus avisos (cambiar, añadir o borrar): $manage\nBorrar este aviso: $off";
    $html = ($first !== '' ? e($first) . '<br>' : '')
        . '<a href="' . e($manage) . '" style="color:#1F3FD8">Gestionar mis avisos</a> (cambiar, añadir o borrar) · <a href="' . e($off) . '" style="color:#1F3FD8">Borrar este aviso</a>';
    return [$text, $html];
}

/* ---------- Correos de avisos por zona ---------- */

function fuel_zone_alert_mail_confirm(array $a, array $zone): bool
{
    $url = absolute_url('gasolina/avisos/confirmar/' . $a['code']);
    $rule = fuel_zone_rule($a, $zone);
    $text = "Hola:\n\nHas pedido que te avisemos $rule.\n\nConfirma el aviso en este enlace:\n$url\n\n"
        . "Solo hay que confirmar la primera vez: los siguientes avisos de este correo se activan solos.\n"
        . "Si no has sido tú, no hagas nada: el aviso se borra solo a los 7 días.\n\n" . site_name();
    $html = mail_html(
        'Confirma tu aviso de gasolina',
        '<p style="margin:0 0 12px">Has pedido que te avisemos <strong>' . e($rule) . '</strong>.</p>'
            . '<p style="margin:0">Pulsa el botón para confirmar que este correo es tuyo. Solo hace falta la primera vez.</p>',
        'Confirmar aviso',
        $url,
        'Si no has sido tú, no hagas nada: el aviso se borra solo a los 7 días.'
    );
    return send_mail((string) $a['email'], 'Confirma tu aviso: ' . fuel_name(fuel_key((string) $a['fuel'])) . ' ' . $zone['in'], $text, $html);
}

/** Correo de «aviso creado» para un correo ya comprobado (no hace falta confirmar). */
function fuel_zone_alert_mail_created(array $a, array $zone): bool
{
    $rule = fuel_zone_rule($a, $zone);
    [$footText, $footHtml] = fuel_mail_foot((string) $a['email'], absolute_url('gasolina/avisos/baja/' . $a['code']), 'Si no has sido tú, borra el aviso con el enlace.');
    $text = "Hola:\n\nHemos activado tu aviso: te escribiremos $rule.\n\n$footText\n\n" . site_name();
    $html = mail_html('Aviso de gasolina activado', '<p style="margin:0">Te escribiremos <strong>' . e($rule) . '</strong>.</p>', 'Ver mis avisos', fuel_manage_url((string) $a['email']), $footHtml);
    return send_mail((string) $a['email'], 'Aviso activado: ' . fuel_name(fuel_key((string) $a['fuel'])) . ' ' . $zone['in'], $text, $html);
}

/** Lista de gasolineras para un correo: [texto, html]. */
function fuel_mail_station_list(array $stations, string $fuel): array
{
    $lines = [];
    $html = '';
    foreach ($stations as $s) {
        $lines[] = '- ' . $s['name'] . ', ' . $s['address'] . ' (' . $s['muni_name'] . '): ' . fuel_money($s[$fuel]);
        $html .= '<tr><td style="padding:8px 0;border-top:1px solid #DDE2F1"><a href="' . e(absolute_url(fuel_station_path($s))) . '" style="color:#10162F;font-weight:bold;text-decoration:none">' . e($s['name']) . '</a>'
            . '<br><span style="color:#535B7C;font-size:14px">' . e($s['address'] . ', ' . $s['muni_name']) . '</span></td>'
            . '<td style="padding:8px 0 8px 12px;border-top:1px solid #DDE2F1;text-align:right;font-weight:bold;white-space:nowrap">' . e(fuel_money($s[$fuel])) . '</td></tr>';
    }
    return [implode("\n", $lines), $html ? '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:15px;line-height:1.4">' . $html . '</table>' : ''];
}

/**
 * Correo de un aviso por zona. $kind: 'down' o 'up' (cambio de la media), 'below' (alguna por debajo del precio)
 * o 'weekly' (resumen). $now = media de ahora; $ref = media de referencia (cambio o hace 7 días).
 */
function fuel_zone_alert_mail(array $a, array $zone, string $kind, ?float $now, ?float $ref): bool
{
    $fuel = fuel_key((string) $a['fuel']);
    $page = absolute_url($zone['path'] . ($fuel === 'g95' ? '' : '?c=' . $fuel));
    $off = absolute_url('gasolina/avisos/baja/' . $a['code']);
    $The = mb_strtoupper(mb_substr(fuel_the($fuel), 0, 1)) . mb_substr(fuel_the($fuel), 1);
    if ($kind === 'below') {
        $target = (float) $a['target'];
        $best = array_values(array_filter(fuel_stations($zone['where'], $zone['params'], $fuel, 5), fn ($s) => (float) $s[$fuel] <= $target + 0.0005));
        $title = $The . ' ya está a ' . fuel_money($target) . ' o menos ' . $zone['in'];
        $intro = count($best) === 1 ? 'Hay una gasolinera ' . $zone['in'] . ' por debajo de tu precio:' : 'Estas gasolineras ' . $zone['in'] . ' están por debajo de tu precio:';
        $subject = $title;
        $foot1 = 'Te volveremos a avisar si sube por encima y vuelve a bajar.';
    } elseif ($kind === 'weekly') {
        $best = fuel_stations($zone['where'], $zone['params'], $fuel, 3);
        $title = 'Tu resumen semanal ' . fuel_of($fuel) . ' ' . $zone['in'];
        $intro = 'El precio medio está a ' . fuel_money($now) . ($ref !== null ? ', ' . fuel_cents($now - $ref) . ' respecto al lunes pasado (' . fuel_money($ref) . ')' : '') . '. Las más baratas ahora:';
        $subject = 'Resumen semanal: ' . fuel_name($fuel) . ' ' . $zone['in'] . ' a ' . fuel_money($now);
        $foot1 = 'Te enviamos este resumen cada lunes.';
    } else {
        $up = $kind === 'up';
        $since = substr((string) $a['ref_at'], 0, 10) === date('Y-m-d') ? 'las ' . date('H:i', strtotime((string) $a['ref_at'])) . ' de hoy' : 'el ' . fuel_day_text((string) $a['ref_at']);
        $best = fuel_stations($zone['where'], $zone['params'], $fuel, 3);
        $title = ($up ? 'Sube ' : 'Baja ') . fuel_the($fuel) . ' ' . $zone['in'];
        $intro = 'El precio medio ' . fuel_of($fuel) . " {$zone['in']} está a " . fuel_money($now) . ', ' . fuel_cents($now - $ref) . " desde $since (" . fuel_money($ref) . '). '
            . ($up ? 'Si tienes que repostar pronto, estas son las más baratas ahora:' : 'Las más baratas ahora:');
        $subject = $title . ': ' . fuel_money($now) . ' (' . fuel_cents($now - $ref) . ')';
        $foot1 = 'Te avisamos cuando la media ' . fuel_zone_verb((string) $a['dir']) . ' ' . fuel_step_text((float) $a['step']) . ' o más, como mucho una vez al día.';
    }
    [$listText, $listHtml] = fuel_mail_station_list($best, $fuel);
    [$footText, $footHtml] = fuel_mail_foot((string) $a['email'], $off, $foot1);
    $text = "$intro\n$listText\n\nTodas las gasolineras:\n$page\n\nEl precio puede cambiar durante el día: el válido es el del surtidor.\n\n$footText\n\n" . site_name();
    $html = mail_html(
        $title,
        '<p style="margin:0 0 10px">' . e($intro) . '</p>' . $listHtml
            . '<p style="margin:14px 0 0;font-size:14px;color:#535B7C">El precio puede cambiar durante el día: el válido es el del surtidor.</p>',
        'Ver todas las gasolineras',
        $page,
        $footHtml
    );
    return send_mail((string) $a['email'], $subject, $text, $html);
}

/**
 * Revisa los avisos por zona después de cada importación.
 * - change: para un aviso de bajada, la referencia sube con el precio (avisa de la bajada desde el último máximo); para uno de subida, baja con él.
 * - below: avisa cuando la más barata de la zona llega al precio, y se vuelve a armar cuando sube medio céntimo por encima.
 * - weekly: los lunes desde las 7:00, una vez por semana.
 * Como mucho un correo al día por aviso y $max correos por pasada. Devuelve cuántos ha enviado.
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
    $weekDue = date('N') === '1' && (int) date('G') >= 7;
    $zones = [];
    $sent = 0;
    foreach ($alerts as $a) {
        $fuel = fuel_key((string) $a['fuel']);
        $mode = (string) ($a['mode'] ?: 'change');
        $now = $avgs[$a['zone']][$fuel] ?? null;
        $min = $avgs[$a['zone']][$fuel . '_min'] ?? null;
        if ($now === null) {
            continue;
        }
        $now = round((float) $now, 3);
        $tooSoon = (string) $a['sent_at'] > $recent;
        if ($mode === 'weekly') {
            if (!$weekDue || (string) $a['sent_at'] > date('Y-m-d H:i:s', time() - 6 * 86400) || $sent >= $max) {
                continue;
            }
            $zone = $zones[$a['zone']] ??= fuel_zone((string) $a['zone']);
            if ($zone && fuel_zone_alert_mail($a, $zone, 'weekly', $now, fuel_zone_avg_on($zone, $fuel, date('Y-m-d', strtotime('-7 days'))))) {
                update('zone_alerts', ['sent_at' => now(), 'sent_price' => $now, 'sent_count' => (int) $a['sent_count'] + 1], (int) $a['id']);
                $sent++;
            }
            continue;
        }
        if ($mode === 'below') {
            $target = (float) $a['target'];
            if ($min === null) {
                continue;
            }
            $min = (float) $min;
            if (!(int) $a['armed']) {
                if ($min > $target + 0.005) {
                    update('zone_alerts', ['armed' => 1], (int) $a['id']);
                }
                continue;
            }
            if ($min <= $target + 0.0005 && !$tooSoon && $sent < $max) {
                $zone = $zones[$a['zone']] ??= fuel_zone((string) $a['zone']);
                if ($zone && fuel_zone_alert_mail($a, $zone, 'below', $now, null)) {
                    update('zone_alerts', ['armed' => 0, 'sent_at' => now(), 'sent_price' => $min, 'sent_count' => (int) $a['sent_count'] + 1], (int) $a['id']);
                    $sent++;
                }
            }
            continue;
        }
        if ($a['ref_price'] === null) {
            update('zone_alerts', ['ref_price' => $now, 'ref_at' => now()], (int) $a['id']);
            continue;
        }
        $ref = (float) $a['ref_price'];
        $step = (float) $a['step'] - 0.0005;
        $down = $a['dir'] !== 'up' && $now - $ref <= -$step;
        $up = $a['dir'] !== 'down' && $now - $ref >= $step;
        if ($down || $up) {
            if ($tooSoon || $sent >= $max) {
                continue;
            }
            $zone = $zones[$a['zone']] ??= fuel_zone((string) $a['zone']);
            if ($zone && fuel_zone_alert_mail($a, $zone, $up ? 'up' : 'down', $now, $ref)) {
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

/**
 * Precio del día de cada gasolinera y medias por provincia y de España a partir de un listado del Ministerio.
 * Devuelve [filas de fuel_daily, filas de fuel_stats]. Solo venta al público y precios con sentido.
 */
function fuel_build_day(array $list, string $day): array
{
    $num = function ($v): ?float {
        $v = trim((string) $v);
        if ($v === '') {
            return null;
        }
        $f = (float) str_replace(',', '.', $v);
        return $f > 0.3 && $f < 5 ? round($f, 3) : null;
    };
    $daily = [];
    $acc = [];
    foreach ($list as $r) {
        if (($r['Tipo Venta'] ?? 'P') !== 'P') {
            continue;
        }
        $id = (int) ($r['IDEESS'] ?? 0);
        $prov = (int) ($r['IDProvincia'] ?? 0);
        if ($id <= 0 || !isset(FUEL_PROVINCES[$prov])) {
            continue;
        }
        $row = ['station_id' => $id, 'day' => $day];
        foreach (FUELS_MAIN as $f) {
            $row[$f] = $num($r[FUELS[$f][1]] ?? '');
            if ($row[$f] !== null) {
                foreach ([0, $prov] as $p) {
                    $acc[$f][$p]['sum'] = ($acc[$f][$p]['sum'] ?? 0) + $row[$f];
                    $acc[$f][$p]['n'] = ($acc[$f][$p]['n'] ?? 0) + 1;
                    $acc[$f][$p]['min'] = min($acc[$f][$p]['min'] ?? 99, $row[$f]);
                }
            }
        }
        if ($row['g95'] !== null || $row['diesel'] !== null || $row['g98'] !== null) {
            $daily[$id] = $row;
        }
    }
    $stats = [];
    foreach ($acc as $f => $byProv) {
        foreach ($byProv as $p => $a) {
            $stats[] = ['day' => $day, 'province_id' => $p, 'fuel' => $f, 'avg' => round($a['sum'] / $a['n'], 3), 'min' => $a['min'], 'stations' => $a['n']];
        }
    }
    return [array_values($daily), $stats];
}
