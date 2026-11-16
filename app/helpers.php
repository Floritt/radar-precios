<?php
declare(strict_types=1);

const STORES = [
    'amazon' => 'Amazon',
    'temu' => 'Temu',
    'shein' => 'Shein',
    'other' => 'Otra tienda',
];

/* ---------- Salida y URLs ---------- */

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path(): string
{
    $path = parse_url((string) config('base_url', ''), PHP_URL_PATH) ?: '';
    return rtrim($path, '/');
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function absolute_url(string $path = ''): string
{
    return rtrim((string) config('base_url', ''), '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = ROOT . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? substr((string) filemtime($file), -6) : '1';
    return url('assets/' . ltrim($path, '/')) . '?v=' . $v;
}

function upload_url(?string $path): ?string
{
    return $path ? url('uploads/' . ltrim($path, '/')) : null;
}

function redirect(string $path): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

function request_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_path();
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }
    $path = '/' . trim(rawurldecode($path), '/');
    return $path === '/index.php' ? '/' : $path;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, $default = '')
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

/* ---------- Texto, fechas y precios ---------- */

function now(): string
{
    return date('Y-m-d H:i:s');
}

function slugify(string $text): string
{
    $text = mb_strtolower(trim($text));
    $map = ['á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i', 'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u', 'ñ' => 'n', 'ç' => 'c', '·' => '-', "'" => ''];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'sin-titulo';
}

function unique_slug(string $table, string $base, ?int $ignoreId = null): string
{
    $slug = slugify($base);
    $candidate = $slug;
    $i = 2;
    while (true) {
        $params = [$candidate];
        $sql = "SELECT id FROM $table WHERE slug = ?";
        if ($ignoreId) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        if (!val($sql, $params)) {
            return $candidate;
        }
        $candidate = $slug . '-' . $i++;
    }
}

function money($amount): string
{
    if ($amount === null || $amount === '') {
        return '';
    }
    return number_format((float) $amount, 2, ',', '.') . ' €';
}

/** Divide un precio en euros y céntimos para la etiqueta: 19,99 → ['19', '99']. */
function price_parts($amount): array
{
    $formatted = number_format((float) $amount, 2, ',', '.');
    [$int, $dec] = explode(',', $formatted);
    return [$int, $dec];
}

function pct($fraction, bool $signed = true): string
{
    $v = round((float) $fraction * 100);
    $s = number_format(abs($v), 0, ',', '.') . ' %';
    if (!$signed) {
        return $s;
    }
    return ($v > 0 ? '+' : ($v < 0 ? '−' : '')) . $s;
}

const MONTHS_ES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
const DAYS_ES = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

function date_es(?string $datetime, bool $withTime = false): string
{
    if (!$datetime) {
        return '';
    }
    $t = strtotime($datetime);
    $s = (int) date('j', $t) . ' de ' . MONTHS_ES[(int) date('n', $t) - 1] . ' de ' . date('Y', $t);
    return $withTime ? $s . ', ' . date('H:i', $t) : $s;
}

function date_short(?string $datetime): string
{
    return $datetime ? date('d/m/Y H:i', strtotime($datetime)) : '';
}

function excerpt(string $text, int $length = 160): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $length - 1), " ,.;:") . '…';
}

function lines(?string $text): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $text) ?: [])));
}

/* ---------- Ajustes editables desde el panel ---------- */

function setting(string $name, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (rows('SELECT name, value FROM settings') as $r) {
                $cache[$r['name']] = (string) $r['value'];
            }
        } catch (Throwable $e) {
            $cache = [];
        }
    }
    return ($cache[$name] ?? '') !== '' ? $cache[$name] : $default;
}

function save_setting(string $name, string $value): void
{
    if (val('SELECT COUNT(*) FROM settings WHERE name = ?', [$name])) {
        q('UPDATE settings SET value = ? WHERE name = ?', [$value, $name]);
    } else {
        q('INSERT INTO settings (name, value) VALUES (?, ?)', [$name, $value]);
    }
}

function site_name(): string
{
    return setting('site_name', 'Radar de Precios');
}

/* ---------- Sesión, CSRF y mensajes ---------- */

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('rp_sess');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => base_path() . '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    start_session();
    $sent = (string) ($_POST['_csrf'] ?? '');
    if (!hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(419);
        exit('La sesión ha caducado. Vuelve atrás, recarga la página y repite la acción.');
    }
}

