<?php
declare(strict_types=1);

function published_posts(string $where = '1=1', array $params = [], int $limit = 12, int $offset = 0): array
{
    return rows(
        "SELECT po.*, n.name AS niche_name, n.slug AS niche_slug, n.color AS niche_color
         FROM posts po LEFT JOIN niches n ON n.id = po.niche_id
         WHERE po.status = 'published' AND po.published_at <= ? AND ($where)
         ORDER BY po.published_at DESC, po.id DESC LIMIT $limit OFFSET $offset",
        array_merge([now()], $params)
    );
}

function page_home(): void
{
    $byDrop = 'p.at_low DESC, CASE WHEN p.change30 IS NULL THEN 1 ELSE 0 END, p.change30 ASC';
    $deals = product_list('(p.at_low = 1 OR p.change30 < 0) AND p.best_price IS NOT NULL', [], $byDrop, 8);
    $popular = rows(
        "SELECT p.*, n.name AS niche_name, n.slug AS niche_slug, n.color AS niche_color,
                (SELECT COUNT(*) FROM clicks c WHERE c.product_id = p.id AND c.created_at > ?) AS clicks30
         FROM products p LEFT JOIN niches n ON n.id = p.niche_id
         WHERE p.status = 'published' ORDER BY clicks30 DESC, p.updated_at DESC LIMIT 8",
        [date('Y-m-d H:i:s', time() - 30 * 86400)]
    );
    render('home', [
        'meta' => meta(['canonical' => absolute_url('')]),
        'deals' => $deals,
        'popular' => $popular,
        'reviews' => published_posts("po.type = 'review'", [], 4),
        'guides' => published_posts(section_where('blog'), [], $deals ? 5 : 7),
        'comparisons' => published_posts(section_where('comparison'), [], 4),
    ]);
}

function page_deals(): void
{
    $type = (string) input('tipo', '');
    $type = in_array($type, ['minimos', 'nuevos'], true) ? $type : '';
    $page = max(1, (int) input('pagina', '1'));
    $perPage = 20;
    $where = $type === 'minimos' ? 'p.at_low = 1' : ($type === 'nuevos' ? '1=1' : '(p.at_low = 1 OR p.change30 < 0)');
    $order = $type === 'nuevos' ? 'p.created_at DESC' : 'p.at_low DESC, CASE WHEN p.change30 IS NULL THEN 1 ELSE 0 END, p.change30 ASC';
    $params = [];
    $niche = null;
    if (($slug = (string) input('nicho', '')) !== '' && ($niche = row('SELECT * FROM niches WHERE slug = ?', [$slug]))) {
        $where .= ' AND p.niche_id = ?';
        $params[] = (int) $niche['id'];
    }
    $total = (int) val("SELECT COUNT(*) FROM products p WHERE p.status = 'published' AND ($where)", $params);
    $offset = ($page - 1) * $perPage;
    $products = rows(
        "SELECT p.*, n.name AS niche_name, n.slug AS niche_slug, n.color AS niche_color
         FROM products p LEFT JOIN niches n ON n.id = p.niche_id
         WHERE p.status = 'published' AND ($where) ORDER BY $order LIMIT $perPage OFFSET $offset",
        $params
    );
    render('deals', [
        'meta' => meta(['title' => 'Ofertas y bajadas de precio', 'description' => 'Productos que han bajado de precio frente a su media de 90 días, con su historial.', 'noindex' => $page > 1]),
        'products' => $products,
        'type' => $type,
        'niche' => $niche,
        'page' => $page,
        'pages' => max(1, (int) ceil($total / $perPage)),
    ]);
}

