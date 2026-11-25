<?php
declare(strict_types=1);

/**
 * Estadísticas de visitas sin cookies ni datos personales. Cada visita suma 1 en contadores por día:
 * página, hora, país aproximado (por la zona horaria del navegador), idioma, dispositivo, navegador,
 * sistema y web de procedencia. No se guarda la IP ni nada que permita reconocer a una persona.
 */

/** Tipos de página que se cuentan, con su nombre para el panel. */
const VISIT_KINDS = [
    'home' => 'Portada',
    'product' => 'Fichas de producto',
    'post' => 'Guías, comparativas y reseñas',
    'list' => 'Listados',
    'fuel' => 'Gasolina: portada',
    'fprov' => 'Gasolina: provincias',
    'fmuni' => 'Gasolina: municipios',
    'fstat' => 'Gasolina: gasolineras',
    'fnear' => 'Gasolina: cerca de mí',
    'fsearch' => 'Gasolina: búsquedas',
    'falert' => 'Gasolina: avisos',
];

/** Suma 1 a un contador (tabla con clave compuesta), sin carreras si dos visitas llegan a la vez. */
function stat_bump(string $table, array $key, int $by = 1): void
{
    $where = implode(' AND ', array_map(fn ($c) => "$c = ?", array_keys($key)));
    $col = $table === 'search_terms' ? 'hits' : 'views';
    if (q("UPDATE $table SET $col = $col + ? WHERE $where", array_merge([$by], array_values($key)))->rowCount()) {
        return;
    }
    try {
        insert($table, $key + [$col => $by]);
    } catch (Throwable $e) {
        q("UPDATE $table SET $col = $col + ? WHERE $where", array_merge([$by], array_values($key)));
    }
}

/** País (código ISO) a partir de la zona horaria del navegador, como «Europe/Madrid». */
function tz_country(string $tz): string
{
    if (!preg_match('~^[A-Za-z_]+(/[A-Za-z0-9_\-+]+){1,2}$~', $tz)) {
        return '';
    }
    try {
        $loc = (new DateTimeZone($tz))->getLocation();
        $cc = is_array($loc) ? strtoupper((string) ($loc['country_code'] ?? '')) : '';
        return preg_match('/^[A-Z]{2}$/', $cc) ? $cc : '';
    } catch (Throwable $e) {
        return '';
    }
}

function country_name(string $cc): string
{
    static $names = ['ES' => 'España', 'PT' => 'Portugal', 'FR' => 'Francia', 'DE' => 'Alemania', 'IT' => 'Italia', 'GB' => 'Reino Unido',
        'IE' => 'Irlanda', 'NL' => 'Países Bajos', 'BE' => 'Bélgica', 'CH' => 'Suiza', 'AD' => 'Andorra', 'US' => 'Estados Unidos',
        'MX' => 'México', 'AR' => 'Argentina', 'CO' => 'Colombia', 'CL' => 'Chile', 'PE' => 'Perú', 'VE' => 'Venezuela', 'EC' => 'Ecuador',
        'UY' => 'Uruguay', 'PY' => 'Paraguay', 'BO' => 'Bolivia', 'DO' => 'República Dominicana', 'CU' => 'Cuba', 'MA' => 'Marruecos',
        'RO' => 'Rumanía', 'PL' => 'Polonia', 'SE' => 'Suecia', 'NO' => 'Noruega', 'DK' => 'Dinamarca', 'FI' => 'Finlandia', 'AT' => 'Austria',
        'BR' => 'Brasil', 'CA' => 'Canadá', 'CN' => 'China', 'IN' => 'India', 'RU' => 'Rusia', 'UA' => 'Ucrania', 'GR' => 'Grecia'];
    if (isset($names[$cc])) {
        return $names[$cc];
    }
    if ($cc !== '' && class_exists('Locale')) {
        $n = Locale::getDisplayRegion('-' . $cc, 'es');
        if ($n !== '' && $n !== $cc) {
            return $n;
        }
    }
    return $cc ?: 'Desconocido';
}

/** Zona de España que se puede distinguir por la hora: Canarias tiene una hora menos. */
function tz_area(string $tz, string $cc): string
{
    if ($cc !== 'ES') {
        return '';
    }
    return $tz === 'Atlantic/Canary' ? 'Canarias' : ($tz === 'Africa/Ceuta' ? 'Ceuta y Melilla' : 'Península y Baleares');
}

function ua_browser(string $ua): string
{
    return match (true) {
        $ua === '' => 'Desconocido',
        (bool) preg_match('~SamsungBrowser~i', $ua) => 'Samsung Internet',
        (bool) preg_match('~Edg(e|A|iOS)?/~', $ua) => 'Edge',
        (bool) preg_match('~OPR/|Opera~', $ua) => 'Opera',
        (bool) preg_match('~Firefox/|FxiOS~', $ua) => 'Firefox',
        (bool) preg_match('~CriOS|Chrome/~', $ua) => 'Chrome',
        (bool) preg_match('~Safari/~', $ua) => 'Safari',
        default => 'Otro',
    };
}

