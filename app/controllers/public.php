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
    $GLOBALS['track'] = ['home', 0];
    render('home', [
        'meta' => meta(['canonical' => absolute_url('')]),
        'deals' => $deals,
        'popular' => $popular,
        'reviews' => published_posts("po.type = 'review'", [], 4),
        'guides' => published_posts(section_where('blog'), [], 5),
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
            'title' => $niche['name'] . ': guías y qué comprar',
            'description' => excerpt($niche['intro'] ?: 'Guías de compra y productos de ' . mb_strtolower($niche['name']) . '.', 155),
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
    if (!$p || (!in_array($p['status'], ['published', 'unlisted'], true) && !current_user())) {
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

    $GLOBALS['track'] = ['product', (int) $p['id']];
    render('product', [
        'meta' => meta([
            'title' => $p['name'] . (empty($p['best_price']) ? ': qué es, para quién y dónde comprarlo' : ': historial de precios'),
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
                default => 'Guías de compra y consejos para saber cuándo comprar y pagar menos en Amazon.',
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
    $faqs = post_faqs((string) $post['body']);
    $GLOBALS['track'] = ['post', (int) $post['id']];
    $picks = post_picks((string) $post['body']);
    render('post', [
        'picks' => $picks,
        'meta' => meta([
            'title' => post_seo_title($post),
            'description' => excerpt($post['excerpt'] ?: (string) $post['body'], 155),
            'image' => $post['cover_path'] ? absolute_url('uploads/' . $post['cover_path']) : null,
            'type' => 'article',
            'noindex' => $post['status'] !== 'published',
            'jsonld' => [
                jsonld_article($post),
                jsonld_breadcrumbs([['Inicio', ''], [$section[0], $section[1]], [$post['title'], ltrim(substr(post_url($post), strlen(base_path())), '/')]]),
                ...($faqs ? [jsonld_faq($faqs)] : []),
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
    $raw = mb_substr(trim((string) input('q', '')), 0, 2000);
    $isLink = (bool) preg_match('~(amazon\.[a-z.]+/|amzn\.(eu|to)/|a\.co/)~i', $raw);
    $q = $isLink ? $raw : mb_substr($raw, 0, 80);
    $products = [];
    $posts = [];
    $follow = null;
    if (mb_strlen($q) >= 2) {
        $asin = $isLink ? resolve_amazon_input($raw) : extract_asin($q);
        if ($asin) {
            $known = row("SELECT slug FROM products WHERE asin = ? AND status IN ('published', 'unlisted') ORDER BY status = 'published' DESC LIMIT 1", [$asin]);
            if ($known) {
                redirect('producto/' . $known['slug']);
            }
            $follow = $asin;
        }
        $like = '%' . $q . '%';
        $products = $asin
            ? product_list('p.asin = ?', [$asin], 'p.name', 24)
            : product_list('(p.name LIKE ? OR p.brand LIKE ? OR n.name LIKE ?)', [$like, $like, $like], 'p.name', 24);
        $posts = $isLink ? [] : published_posts('(po.title LIKE ? OR po.excerpt LIKE ?)', [$like, $like], 12);
    }
    render('search', [
        'meta' => meta(['title' => $isLink ? 'Seguir un producto' : ($q ? 'Resultados para «' . $q . '»' : 'Buscar'), 'noindex' => true]),
        'q' => $isLink ? '' : $q,
        'follow' => $follow,
        'isLink' => $isLink,
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
        // Las páginas con datos del titular no se muestran en buscadores.
        'meta' => meta(['title' => $pages[$slug], 'noindex' => in_array($slug, ['aviso-legal', 'privacidad'], true)]),
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
    if (fuel_enabled()) {
        $today = date('Y-m-d');
        $urls[] = ['gasolina', $today];
        foreach (array_keys(FUEL_PROVINCES) as $pid) {
            $urls[] = ['gasolina/' . fuel_province_slug($pid), $today];
        }
        foreach (rows('SELECT id, province_id, slug FROM fuel_munis WHERE stations > 0 ORDER BY province_id, slug') as $m) {
            $urls[] = [fuel_muni_path($m), $today];
        }
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
    $id = (int) input('product', '0');
    $post = (int) input('post', '0');
    $store = (string) input('store', '');
    if (($id > 0 || $post > 0) && isset(STORES[$store]) && !is_untracked_request()) {
        insert('clicks', [
            'product_id' => max(0, $id),
            'store' => $store,
            'page' => mb_substr((string) input('from', ''), 0, 16),
            'post_id' => $post > 0 ? $post : null,
            'label' => mb_substr((string) input('label', ''), 0, 120) ?: null,
            'created_at' => now(),
        ]);
    }
    http_response_code(204);
}

/** Robots y la propia administradora con la sesión abierta no cuentan en las estadísticas. */
function is_untracked_request(): bool
{
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua === '' || preg_match('~bot|crawl|spider|slurp|preview|headless|lighthouse|monitor|curl|wget|python~', $ua)) {
        return true;
    }
    return !empty($_COOKIE['rp_sess']) && current_user() !== null;
}

/** Cuenta una visita a una ficha, un artículo o la portada (lo llama el navegador en segundo plano). */
function api_visit(): void
{
    $kind = (string) input('kind', '');
    $ref = (int) input('ref', '0');
    if (in_array($kind, ['product', 'post', 'home', 'list', 'fuel'], true) && $ref >= 0 && !is_untracked_request()) {
        $day = date('Y-m-d');
        $done = q('UPDATE visits SET views = views + 1 WHERE day = ? AND kind = ? AND ref_id = ?', [$day, $kind, $ref])->rowCount();
        if (!$done) {
            try {
                insert('visits', ['day' => $day, 'kind' => $kind, 'ref_id' => $ref, 'views' => 1]);
            } catch (Throwable $e) {
                q('UPDATE visits SET views = views + 1 WHERE day = ? AND kind = ? AND ref_id = ?', [$day, $kind, $ref]);
            }
        }
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


/** "Pega un enlace de Amazon y lo seguimos". */
function page_follow(): void
{
    $result = null;
    $error = null;
    $value = '';
    if (is_post()) {
        csrf_check();
        $value = mb_substr(trim((string) input('enlace', '')), 0, 2000);
        $asin = resolve_amazon_input($value);
        if (!$asin) {
            $error = 'No encontramos un producto de Amazon en ese enlace. Copia el enlace de la página del producto, o el que te da el botón Compartir de la app de Amazon.';
        } elseif (rate_limited('follow', 15, 3600)) {
            $error = 'Has añadido muchos productos seguidos. Espera un rato y vuelve a intentarlo.';
        } else {
            $result = request_tracking($asin);
            if ($result['product']) {
                redirect('producto/' . $result['product']['slug']);
            }
            if ($result['status'] === 'error') {
                $error = $result['message'];
                $result = null;
            }
        }
    }
    header('X-Robots-Tag: noindex');
    render('follow', [
        'meta' => meta(['title' => 'Seguir el precio de un producto de Amazon', 'description' => 'Pega el enlace de cualquier producto de Amazon.es y te enseñamos cómo cambia su precio. ', 'noindex' => true]),
        'result' => $result,
        'error' => $error,
        'value' => $value,
    ]);
}

/** Crea un aviso de precio. Por correo hay que confirmarlo desde un enlace; por Telegram se activa en el bot. */
function action_alert(): void
{
    csrf_check();
    if (!alerts_enabled()) {
        not_found();
    }
    $p = row("SELECT * FROM products WHERE id = ? AND status IN ('published', 'unlisted')", [(int) input('producto', '0')]);
    if (!$p) {
        not_found();
    }
    $byMail = mail_enabled() && (input('canal', 'correo') !== 'telegram' || !telegram_alerts_enabled());
    $raw = str_replace([' ', '€'], '', (string) input('precio', ''));
    $raw = str_contains($raw, ',') ? str_replace(['.', ','], ['', '.'], $raw) : $raw;
    $target = is_numeric($raw) ? (float) $raw : 0.0;
    $email = mb_strtolower(trim(mb_substr((string) input('correo', ''), 0, 190)));
    $error = null;
    $startUrl = null;
    $needsConfirm = true;
    if ($target <= 0 || $target > 100000) {
        $error = 'Escribe el precio al que quieres comprarlo, por ejemplo 24,99.';
    } elseif ($byMail && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Escribe un correo válido para poder avisarte.';
    } elseif (rate_limited('alert', 20, 86400)) {
        $error = 'Has creado muchos avisos hoy. Vuelve a intentarlo mañana.';
    } elseif ($byMail) {
        $made = create_email_alert((int) $p['id'], $target, $email);
        if (!$made) {
            $error = 'Ese correo ya tiene muchos avisos activos. Borra alguno desde el enlace que hay en nuestros correos.';
        } else {
            [$code, $needsConfirm] = $made;
            if ($needsConfirm && !send_alert_confirmation($p, $target, $email, $code)) {
                q('DELETE FROM alerts WHERE code = ? AND status = ?', [$code, 'pending']);
                $error = 'No hemos podido enviarte el correo de confirmación. Vuelve a intentarlo en unos minutos.';
            }
        }
    } else {
        $startUrl = alert_start_url(create_alert((int) $p['id'], $target));
    }
    header('X-Robots-Tag: noindex');
    render('alert-created', [
        'meta' => meta(['title' => 'Tu aviso de precio', 'noindex' => true]),
        'p' => $p,
        'target' => $target,
        'error' => $error,
        'startUrl' => $startUrl,
        'email' => $byMail ? $email : null,
        'needsConfirm' => $needsConfirm,
    ]);
}

/** Enlace del correo de confirmación: activa el aviso. */
function alert_confirm(string $code): void
{
    $a = row('SELECT a.*, p.name, p.slug FROM alerts a JOIN products p ON p.id = a.product_id WHERE a.code = ? AND a.email IS NOT NULL', [$code]);
    if ($a && $a['status'] === 'pending') {
        update('alerts', ['status' => 'active', 'activated_at' => now()], (int) $a['id']);
        $a['status'] = 'active';
    }
    header('X-Robots-Tag: noindex');
    render('alert-status', [
        'meta' => meta(['title' => 'Aviso de precio', 'noindex' => true]),
        'state' => $a && $a['status'] === 'active' ? 'confirmed' : 'gone',
        'alert' => $a,
    ]);
}

/** Enlace de baja de los correos: pregunta primero y borra al pulsar el botón, para que no lo active un lector de correo. */
function alert_unsubscribe(string $code): void
{
    $a = row('SELECT a.*, p.name, p.slug FROM alerts a JOIN products p ON p.id = a.product_id WHERE a.code = ? AND a.email IS NOT NULL', [$code]);
    $state = $a ? 'ask' : 'gone';
    if ($a && is_post()) {
        csrf_check();
        if (input('todos', '') === '1') {
            q('DELETE FROM alerts WHERE email = ?', [$a['email']]);
        } else {
            q('DELETE FROM alerts WHERE id = ?', [(int) $a['id']]);
        }
        $state = 'removed';
    }
    header('X-Robots-Tag: noindex');
    render('alert-status', [
        'meta' => meta(['title' => 'Borrar aviso de precio', 'noindex' => true]),
        'state' => $state,
        'alert' => $a,
    ]);
}

/** Mensajes que Telegram envía al bot. */
function telegram_webhook(string $secret): void
{
    $expected = setting('telegram_webhook_secret');
    $header = (string) ($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '');
    if ($expected === '' || !hash_equals($expected, $secret) || !hash_equals($expected, $header)) {
        http_response_code(403);
        exit;
    }
    $update = json_decode((string) file_get_contents('php://input'), true);
    if (is_array($update)) {
        try {
            handle_telegram_update($update);
        } catch (Throwable $e) {
            log_line('telegram', 'Webhook: ' . $e->getMessage());
        }
    }
    header('Content-Type: text/plain');
    echo 'ok';
}