function page_niche(string $slug): void
{
    $niche = row('SELECT * FROM niches WHERE slug = ?', [$slug]);
    if (!$niche) {
        not_found();
    }
    $sort = input('orden', 'bajada');
    $orders = [
        'bajada' => 'CASE WHEN p.change30 IS NULL THEN 1 ELSE 0 END, p.change30 ASC',
        'precio' => 'CASE WHEN p.best_price IS NULL THEN 1 ELSE 0 END, p.best_price ASC',
        'nombre' => 'p.name ASC',
    ];
    $products = product_list('p.niche_id = ?', [(int) $niche['id']], $orders[$sort] ?? $orders['bajada'], 200);
    $posts = published_posts('po.niche_id = ?', [(int) $niche['id']], 6);

    render('niche', [
        'meta' => meta([
            'title' => $niche['name'] . ': precios y reseñas',
            'description' => excerpt($niche['intro'] ?: 'Historial de precios y reseñas de ' . mb_strtolower($niche['name']) . '.', 155),
            'jsonld' => [jsonld_breadcrumbs([['Inicio', ''], [$niche['name'], 'nicho/' . $niche['slug']]])],
        ]),
        'niche' => $niche,
        'products' => $products,
        'posts' => $posts,
        'sort' => isset($orders[$sort]) ? $sort : 'bajada',
    ]);
}

function page_product(string $slug): void
{
    $p = row(
        'SELECT p.*, n.name AS niche_name, n.slug AS niche_slug, n.color AS niche_color
         FROM products p LEFT JOIN niches n ON n.id = p.niche_id WHERE p.slug = ?',
        [$slug]
    );
    if (!$p || ($p['status'] !== 'published' && !current_user())) {
        not_found();
    }
    $offers = product_offers($p);
    $best = best_offer($offers);
    $store = primary_store((int) $p['id']);
    $stats = null;
    if ($store && ($store !== 'amazon' || amazon_price_visible($p))) {
        $stats = series_stats(daily_series((int) $p['id'], $store, 180));
    }
    $chart = chart_payload($p);
    $related = rows(
        "SELECT po.*, n.name AS niche_name, n.slug AS niche_slug, n.color AS niche_color FROM posts po LEFT JOIN niches n ON n.id = po.niche_id
         WHERE po.status = 'published' AND po.published_at <= ? AND (po.product_id = ? OR po.body LIKE ?) ORDER BY po.published_at DESC LIMIT 3",
        [now(), (int) $p['id'], '%[producto:' . $p['slug'] . ']%']
    );
    $review = row("SELECT * FROM posts WHERE type = 'review' AND status = 'published' AND published_at <= ? AND product_id = ? AND rating IS NOT NULL ORDER BY published_at DESC LIMIT 1", [now(), (int) $p['id']]);
    $similar = $p['niche_id'] ? product_list('p.niche_id = ? AND p.id <> ?', [(int) $p['niche_id'], (int) $p['id']], 'CASE WHEN p.change30 IS NULL THEN 1 ELSE 0 END, p.change30 ASC', 4) : [];

    render('product', [
        'meta' => meta([
            'title' => $p['name'] . ': historial de precios',
            'description' => excerpt($p['summary'] ?: 'Evolución del precio de ' . $p['name'] . ' en ' . implode(', ', array_column($offers, 'name')) . '. Mira si es buen momento para comprar.', 155),
            'image' => upload_url($p['image_path']) ? absolute_url('uploads/' . $p['image_path']) : null,
            'noindex' => $p['status'] !== 'published',
            'jsonld' => [jsonld_breadcrumbs(array_values(array_filter([
                ['Inicio', ''],
                $p['niche_name'] ? [$p['niche_name'], 'nicho/' . $p['niche_slug']] : null,
                [$p['name'], 'producto/' . $p['slug']],
            ])))],
        ]),
        'p' => $p,
        'offers' => $offers,
        'best' => $best,
        'stats' => $stats,
        'statsStore' => $store,
        'verdict' => array_filter(array_column($offers, 'price'), fn ($v) => $v !== null)
            ? price_verdict($stats)
            : ['key' => 'none', 'label' => 'Precio en la tienda', 'text' => 'Todavía no mostramos el precio de este producto aquí. Pulsa en la tienda para ver el precio de hoy.'],
        'chart' => $chart,
        'related' => $related,
        'similar' => $similar,
        'loadChart' => (bool) $chart['series'],
        'review' => $review,
    ]);
}

