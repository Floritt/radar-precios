<?php
/**
 * Publica los artículos de la carpeta content/ y, con --borrar-ejemplo, quita los productos y artículos de ejemplo.
 * php cron/contenido.php [--borrar-ejemplo]
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

if (in_array('--borrar-ejemplo', $argv, true)) {
    foreach (array_column(rows('SELECT id FROM products WHERE is_demo = 1'), 'id') as $id) {
        q('DELETE FROM prices WHERE product_id = ?', [(int) $id]);
        q('DELETE FROM clicks WHERE product_id = ?', [(int) $id]);
    }
    $p = q('DELETE FROM products WHERE is_demo = 1')->rowCount();
    $a = q('DELETE FROM posts WHERE is_demo = 1')->rowCount();
    echo "Ejemplo borrado: $p productos, $a artículos\n";
}

$author = setting('default_author') ?: (string) val('SELECT name FROM users ORDER BY id LIMIT 1') ?: '';
$niches = [];
foreach (rows('SELECT id, slug FROM niches') as $n) {
    $niches[$n['slug']] = (int) $n['id'];
}

$files = glob('content/*/*.md') ?: [];
sort($files);
$added = 0;
foreach ($files as $file) {
    $raw = (string) file_get_contents($file);
    if (!preg_match('/\A---\R(.*?)\R---\R(.*)\z/s', $raw, $m)) {
        echo "Sin cabecera: $file\n";
        continue;
    }
    $meta = [];
    foreach (preg_split('/\R/', $m[1]) as $line) {
        if (preg_match('/^(\w+):\s*(.*)$/', $line, $kv)) {
            $meta[$kv[1]] = trim($kv[2], " \"'");
        }
    }
    $slug = $meta['slug'] ?? basename($file, '.md');
    if (val('SELECT id FROM posts WHERE slug = ?', [$slug])) {
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
        'excerpt' => $meta['excerpt'] ?? '',
        'body' => trim($m[2]) . "\n",
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
