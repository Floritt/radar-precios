<?php
declare(strict_types=1);

require_once APP . '/lib/Parsedown.php';

/**
 * Convierte el texto de un artículo (Markdown) en HTML.
 * Atajos disponibles, cada uno en su propia línea:
 *   [producto:slug-del-producto]         Ficha con precio y botones de compra
 *   [comparativa:slug-uno,slug-dos,...]  Tabla comparando varios productos
 */
function render_markdown(?string $text): string
{
    $md = new Parsedown();
    $md->setSafeMode(true);
    $md->setBreaksEnabled(false);
    $html = $md->text((string) $text);

    $html = preg_replace_callback('~<p>\[producto:([a-z0-9\-]+)\]</p>~', function ($m) {
        $p = row("SELECT p.*, n.name AS niche_name, n.color AS niche_color, n.slug AS niche_slug FROM products p LEFT JOIN niches n ON n.id = p.niche_id WHERE p.slug = ? AND p.status = 'published'", [$m[1]]);
        return $p ? view('partials/product-box', ['p' => $p]) : '';
    }, $html) ?? $html;

    $html = preg_replace_callback('~<p>\[comparativa:([a-z0-9\-,\s]+)\]</p>~', function ($m) {
        $slugs = array_filter(array_map('trim', explode(',', $m[1])));
        $items = [];
        foreach ($slugs as $slug) {
            $p = row("SELECT p.*, n.name AS niche_name, n.color AS niche_color, n.slug AS niche_slug FROM products p LEFT JOIN niches n ON n.id = p.niche_id WHERE p.slug = ? AND p.status = 'published'", [$slug]);
            if ($p) {
                $items[] = $p;
            }
        }
        return $items ? view('partials/compare-table', ['items' => $items]) : '';
    }, $html) ?? $html;

    // Las tablas de Markdown se envuelven para poder desplazarse en el móvil.
    $html = str_replace(['<table>', '</table>'], ['<div class="table-scroll"><table>', '</table></div>'], $html);
    return $html;
}

function reading_minutes(?string $text): int
{
    $words = str_word_count(strip_tags((string) $text), 0, 'áéíóúñüÁÉÍÓÚÑÜ');
    return max(1, (int) round($words / 220));
}
