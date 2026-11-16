<?php
/**
 * Publica los productos (content/productos) y artículos (resto de content/) y, con --borrar-ejemplo, quita los de ejemplo.
 * php cron/contenido.php [--borrar-ejemplo] [--actualizar] [--nombre=BASE64]
 * Cada archivo .md empieza con una cabecera entre líneas "---" (title, type, niche, excerpt). No toca los artículos
 * que ya existan con el mismo slug, así que se puede ejecutar varias veces.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
chdir(dirname(__DIR__));
require 'app/bootstrap.php';
ensure_schema();

// --nombre=BASE64 cambia el nombre de la web (en base64 para poder pasar acentos desde una tarea cron).
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--nombre=') && ($name = base64_decode(substr($arg, 9), true)) !== false && trim($name) !== '') {
        save_setting('site_name', mb_substr(trim($name), 0, 80));
        echo 'Nombre de la web: ', trim($name), "\n";
    }
}

if (in_array('--borrar-ejemplo', $argv, true)) {
    foreach (array_column(rows('SELECT id FROM products WHERE is_demo = 1'), 'id') as $id) {
        q('DELETE FROM prices WHERE product_id = ?', [(int) $id]);
        q('DELETE FROM clicks WHERE product_id = ?', [(int) $id]);
    }
    $p = q('DELETE FROM products WHERE is_demo = 1')->rowCount();
    $a = q('DELETE FROM posts WHERE is_demo = 1')->rowCount();
    echo "Ejemplo borrado: $p productos, $a artículos\n";
}

$update = in_array('--actualizar', $argv, true);
$author = setting('default_author') ?: (string) val('SELECT name FROM users ORDER BY id LIMIT 1') ?: '';
// Nichos nuevos de content/nichos (los que ya existen no se tocan).
foreach (glob('content/nichos/*.md') ?: [] as $file) {
    $raw = (string) file_get_contents($file);
    if (!preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $raw, $m)) {
        continue;
    }
    $meta = [];
    foreach (explode("\n", $m[1]) as $line) {
        if (str_contains($line, ':')) {
            [$k, $v] = array_map('trim', explode(':', $line, 2));
            $meta[$k] = $v;
        }
    }
    $slug = basename($file, '.md');
    if (val('SELECT id FROM niches WHERE slug = ?', [$slug])) {
        continue;
    }
    insert('niches', [
        'name' => $meta['name'] ?? $slug,
        'slug' => $slug,
        'intro' => trim($m[2]),
        'color' => preg_match('/^#[0-9A-Fa-f]{6}$/', $meta['color'] ?? '') ? $meta['color'] : '#1F3FD8',
        'sort_order' => (int) ($meta['orden'] ?? 50),
        'is_demo' => 0,
        'created_at' => now(),
    ]);
    echo "Nicho: $slug\n";
}

$niches = [];
foreach (rows('SELECT id, slug FROM niches') as $n) {
    $niches[$n['slug']] = (int) $n['id'];
}

/** Lee un .md con cabecera "---" y devuelve [cabecera, cuerpo] o null. */
function read_md(string $file): ?array
{
    $raw = (string) file_get_contents($file);
    if (!preg_match('/\A---\R(.*?)\R---\R(.*)\z/s', $raw, $m)) {
        echo "Sin cabecera: $file\n";
        return null;
    }
    $meta = [];
    foreach (preg_split('/\R/', $m[1]) as $line) {
        if (preg_match('/^(\w+):\s*(.*)$/', $line, $kv)) {
            $meta[$kv[1]] = trim($kv[2], " \"'");
        }
    }
    return [$meta, trim($m[2]) . "\n"];
}

// Productos primero, para que los artículos puedan mostrarlos con [producto:slug].
$newProducts = 0;
foreach (glob('content/productos/*.md') ?: [] as $file) {
    if (!($md = read_md($file))) {
        continue;
    }
    [$meta, $body] = $md;
    $slug = $meta['slug'] ?? basename($file, '.md');
    if (val('SELECT id FROM products WHERE slug = ?', [$slug])) {
        continue;
    }
    $asin = isset($meta['asin']) ? extract_asin($meta['asin']) : null;
    $at = now();
    insert('products', [
        'niche_id' => $niches[$meta['niche'] ?? ''] ?? null,
        'name' => $meta['name'] ?? $slug,
        'slug' => $slug,
        'brand' => $meta['brand'] ?? '',
        'summary' => $body,
        'asin' => $asin,
        'amazon_url' => !$asin && !empty($meta['busqueda']) ? 'https://www.amazon.es/s?k=' . rawurlencode($meta['busqueda']) : null,
        'status' => 'published',
        'track' => 1,
        'is_demo' => 0,
        'created_at' => $at,
        'updated_at' => $at,
    ]);
    $newProducts++;
    echo "Producto: $slug\n";
}
echo "Productos nuevos: $newProducts\n";

$files = array_filter(glob('content/*/*.md') ?: [], fn ($f) => !str_starts_with($f, 'content/productos/') && !str_starts_with($f, 'content/nichos/'));
sort($files);
$added = 0;
foreach ($files as $file) {
    if (!($md = read_md($file))) {
        continue;
    }
    [$meta, $body] = $md;
    $slug = $meta['slug'] ?? basename($file, '.md');
    $existing = row('SELECT id, title, excerpt, seo_title, body, created_at, updated_at FROM posts WHERE slug = ?', [$slug]);
    $seoTitle = trim((string) ($meta['seo_title'] ?? '')) ?: null;
    if ($existing) {
        // Con --actualizar se refrescan los artículos que nadie ha tocado desde el panel.
        $changed = $existing['body'] !== $body || $existing['title'] !== ($meta['title'] ?? $slug) || (string) $existing['excerpt'] !== ($meta['excerpt'] ?? '') || $existing['seo_title'] !== $seoTitle;
        if ($update && $existing['updated_at'] === $existing['created_at'] && $changed) {
            q('UPDATE posts SET title = ?, seo_title = ?, excerpt = ?, body = ? WHERE id = ?', [$meta['title'] ?? $slug, $seoTitle, $meta['excerpt'] ?? '', $body, (int) $existing['id']]);
            echo "Actualizado: $slug\n";
        }
        continue;
    }
    $type = isset(POST_TYPES[$meta['type'] ?? '']) ? $meta['type'] : 'guide';
    $at = now();
    insert('posts', [
        'type' => $type,
        'niche_id' => $niches[$meta['niche'] ?? ''] ?? null,
        'product_id' => null,
        'title' => $meta['title'] ?? $slug,
        'slug' => $slug,
        'seo_title' => $seoTitle,
        'excerpt' => $meta['excerpt'] ?? '',
        'body' => $body,
        'author' => $author,
        'status' => 'published',
        'is_demo' => 0,
        'published_at' => $at,
        'created_at' => $at,
        'updated_at' => $at,
    ]);
    $added++;
    echo "Publicado: $slug\n";
}
echo "Artículos nuevos: $added. Total publicados: " . val("SELECT COUNT(*) FROM posts WHERE status = 'published'") . "\n";
