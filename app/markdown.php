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

    // [amazon:búsqueda|Texto del botón]: solo en su párrafo es un botón; dentro de una frase, un enlace.
    $amazon = function (array $m, bool $block): string {
        $query = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
        $label = isset($m[2]) && trim($m[2]) !== '' ? trim(html_entity_decode($m[2], ENT_QUOTES, 'UTF-8')) : 'Ver ' . $query . ' en Amazon';
        $a = '<a href="' . e(amazon_search_url($query)) . '" rel="sponsored nofollow noopener" target="_blank"' . ($block ? ' class="btn btn--buy"' : '') . '>' . e($label) . '</a>';
        return $block ? '<p class="amz-cta">' . $a . '</p>' : $a;
    };
    $html = preg_replace_callback('~<p>\[amazon:([^\]|<]{2,90})(?:\|([^\]<]{1,90}))?\]</p>~u', fn ($m) => $amazon($m, true), $html) ?? $html;
    $html = preg_replace_callback('~\[amazon:([^\]|<]{2,90})(?:\|([^\]<]{1,90}))?\]~u', fn ($m) => $amazon($m, false), $html) ?? $html;

    // Cada h2 recibe un id para poder enlazarlo desde el índice del artículo.
    $seen = [];
    $html = preg_replace_callback('~<h2>(.*?)</h2>~s', function ($m) use (&$seen) {
        $id = slugify(html_entity_decode(strip_tags($m[1]), ENT_QUOTES, 'UTF-8')) ?: 'seccion';
        $base = $id;
        for ($i = 2; isset($seen[$id]); $i++) {
            $id = $base . '-' . $i;
        }
        $seen[$id] = true;
        return '<h2 id="' . $id . '">' . $m[1] . '</h2>';
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