function flash(?string $message = null, string $type = 'ok'): ?array
{
    start_session();
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- Vistas ---------- */

function view(string $name, array $vars = []): string
{
    extract($vars, EXTR_SKIP);
    ob_start();
    include APP . '/views/' . $name . '.php';
    return (string) ob_get_clean();
}

function render(string $name, array $vars = [], string $layout = 'layout'): void
{
    $vars['content'] = view($name, $vars);
    echo view($layout, $vars);
}

function partial(string $name, array $vars = []): void
{
    echo view('partials/' . $name, $vars);
}

function not_found(): never
{
    http_response_code(404);
    render('404', ['meta' => meta(['title' => 'Página no encontrada', 'noindex' => true])]);
    exit;
}

function send_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https://m.media-amazon.com https://images-na.ssl-images-amazon.com https://images-eu.ssl-images-amazon.com; style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self'; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
}

/* ---------- Datos comunes ---------- */

function all_niches(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = rows(
            "SELECT n.*, (SELECT COUNT(*) FROM products p WHERE p.niche_id = n.id AND p.status = 'published') AS product_count
             FROM niches n ORDER BY n.sort_order, n.name"
        );
    }
    return $cache;
}

function niche_by_id(?int $id): ?array
{
    if (!$id) {
        return null;
    }
    foreach (all_niches() as $n) {
        if ((int) $n['id'] === $id) {
            return $n;
        }
    }
    return null;
}

function has_demo_content(): bool
{
    try {
        return (int) val('SELECT COUNT(*) FROM products WHERE is_demo = 1')
            + (int) val('SELECT COUNT(*) FROM posts WHERE is_demo = 1') > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function post_url(array $post): string
{
    return url(post_section($post['type'])[1] . '/' . $post['slug']);
}

function product_url(array $product): string
{
    return url('producto/' . $product['slug']);
}

function niche_url(array $niche): string
{
    return url('nicho/' . $niche['slug']);
}

const POST_TYPES = ['blog' => 'Artículo', 'guide' => 'Guía de compra', 'review' => 'Reseña', 'comparison' => 'Comparativa'];

/** Sección pública de cada tipo de artículo: [nombre, ruta]. */
function post_section(string $type): array
{
    return match ($type) {
        'review' => ['Reseñas', 'resenas'],
        'comparison' => ['Comparativas', 'comparativas'],
        default => ['Guías y artículos', 'blog'],
    };
}

/** Condición SQL para listar una sección. */
function section_where(string $kind): string
{
    return match ($kind) {
        'review' => "po.type = 'review'",
        'comparison' => "po.type = 'comparison'",
        default => "po.type NOT IN ('review', 'comparison')",
    };
}

/* ---------- Subida de imágenes ---------- */

/** Guarda una imagen subida (JPG, PNG o WebP, máx. 4 MB) y devuelve su ruta relativa. */
function save_upload(string $field): ?string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo subir la imagen (código ' . $file['error'] . ').');
    }
    if ($file['size'] > 4 * 1024 * 1024) {
        throw new RuntimeException('La imagen pesa más de 4 MB. Redúcela y vuelve a subirla.');
    }
    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]])) {
        throw new RuntimeException('Solo se admiten imágenes JPG, PNG o WebP.');
    }
    $dir = date('Y/m');
    if (!is_dir(ROOT . '/uploads/' . $dir)) {
        mkdir(ROOT . '/uploads/' . $dir, 0755, true);
    }
    $name = $dir . '/' . bin2hex(random_bytes(8)) . '.' . $types[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], ROOT . '/uploads/' . $name)) {
        throw new RuntimeException('No se pudo guardar la imagen en el servidor. Revisa los permisos de la carpeta uploads.');
    }
    return $name;
}

function log_line(string $file, string $message): void
{
    @file_put_contents(ROOT . '/storage/logs/' . $file . '-' . date('Y-m') . '.log', '[' . now() . '] ' . $message . PHP_EOL, FILE_APPEND);
}

/* ---------- Ayudas de presentación ---------- */

/** Foto del producto: la subida desde el panel o, si no hay, la de Amazon (solo con precio vigente). */
function product_image(array $p): ?string
{
    if (!empty($p['image_path'])) {
        return upload_url($p['image_path']);
    }
    if (!empty($p['amazon_image_url']) && amazon_price_visible($p)) {
        return $p['amazon_image_url'];
    }
    return null;
}

