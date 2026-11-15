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