function list_page(string $kind): void
{
    $page = max(1, (int) input('pagina', '1'));
    $perPage = 12;
    $where = section_where($kind);
    $nicheSlug = input('nicho', '');
    $params = [];
    $niche = null;
    if ($nicheSlug !== '') {
        $niche = row('SELECT * FROM niches WHERE slug = ?', [$nicheSlug]);
        if ($niche) {
            $where .= ' AND po.niche_id = ?';
            $params[] = (int) $niche['id'];
        }
    }
    $total = (int) val("SELECT COUNT(*) FROM posts po WHERE po.status = 'published' AND po.published_at <= ? AND ($where)", array_merge([now()], $params));
    $posts = published_posts($where, $params, $perPage, ($page - 1) * $perPage);
    $title = post_section($kind)[0];

    render('list', [
        'meta' => meta([
            'title' => $title . ($niche ? ': ' . $niche['name'] : ''),
            'description' => match ($kind) {
                'review' => 'Reseñas con nota, pros y contras, y el historial de precio de cada producto.',
                'comparison' => 'Comparativas entre productos del mismo tipo: diferencias, para quién es cada uno y cuál conviene.',
                default => 'Guías de compra y consejos para pagar menos en Amazon, Temu y Shein.',
            },
            'noindex' => $page > 1,
        ]),
        'title' => $title,
        'kind' => $kind,
        'posts' => $posts,
        'niche' => $niche,
        'page' => $page,
        'pages' => max(1, (int) ceil($total / $perPage)),
    ]);
}

function page_blog(): void
{
    list_page('blog');
}

function page_reviews(): void
{
    list_page('review');
}

function page_comparisons(): void
{
    list_page('comparison');
}

function page_post(string $slug): void
{
    $post = row(
        'SELECT po.*, n.name AS niche_name, n.slug AS niche_slug, n.color AS niche_color, pr.name AS product_name
         FROM posts po LEFT JOIN niches n ON n.id = po.niche_id LEFT JOIN products pr ON pr.id = po.product_id
         WHERE po.slug = ?',
        [$slug]
    );
    $isPreview = (bool) current_user();
    if (!$post || (($post['status'] !== 'published' || $post['published_at'] > now()) && !$isPreview)) {
        not_found();
    }
    $expected = '/' . post_section($post['type'])[1] . '/';
    if (!str_starts_with(request_path(), $expected)) {
        redirect(ltrim(substr(post_url($post), strlen(base_path())), '/'));
    }
    $product = $post['product_id']
        ? row('SELECT p.*, n.name AS niche_name, n.color AS niche_color, n.slug AS niche_slug FROM products p LEFT JOIN niches n ON n.id = p.niche_id WHERE p.id = ?', [(int) $post['product_id']])
        : null;
    $more = published_posts('po.id <> ? AND (po.niche_id = ? OR po.type = ?)', [(int) $post['id'], (int) $post['niche_id'], $post['type']], 3);

    $section = post_section($post['type']);
    render('post', [
        'meta' => meta([
            'title' => $post['title'],
            'description' => excerpt($post['excerpt'] ?: (string) $post['body'], 155),
            'image' => $post['cover_path'] ? absolute_url('uploads/' . $post['cover_path']) : null,
            'type' => 'article',
            'noindex' => $post['status'] !== 'published',
            'jsonld' => [
                jsonld_article($post),
                jsonld_breadcrumbs([['Inicio', ''], [$section[0], $section[1]], [$post['title'], ltrim(substr(post_url($post), strlen(base_path())), '/')]]),
            ],
        ]),
        'post' => $post,
        'product' => $product,
        'more' => $more,
        'section' => $section,
        'isDraft' => $post['status'] !== 'published',
    ]);
}