/** Icono neutro para productos sin foto, elegido por el nicho. */
function placeholder_svg(?string $nicheSlug): string
{
    $s = (string) $nicheSlug;
    $paths = [
        'decor' => '<path d="M32 8 20 26h7l-10 14h8L14 54h36L39 40h8L37 26h7Z"/><path d="M32 54v6"/>',
        'jugu' => '<rect x="12" y="34" width="18" height="18" rx="2"/><rect x="34" y="34" width="18" height="18" rx="2"/><rect x="23" y="14" width="18" height="18" rx="2"/>',
        'perfum' => '<rect x="18" y="26" width="28" height="28" rx="6"/><path d="M26 26v-8h12v8M28 18v-6h8v6"/>',
        'jersei' => '<path d="M22 12h20l12 8-6 10-6-4v28H22V26l-6 4-6-10Z"/><path d="M26 12c0 4 3 6 6 6s6-2 6-6"/>',
        'pijama' => '<path d="M22 12h20l12 8-6 10-6-4v28H22V26l-6 4-6-10Z"/>',
        'cocina' => '<path d="M12 28h40v6a14 14 0 0 1-14 14H26a14 14 0 0 1-14-14Z"/><path d="M8 28h48M24 20c0-3 3-3 3-6M33 20c0-3 3-3 3-6"/>',
        'tecno' => '<path d="M14 40v-6a18 18 0 0 1 36 0v6"/><rect x="10" y="38" width="10" height="14" rx="3"/><rect x="44" y="38" width="10" height="14" rx="3"/>',
    ];
    $inner = '<path d="M12 30V14a2 2 0 0 1 2-2h16l22 22-18 18Z"/><circle cx="21" cy="21" r="3"/>';
    foreach ($paths as $key => $svg) {
        if (str_contains($s, $key)) {
            $inner = $svg;
            break;
        }
    }
    return '<svg class="ph" viewBox="0 0 64 64" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round">' . $inner . '</svg>';
}

function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }
    $diff = time() - strtotime($datetime);
    if ($diff < 3600) {
        return 'hace ' . max(1, (int) floor($diff / 60)) . ' min';
    }
    if ($diff < 86400) {
        return 'hace ' . (int) floor($diff / 3600) . ' h';
    }
    $days = (int) floor($diff / 86400);
    return $days === 1 ? 'ayer' : 'hace ' . $days . ' días';
}

function store_count(array $p): int
{
    return (int) (!empty($p['asin']) || !empty($p['amazon_url'])) + (int) !empty($p['temu_url']) + (int) !empty($p['shein_url']) + (int) !empty($p['other_url']);
}

/** Rebaja frente a la media de 90 días, solo si el mejor precio es de la misma tienda que el historial. */
function deal_discount(array $p): ?float
{
    $tp = tag_price($p);
    if (!$tp || !$p['avg90'] || ($p['stats_store'] ?? null) !== $tp['store']) {
        return null;
    }
    $d = 1 - $tp['price'] / (float) $p['avg90'];
    return $d >= 0.03 ? $d : null;
}

function store_label(array $p, string $store): string
{
    return $store === 'other' ? (($p['other_name'] ?? '') ?: 'Otra tienda') : (STORES[$store] ?? $store);
}

/** Cuántos productos y reseñas publicadas hay, para ocultar secciones vacías. */
function site_counts(): array
{
    static $c = null;
    if ($c === null) {
        $c = [
            'products' => (int) val("SELECT COUNT(*) FROM products WHERE status = 'published'"),
            'priced' => (int) val("SELECT COUNT(*) FROM products WHERE status = 'published' AND best_price IS NOT NULL"),
            'reviews' => (int) val("SELECT COUNT(*) FROM posts WHERE type = 'review' AND status = 'published' AND published_at <= ?", [now()]),
            'comparisons' => (int) val("SELECT COUNT(*) FROM posts WHERE type = 'comparison' AND status = 'published' AND published_at <= ?", [now()]),
        ];
    }
    return $c;
}

/** Enlace de afiliado a una búsqueda de Amazon, con el ID de seguimiento de Ajustes. */
function amazon_search_url(string $query): string
{
    $tag = amazon_tag();
    return 'https://' . config('amazon.marketplace', 'www.amazon.es') . '/s?k=' . rawurlencode($query) . ($tag ? '&tag=' . rawurlencode($tag) : '');
}

/** Atributo style con el color de la categoría, para teñir los iconos de las fichas sin foto. */
function tint_attr(array $row): string
{
    $c = (string) ($row['niche_color'] ?? '');
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $c) ? ' style="--tint: ' . $c . '"' : '';
}

/** Variante de tono (0 a 2) para que las portadas de una misma categoría no sean todas iguales. */
function cover_shade(string $slug): int
{
    return crc32($slug) % 3;
}

/** Portada de artículo sin foto: color de la categoría y su icono. */
function article_cover(array $post, string $class): string
{
    $variant = isset($post['slug']) && crc32((string) $post['slug']) % 2 ? ' acover--b' : '';
    return '<span class="' . $class . ' acover' . $variant . '">' . placeholder_svg($post['niche_slug'] ?? '') . '</span>';
}