function ua_os(string $ua): string
{
    return match (true) {
        (bool) preg_match('~iPhone|iPad|iPod~', $ua) => 'iOS',
        (bool) preg_match('~Android~', $ua) => 'Android',
        (bool) preg_match('~Windows~', $ua) => 'Windows',
        (bool) preg_match('~Mac OS X|Macintosh~', $ua) => 'macOS',
        (bool) preg_match('~CrOS~', $ua) => 'ChromeOS',
        (bool) preg_match('~Linux~', $ua) => 'Linux',
        default => 'Otro',
    };
}

function screen_device(int $w, string $ua): string
{
    if ($w <= 0) {
        return preg_match('~Mobi|iPhone|Android~', $ua) ? 'Móvil' : 'Ordenador';
    }
    return $w < 768 ? 'Móvil' : ($w < 1100 ? 'Tableta' : 'Ordenador');
}

/** De dónde llega la visita, según la página anterior. Las visitas entre páginas propias no cuentan como entrada. */
function referrer_source(string $ref): ?string
{
    $host = strtolower((string) parse_url($ref, PHP_URL_HOST));
    if ($ref === '' || $host === '') {
        return 'Directo';
    }
    $own = strtolower((string) parse_url((string) config('base_url'), PHP_URL_HOST));
    if ($host === $own || $host === 'www.' . $own || str_ends_with($host, '.' . $own)) {
        return null;
    }
    $map = [
        'google.' => 'Google', 'bing.' => 'Bing', 'duckduckgo.' => 'DuckDuckGo', 'yahoo.' => 'Yahoo', 'ecosia.' => 'Ecosia', 'qwant.' => 'Qwant',
        'brave.' => 'Brave Search', 'facebook.' => 'Facebook', 'fb.' => 'Facebook', 'instagram.' => 'Instagram', 't.co' => 'X (Twitter)',
        'twitter.' => 'X (Twitter)', 'x.com' => 'X (Twitter)', 'linkedin.' => 'LinkedIn', 'pinterest.' => 'Pinterest', 'youtube.' => 'YouTube',
        'tiktok.' => 'TikTok', 'reddit.' => 'Reddit', 'whatsapp.' => 'WhatsApp', 'telegram.' => 'Telegram', 't.me' => 'Telegram',
        'chatgpt.' => 'ChatGPT', 'openai.' => 'ChatGPT', 'perplexity.' => 'Perplexity', 'claude.ai' => 'Claude', 'gemini.' => 'Gemini',
        'copilot.' => 'Copilot', 'forocoches.' => 'Forocoches', 'chollometro.' => 'Chollometro', 'mail.' => 'Correo electrónico',
    ];
    foreach ($map as $needle => $name) {
        if (str_contains($host, $needle)) {
            return $name;
        }
    }
    return mb_substr(preg_replace('/^www\./', '', $host), 0, 60);
}

/** Registra una visita con todos sus datos agregados. */
function record_visit(string $kind, int $ref, array $info): void
{
    $day = date('Y-m-d');
    stat_bump('visits', ['day' => $day, 'kind' => $kind, 'ref_id' => $ref]);
    stat_bump('visits_hourly', ['day' => $day, 'hour' => (int) date('G')]);
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    $tz = mb_substr((string) ($info['tz'] ?? ''), 0, 60);
    $cc = tz_country($tz);
    $lang = strtolower(substr(preg_replace('/[^A-Za-z\-]/', '', (string) ($info['lang'] ?? '')), 0, 2));
    $dims = [
        'country' => $cc ?: '??',
        'area' => tz_area($tz, $cc),
        'lang' => $lang ?: '??',
        'device' => screen_device((int) ($info['w'] ?? 0), $ua),
        'browser' => ua_browser($ua),
        'os' => ua_os($ua),
    ];
    $src = referrer_source((string) ($info['src'] ?? ''));
    if ($src !== null) {
        $dims['source'] = $src;
    }
    foreach ($dims as $dim => $val) {
        if ($val !== '') {
            stat_bump('visit_dims', ['day' => $day, 'dim' => $dim, 'val' => mb_substr($val, 0, 60)]);
        }
    }
}

/** Guarda una búsqueda (texto normalizado), para ver qué busca la gente. */
function record_search(string $scope, string $term): void
{
    $term = mb_strtolower(trim(preg_replace('/\s+/', ' ', $term)));
    if (mb_strlen($term) < 2 || mb_strlen($term) > 80 || is_untracked_request()) {
        return;
    }
    stat_bump('search_terms', ['day' => date('Y-m-d'), 'scope' => $scope, 'term' => $term]);
}

/** Correo con la mitad tapada para listados del panel: j***@gmail.com */
function mask_email(string $email): string
{
    [$user, $domain] = array_pad(explode('@', $email, 2), 2, '');
    return mb_substr($user, 0, 1) . str_repeat('*', max(2, min(6, mb_strlen($user) - 1))) . '@' . $domain;
}