function page_search(): void
{
    $q = mb_substr((string) input('q', ''), 0, 80);
    $products = [];
    $posts = [];
    if (mb_strlen($q) >= 2) {
        $asin = extract_asin($q);
        $like = '%' . $q . '%';
        $products = $asin
            ? product_list('p.asin = ?', [$asin], 'p.name', 24)
            : product_list('(p.name LIKE ? OR p.brand LIKE ? OR n.name LIKE ?)', [$like, $like, $like], 'p.name', 24);
        $posts = published_posts('(po.title LIKE ? OR po.excerpt LIKE ?)', [$like, $like], 12);
    }
    render('search', [
        'meta' => meta(['title' => $q ? 'Resultados para «' . $q . '»' : 'Buscar', 'noindex' => true]),
        'q' => $q,
        'products' => $products,
        'posts' => $posts,
    ]);
}

function page_legal(string $slug): void
{
    $pages = [
        'aviso-legal' => 'Aviso legal',
        'privacidad' => 'Política de privacidad',
        'cookies' => 'Política de cookies',
        'afiliacion' => 'Cómo nos financiamos',
    ];
    if (!isset($pages[$slug])) {
        not_found();
    }
    render('legal', [
        'meta' => meta(['title' => $pages[$slug]]),
        'slug' => $slug,
        'title' => $pages[$slug],
    ]);
}

function page_sitemap(): void
{
    header('Content-Type: application/xml; charset=utf-8');
    $urls = [['', date('Y-m-d')], ['blog', null]];
    if (site_counts()['reviews']) $urls[] = ['resenas', null];
    if (site_counts()['comparisons']) $urls[] = ['comparativas', null];
    foreach (all_niches() as $n) {
        $urls[] = ['nicho/' . $n['slug'], null];
    }
    foreach (rows("SELECT slug, updated_at FROM products WHERE status = 'published'") as $p) {
        $urls[] = ['producto/' . $p['slug'], substr($p['updated_at'], 0, 10)];
    }
    foreach (rows("SELECT slug, type, updated_at FROM posts WHERE status = 'published' AND published_at <= ?", [now()]) as $p) {
        $urls[] = [post_section($p['type'])[1] . '/' . $p['slug'], substr($p['updated_at'], 0, 10)];
    }
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as [$path, $mod]) {
        echo '  <url><loc>' . e(absolute_url($path)) . '</loc>' . ($mod ? '<lastmod>' . e($mod) . '</lastmod>' : '') . "</url>\n";
    }
    echo '</urlset>';
}

function page_robots(): void
{
    header('Content-Type: text/plain; charset=utf-8');
    if (has_demo_content()) {
        echo "User-agent: *\nDisallow: /\n";
        return;
    }
    echo "User-agent: *\nDisallow: /admin\nDisallow: /buscar\nDisallow: /install/\n\nSitemap: " . absolute_url('sitemap.xml') . "\n";
}

/** Registra un clic hacia una tienda (se llama en segundo plano, sin redirecciones). */
function api_click(): void
{
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    $id = (int) input('product', '0');
    $store = (string) input('store', '');
    if ($id > 0 && isset(STORES[$store]) && !preg_match('~bot|crawl|spider|slurp|preview~', $ua)) {
        insert('clicks', [
            'product_id' => $id,
            'store' => $store,
            'page' => mb_substr((string) input('from', ''), 0, 16),
            'created_at' => now(),
        ]);
    }
    http_response_code(204);
}

/** Lanza el recolector por URL (para planes sin cron por línea de comandos). Requiere cron_key. */
function task_prices(): void
{
    $key = (string) config('cron_key', '');
    if ($key === '' || !hash_equals($key, (string) input('key', ''))) {
        http_response_code(403);
        exit('Prohibido');
    }
    require_once APP . '/cron.php';
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex');
    echo run_price_update();
}
