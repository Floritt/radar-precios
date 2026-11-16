<?php
declare(strict_types=1);

/** Metadatos de una página con valores por defecto razonables. */
function meta(array $m = []): array
{
    $defaults = [
        'title' => '',
        'description' => setting('tagline', 'Historial de precios y reseñas para comprar en el mejor momento.'),
        'canonical' => absolute_url(ltrim(request_path(), '/')),
        'image' => null,
        'type' => 'website',
        'noindex' => has_demo_content(),
        'jsonld' => [],
    ];
    $m = array_merge($defaults, $m);
    $m['full_title'] = $m['title'] ? $m['title'] . ' | ' . site_name() : site_name() . ': ' . setting('tagline', 'precios y reseñas');
    return $m;
}

function jsonld_breadcrumbs(array $items): array
{
    $list = [];
    foreach ($items as $i => [$name, $path]) {
        $list[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $name, 'item' => absolute_url($path)];
    }
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list];
}

function jsonld_article(array $post): array
{
    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $post['title'],
        'datePublished' => date('c', strtotime($post['published_at'] ?: $post['created_at'])),
        'dateModified' => date('c', strtotime($post['updated_at'])),
        'author' => ['@type' => 'Person', 'name' => $post['author'] ?: site_name()],
        'publisher' => ['@type' => 'Organization', 'name' => site_name()],
        'mainEntityOfPage' => absolute_url(ltrim(request_path(), '/')),
    ];
    if (!empty($post['cover_path'])) {
        $data['image'] = absolute_url('uploads/' . $post['cover_path']);
    }
    if ($post['type'] === 'review' && $post['rating'] !== null && !empty($post['product_name'])) {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Review',
            'name' => $post['title'],
            'author' => ['@type' => 'Person', 'name' => $post['author'] ?: site_name()],
            'datePublished' => $data['datePublished'],
            'reviewRating' => ['@type' => 'Rating', 'ratingValue' => (float) $post['rating'], 'bestRating' => 10, 'worstRating' => 0],
            'itemReviewed' => ['@type' => 'Product', 'name' => $post['product_name']],
        ];
    }
    return $data;
}

/**
 * Título del artículo para buscadores: el corto si lo tiene, con el año de la última revisión.
 * El año va en el título y no en la dirección, para no tener que redirigir cada enero.
 */
function post_seo_title(array $post): string
{
    $t = trim((string) ($post['seo_title'] ?? '')) ?: (string) $post['title'];
    if (!preg_match('/\b20\d\d\b/', $t)) {
        $t .= ' (' . date('Y', strtotime((string) ($post['updated_at'] ?: $post['published_at'] ?: 'now'))) . ')';
    }
    return $t;
}

/** Preguntas del apartado "## Preguntas frecuentes" de un artículo: cada "### pregunta" con su respuesta en texto plano. */
function post_faqs(string $body): array
{
    if (!preg_match('/^##\s+Preguntas frecuentes\s*$(.*?)(?=^##\s|\z)/msu', $body, $m)) {
        return [];
    }
    $faqs = [];
    foreach (preg_split('/^###\s+/m', $m[1]) as $i => $chunk) {
        if ($i === 0) {
            continue;
        }
        [$q, $a] = array_pad(explode("\n", $chunk, 2), 2, '');
        $a = html_entity_decode(strip_tags(render_markdown(trim($a))), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $a = trim((string) preg_replace('/\s+/u', ' ', $a));
        if (trim($q) !== '' && $a !== '') {
            $faqs[] = [trim($q), $a];
        }
    }
    return $faqs;
}

function jsonld_faq(array $faqs): array
{
    return [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(fn($f) => [
            '@type' => 'Question',
            'name' => $f[0],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
        ], $faqs),
    ];
}
