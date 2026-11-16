<?php
declare(strict_types=1);

function admin_render(string $view, array $vars = []): void
{
    header('X-Robots-Tag: noindex, nofollow');
    $vars['user'] = current_user();
    $vars['flash'] = flash();
    render('admin/' . $view, $vars, 'admin/layout');
}

/* ---------- Acceso ---------- */

function admin_login(): void
{
    if (current_user()) {
        redirect('admin');
    }
    $error = null;
    if (is_post()) {
        csrf_check();
        if (too_many_attempts()) {
            $error = 'Demasiados intentos. Espera 15 minutos y vuelve a probar.';
        } elseif (attempt_login((string) input('email'), (string) ($_POST['password'] ?? ''))) {
            redirect('admin');
        } else {
            $error = 'El correo o la contraseña no son correctos.';
        }
    }
    header('X-Robots-Tag: noindex, nofollow');
    echo view('admin/login', ['error' => $error]);
}

function admin_setup(): void
{
    $hash = (string) setting('setup_token_hash', '');
    $token = (string) input('token', '');
    if ($hash === '' || (int) val('SELECT COUNT(*) FROM users') > 0 || !hash_equals($hash, hash('sha256', $token))) {
        not_found();
    }
    $errors = [];
    $form = ['name' => '', 'email' => ''];
    if (is_post()) {
        csrf_check();
        $form['name'] = trim((string) input('name', ''));
        $form['email'] = mb_strtolower(trim((string) input('email', '')));
        $password = (string) ($_POST['password'] ?? '');
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Escribe un correo válido.';
        if (mb_strlen($password) < 10) $errors[] = 'La contraseña debe tener al menos 10 caracteres.';
        if ($password !== (string) ($_POST['password2'] ?? '')) $errors[] = 'Las dos contraseñas no coinciden.';
        if (!$errors) {
            $id = insert('users', ['email' => $form['email'], 'name' => $form['name'], 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'created_at' => now()]);
            save_setting('setup_token_hash', '');
            if ($form['name'] !== '') {
                save_setting('default_author', $form['name']);
                q("UPDATE posts SET author = ? WHERE author = '' OR author IS NULL", [$form['name']]);
            }
            start_session();
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $id;
            flash('Cuenta creada. Ya estás dentro del panel.');
            redirect('admin');
        }
    }
    header('X-Robots-Tag: noindex, nofollow');
    echo view('admin/setup', ['errors' => $errors, 'form' => $form, 'token' => $token]);
}

function admin_logout(): void
{
    csrf_check();
    logout();
    redirect('admin/entrar');
}

/* ---------- Escritorio ---------- */

function admin_dashboard(): void
{
    require_login();
    $since = date('Y-m-d H:i:s', time() - 30 * 86400);
    admin_render('dashboard', [
        'counts' => [
            'products' => (int) val("SELECT COUNT(*) FROM products WHERE status = 'published'"),
            'drafts' => (int) val("SELECT COUNT(*) FROM products WHERE status <> 'published'"),
            'posts' => (int) val("SELECT COUNT(*) FROM posts WHERE status = 'published'"),
            'clicks' => (int) val('SELECT COUNT(*) FROM clicks WHERE created_at > ?', [$since]),
        ],
        'topClicks' => rows(
            'SELECT p.id, p.name, c.store, COUNT(*) AS total FROM clicks c JOIN products p ON p.id = c.product_id
             WHERE c.created_at > ? GROUP BY p.id, p.name, c.store ORDER BY total DESC LIMIT 10',
            [$since]
        ),
        'lows' => product_list('p.at_low = 1', [], 'p.name', 10),
        'lastCron' => setting('cron_last_run'),
        'lastCronResult' => setting('cron_last_result'),
        'demo' => has_demo_content(),
        'amazonOn' => amazon_enabled(),
        'telegramOn' => telegram_enabled(),
        'pendingAsin' => (int) val("SELECT COUNT(*) FROM products WHERE asin IS NOT NULL AND asin <> '' AND amazon_checked_at IS NULL"),
        'alertsOn' => alerts_enabled(),
        'alertCounts' => [
            'active' => (int) val("SELECT COUNT(*) FROM alerts WHERE status = 'active'"),
            'sent' => (int) val("SELECT COUNT(*) FROM alerts WHERE status = 'sent' AND sent_at > ?", [$since]),
        ],
        'requests' => rows("SELECT * FROM track_requests WHERE status IN ('pending', 'error') ORDER BY requests DESC, created_at DESC LIMIT 15"),
        'unlisted' => rows("SELECT id, name, slug, asin, created_at FROM products WHERE status = 'unlisted' ORDER BY created_at DESC LIMIT 15"),
    ]);
}

/* ---------- Estadísticas ---------- */

function admin_stats(): void
{
    require_login();
    $days = (int) input('dias', '30');
    $days = in_array($days, [7, 30, 90], true) ? $days : 30;
    $fromDay = date('Y-m-d', time() - ($days - 1) * 86400);
    $fromTime = $fromDay . ' 00:00:00';

    $views = ['product' => [], 'post' => []];
    $totals = ['views' => 0, 'home' => 0, 'fuel' => 0];
    foreach (rows('SELECT kind, ref_id, SUM(views) AS v FROM visits WHERE day >= ? GROUP BY kind, ref_id', [$fromDay]) as $r) {
        $totals['views'] += (int) $r['v'];
        if ($r['kind'] === 'home') {
            $totals['home'] += (int) $r['v'];
        } elseif ($r['kind'] === 'fuel') {
            $totals['fuel'] += (int) $r['v'];
        } elseif (isset($views[$r['kind']])) {
            $views[$r['kind']][(int) $r['ref_id']] = (int) $r['v'];
        }
    }
    $clicksProduct = [];
    foreach (rows('SELECT product_id, COUNT(*) AS c FROM clicks WHERE created_at >= ? AND product_id > 0 GROUP BY product_id', [$fromTime]) as $r) {
        $clicksProduct[(int) $r['product_id']] = (int) $r['c'];
    }
    $clicksPost = [];
    foreach (rows('SELECT post_id, COUNT(*) AS c FROM clicks WHERE created_at >= ? AND post_id IS NOT NULL GROUP BY post_id', [$fromTime]) as $r) {
        $clicksPost[(int) $r['post_id']] = (int) $r['c'];
    }
    $build = function (array $items, array $v, array $c, string $path): array {
        $out = [];
        foreach ($items as $it) {
            $id = (int) $it['id'];
            $out[] = ['id' => $id, 'name' => $it['name'], 'url' => $path . $id, 'views' => $v[$id] ?? 0, 'clicks' => $c[$id] ?? 0];
        }
        usort($out, fn ($a, $b) => [$b['clicks'], $b['views'], $a['name']] <=> [$a['clicks'], $a['views'], $b['name']]);
        return $out;
    };

    $series = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $series[date('Y-m-d', time() - $i * 86400)] = ['views' => 0, 'clicks' => 0];
    }
    foreach (rows('SELECT day, SUM(views) AS v FROM visits WHERE day >= ? GROUP BY day', [$fromDay]) as $r) {
        $d = substr((string) $r['day'], 0, 10);
        if (isset($series[$d])) {
            $series[$d]['views'] = (int) $r['v'];
        }
    }
    foreach (rows('SELECT SUBSTR(created_at, 1, 10) AS d, COUNT(*) AS c FROM clicks WHERE created_at >= ? GROUP BY SUBSTR(created_at, 1, 10)', [$fromTime]) as $r) {
        if (isset($series[$r['d']])) {
            $series[$r['d']]['clicks'] = (int) $r['c'];
        }
    }

    admin_render('stats', [
        'days' => $days,
        'totals' => $totals + ['clicks' => (int) val('SELECT COUNT(*) FROM clicks WHERE created_at >= ?', [$fromTime])],
        'series' => $series,
        'products' => $build(rows("SELECT id, name FROM products WHERE status <> 'draft' ORDER BY name"), $views['product'], $clicksProduct, 'admin/productos/'),
        'posts' => $build(rows("SELECT id, title AS name FROM posts WHERE status = 'published' ORDER BY title"), $views['post'], $clicksPost, 'admin/articulos/'),
        'links' => rows("SELECT label, COUNT(*) AS c FROM clicks WHERE created_at >= ? AND label IS NOT NULL AND label <> '' GROUP BY label ORDER BY c DESC LIMIT 15", [$fromTime]),
    ]);
}

function admin_demo_delete(): void
{
    require_login();
    csrf_check();
    $ids = array_column(rows('SELECT id FROM products WHERE is_demo = 1'), 'id');
    foreach ($ids as $id) {
        q('DELETE FROM prices WHERE product_id = ?', [(int) $id]);
        q('DELETE FROM clicks WHERE product_id = ?', [(int) $id]);
    }
    q('DELETE FROM products WHERE is_demo = 1');
    q('DELETE FROM posts WHERE is_demo = 1');
    flash('Contenido de ejemplo borrado. Los nichos se mantienen para que añadas tus productos.');
    redirect('admin');
}

/* ---------- Nichos ---------- */

function admin_niches(): void
{
    require_login();
    admin_render('niches', ['niches' => all_niches()]);
}

function admin_niche_edit(string $id): void
{
    require_login();
    $niche = $id === 'nuevo'
        ? ['id' => null, 'name' => '', 'slug' => '', 'intro' => '', 'color' => '#2546D8', 'sort_order' => (int) val('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM niches')]
        : row('SELECT * FROM niches WHERE id = ?', [(int) $id]);
    if (!$niche) {
        not_found();
    }
    $errors = [];
    if (is_post()) {
        csrf_check();
        $data = [
            'name' => mb_substr((string) input('name'), 0, 120),
            'intro' => (string) input('intro'),
            'color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) input('color')) ? strtoupper((string) input('color')) : '#2546D8',
            'sort_order' => (int) input('sort_order', '0'),
        ];
        if ($data['name'] === '') {
            $errors[] = 'Escribe el nombre del nicho.';
        }
        $data['slug'] = unique_slug('niches', (string) input('slug') ?: $data['name'], $niche['id'] ? (int) $niche['id'] : null);
        if (!$errors) {
            if ($niche['id']) {
                update('niches', $data, (int) $niche['id']);
                flash('Nicho guardado.');
            } else {
                $data['created_at'] = now();
                insert('niches', $data);
                flash('Nicho creado.');
            }
            redirect('admin/nichos');
        }
        $niche = array_merge($niche, $data);
    }
    admin_render('niche-edit', ['niche' => $niche, 'errors' => $errors]);
}

function admin_niche_delete(string $id): void
{
    require_login();
    csrf_check();
    $count = (int) val('SELECT COUNT(*) FROM products WHERE niche_id = ?', [(int) $id]);
    if ($count > 0) {
        flash('Este nicho tiene ' . $count . ' productos. Muévelos a otro nicho antes de borrarlo.', 'error');
        redirect('admin/nichos/' . (int) $id);
    }
    q('UPDATE posts SET niche_id = NULL WHERE niche_id = ?', [(int) $id]);
    q('DELETE FROM niches WHERE id = ?', [(int) $id]);
    flash('Nicho borrado.');
    redirect('admin/nichos');
}

/* ---------- Productos ---------- */

function admin_products(): void
{
    require_login();
    $nicheId = (int) input('nicho', '0');
    $q = (string) input('q', '');
    $where = ['1=1'];
    $params = [];
    if ($nicheId) {
        $where[] = 'p.niche_id = ?';
        $params[] = $nicheId;
    }
    if ($q !== '') {
        $where[] = '(p.name LIKE ? OR p.asin = ?)';
        $params[] = '%' . $q . '%';
        $params[] = strtoupper($q);
    }
    $products = rows(
        'SELECT p.*, n.name AS niche_name, n.color AS niche_color FROM products p LEFT JOIN niches n ON n.id = p.niche_id
         WHERE ' . implode(' AND ', $where) . ' ORDER BY p.updated_at DESC LIMIT 300',
        $params
    );
    admin_render('products', ['products' => $products, 'nicheId' => $nicheId, 'q' => $q]);
}

function admin_product_edit(string $id): void
{
    require_login();
    $empty = ['id' => null, 'niche_id' => (int) input('nicho', '0') ?: null, 'name' => '', 'slug' => '', 'brand' => '', 'summary' => '',
        'image_path' => null, 'asin' => '', 'temu_url' => '', 'shein_url' => '', 'other_name' => '', 'other_url' => '',
        'track' => 1, 'status' => 'draft', 'amazon_title' => null, 'amazon_price' => null, 'amazon_price_at' => null,
        'amazon_checked_at' => null, 'amazon_error' => null, 'is_demo' => 0];
    $p = $id === 'nuevo' ? $empty : row('SELECT * FROM products WHERE id = ?', [(int) $id]);
    if (!$p) {
        not_found();
    }
    $errors = [];
    if (is_post()) {
        csrf_check();
        $asinInput = (string) input('asin');
        $asin = $asinInput !== '' ? extract_asin($asinInput) : null;
        if ($asinInput !== '' && !$asin) {
            $errors[] = 'No reconozco ese ASIN. Pega el enlace de la ficha de Amazon o el código de 10 caracteres.';
        }
        $data = [
            'niche_id' => (int) input('niche_id', '0') ?: null,
            'name' => mb_substr((string) input('name'), 0, 200),
            'brand' => mb_substr((string) input('brand'), 0, 120),
            'summary' => (string) input('summary'),
            'asin' => $asin,
            'temu_url' => clean_url((string) input('temu_url')),
            'shein_url' => clean_url((string) input('shein_url')),
            'other_name' => mb_substr((string) input('other_name'), 0, 80) ?: null,
            'other_url' => clean_url((string) input('other_url')),
            'track' => input('track') ? 1 : 0,
            'status' => in_array(input('status'), ['published', 'unlisted'], true) ? (string) input('status') : 'draft',
            'updated_at' => now(),
        ];
        if ($data['name'] === '') {
            $errors[] = 'Escribe el nombre del producto.';
        }
        foreach (['temu_url' => 'Temu', 'shein_url' => 'Shein', 'other_url' => 'la otra tienda'] as $k => $label) {
            if ((string) input($k) !== '' && !$data[$k]) {
                $errors[] = 'El enlace de ' . $label . ' no es una dirección web válida (debe empezar por https://).';
            }
        }
        if ($p['id'] && $asin !== ($p['asin'] ?: null)) {
            // Si cambia el ASIN, se olvidan los datos de Amazon del producto anterior.
            $data += ['amazon_title' => null, 'amazon_url' => null, 'amazon_image_url' => null, 'amazon_price' => null, 'amazon_price_at' => null, 'amazon_checked_at' => null];
        }
        try {
            if ($img = save_upload('image')) {
                $data['image_path'] = $img;
            } elseif (input('remove_image')) {
                $data['image_path'] = null;
            }
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }
        $data['slug'] = unique_slug('products', (string) input('slug') ?: $data['name'], $p['id'] ? (int) $p['id'] : null);

        if (!$errors) {
            if ($p['id']) {
                update('products', $data, (int) $p['id']);
                $newId = (int) $p['id'];
            } else {
                $data['created_at'] = now();
                $newId = insert('products', $data);
            }
            refresh_product_stats($newId);
            if ($asin && amazon_enabled() && !$p['amazon_checked_at']) {
                admin_fetch_amazon($newId);
            }
            flash($p['id'] ? 'Producto guardado.' : 'Producto creado. Ya puedes añadir precios de otras tiendas.');
            redirect('admin/productos/' . $newId);
        }
        $p = array_merge($p, $data, ['asin' => $asinInput]);
    }
    $manual = $p['id'] ? rows("SELECT * FROM prices WHERE product_id = ? AND store <> 'amazon' ORDER BY captured_at DESC LIMIT 30", [(int) $p['id']]) : [];
    admin_render('product-edit', ['p' => $p, 'errors' => $errors, 'manual' => $manual, 'niches' => all_niches()]);
}

function clean_url(string $u): ?string
{
    $u = trim($u);
    if ($u === '') {
        return null;
    }
    return (filter_var($u, FILTER_VALIDATE_URL) && preg_match('~^https?://~i', $u)) ? mb_substr($u, 0, 500) : null;
}

function admin_fetch_amazon(int $id): ?string
{
    $p = row('SELECT * FROM products WHERE id = ?', [$id]);
    if (!$p || !$p['asin']) {
        return 'Este producto no tiene ASIN.';
    }
    try {
        $res = amazon_get_items([$p['asin']]);
        $item = $res['items'][$p['asin']] ?? null;
        amazon_store_result($p, $item, $res['errors'][$p['asin']] ?? $res['errors']['general'] ?? null);
        return $item ? null : ($res['errors'][$p['asin']] ?? $res['errors']['general'] ?? 'Amazon no ha devuelto este producto.');
    } catch (Throwable $e) {
        log_line('amazon', $e->getMessage());
        return $e->getMessage();
    }
}

function admin_product_amazon(string $id): void
{
    require_login();
    csrf_check();
    if (!amazon_enabled()) {
        flash('La conexión con Amazon está desactivada. Actívala en config.php cuando Amazon te dé acceso a Creators API.', 'error');
    } elseif ($err = admin_fetch_amazon((int) $id)) {
        flash('Amazon respondió con un aviso: ' . $err, 'error');
    } else {
        flash('Datos de Amazon actualizados.');
    }
    redirect('admin/productos/' . (int) $id);
}

function admin_product_delete(string $id): void
{
    require_login();
    csrf_check();
    q('DELETE FROM prices WHERE product_id = ?', [(int) $id]);
    q('DELETE FROM clicks WHERE product_id = ?', [(int) $id]);
    q('UPDATE posts SET product_id = NULL WHERE product_id = ?', [(int) $id]);
    q('DELETE FROM products WHERE id = ?', [(int) $id]);
    flash('Producto borrado.');
    redirect('admin/productos');
}

/** Precio manual de otra tienda (no Amazon). Los de Amazon solo llegan por la API. */
function admin_price_add(string $id): void
{
    require_login();
    csrf_check();
    $store = (string) input('store');
    $price = (float) str_replace(',', '.', (string) input('price'));
    $date = (string) input('date') ?: date('Y-m-d');
    if (!in_array($store, ['temu', 'shein', 'other'], true) || $price <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        flash('Revisa la tienda, el precio y la fecha.', 'error');
        redirect('admin/productos/' . (int) $id);
    }
    insert('prices', [
        'product_id' => (int) $id,
        'store' => $store,
        'price' => round($price, 2),
        'currency' => 'EUR',
        'source' => 'manual',
        'captured_at' => $date === date('Y-m-d') ? now() : $date . ' 12:00:00',
    ]);
    refresh_product_stats((int) $id);
    q('UPDATE products SET updated_at = ? WHERE id = ?', [now(), (int) $id]);
    flash('Precio añadido.');
    redirect('admin/productos/' . (int) $id . '#precios');
}

function admin_price_delete(string $id): void
{
    require_login();
    csrf_check();
    $price = row("SELECT * FROM prices WHERE id = ? AND store <> 'amazon'", [(int) $id]);
    if ($price) {
        q('DELETE FROM prices WHERE id = ?', [(int) $id]);
        refresh_product_stats((int) $price['product_id']);
        flash('Precio borrado.');
        redirect('admin/productos/' . (int) $price['product_id'] . '#precios');
    }
    redirect('admin/productos');
}

/* ---------- Artículos y reseñas ---------- */

function admin_posts(): void
{
    require_login();
    $type = (string) input('tipo', '');
    $where = isset(POST_TYPES[$type]) ? 'WHERE po.type = ?' : '';
    $posts = rows(
        "SELECT po.*, n.name AS niche_name FROM posts po LEFT JOIN niches n ON n.id = po.niche_id $where ORDER BY COALESCE(po.published_at, po.created_at) DESC LIMIT 300",
        $where ? [$type] : []
    );
    admin_render('posts', ['posts' => $posts, 'type' => $type]);
}

function admin_post_edit(string $id): void
{
    $user = require_login();
    $empty = ['id' => null, 'type' => array_key_exists((string) input('tipo'), POST_TYPES) ? (string) input('tipo') : 'blog', 'niche_id' => null, 'product_id' => null, 'title' => '', 'seo_title' => '', 'slug' => '',
        'excerpt' => '', 'body' => '', 'cover_path' => null, 'rating' => null, 'pros' => '', 'cons' => '', 'verdict' => '',
        'author' => $user['name'] ?: setting('default_author', ''), 'status' => 'draft', 'published_at' => null];
    $post = $id === 'nuevo' ? $empty : row('SELECT * FROM posts WHERE id = ?', [(int) $id]);
    if (!$post) {
        not_found();
    }
    $errors = [];
    if (is_post()) {
        csrf_check();
        $rating = trim((string) input('rating'));
        $data = [
            'type' => isset(POST_TYPES[(string) input('type')]) ? (string) input('type') : 'blog',
            'niche_id' => (int) input('niche_id', '0') ?: null,
            'product_id' => (int) input('product_id', '0') ?: null,
            'title' => mb_substr((string) input('title'), 0, 220),
            'seo_title' => mb_substr(trim((string) input('seo_title')), 0, 120) ?: null,
            'excerpt' => (string) input('excerpt'),
            'body' => (string) ($_POST['body'] ?? ''),
            'rating' => $rating === '' ? null : max(0, min(10, round((float) str_replace(',', '.', $rating), 1))),
            'pros' => (string) input('pros'),
            'cons' => (string) input('cons'),
            'verdict' => (string) input('verdict'),
            'author' => mb_substr((string) input('author'), 0, 120),
            'status' => input('status') === 'published' ? 'published' : 'draft',
            'updated_at' => now(),
        ];
        $when = (string) input('published_at');
        $data['published_at'] = preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $when)
            ? str_replace('T', ' ', $when) . ':00'
            : ($post['published_at'] ?: ($data['status'] === 'published' ? now() : null));
        if ($data['title'] === '') {
            $errors[] = 'Escribe el título.';
        }
        try {
            if ($img = save_upload('cover')) {
                $data['cover_path'] = $img;
            } elseif (input('remove_cover')) {
                $data['cover_path'] = null;
            }
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }
        $data['slug'] = unique_slug('posts', (string) input('slug') ?: $data['title'], $post['id'] ? (int) $post['id'] : null);
        if (!$errors) {
            if ($post['id']) {
                update('posts', $data, (int) $post['id']);
                $newId = (int) $post['id'];
            } else {
                $data['created_at'] = now();
                $newId = insert('posts', $data);
            }
            flash($data['status'] === 'published' ? 'Publicado.' : 'Borrador guardado.');
            redirect('admin/articulos/' . $newId);
        }
        $post = array_merge($post, $data);
    }
    admin_render('post-edit', [
        'post' => $post,
        'errors' => $errors,
        'niches' => all_niches(),
        'products' => rows('SELECT id, name, slug FROM products ORDER BY name'),
    ]);
}

function admin_post_delete(string $id): void
{
    require_login();
    csrf_check();
    q('DELETE FROM posts WHERE id = ?', [(int) $id]);
    flash('Artículo borrado.');
    redirect('admin/articulos');
}

/* ---------- Ajustes y cuenta ---------- */

const SETTING_FIELDS = [
    'site_name' => 'Nombre de la web',
    'tagline' => 'Descripción corta (aparece en Google y en el pie)',
    'home_lead' => 'Texto de presentación de la portada',
    'default_author' => 'Autor por defecto de los artículos',
    'amazon_tag' => 'ID de seguimiento de Amazon Afiliados',
    'telegram_url' => 'Enlace a tu canal de Telegram',
    'telegram_channel' => 'Canal donde el bot publica los mínimos',
    'whatsapp_url' => 'Enlace a tu canal de WhatsApp',
    'legal_owner' => 'Titular (nombre y apellidos o razón social)',
    'legal_nif' => 'NIF',
    'legal_address' => 'Domicilio',
    'legal_email' => 'Correo de contacto',
];

function admin_settings(): void
{
    require_login();
    if (is_post()) {
        csrf_check();
        $tag = trim((string) input('amazon_tag', ''));
        if ($tag !== '' && !preg_match('/^[A-Za-z0-9_-]{3,40}$/', $tag)) {
            flash('El ID de Amazon solo lleva letras, números y guiones, por ejemplo tunombre-21. No se ha guardado ningún cambio.', 'error');
            redirect('admin/ajustes');
        }
        foreach (array_keys(SETTING_FIELDS) as $k) {
            save_setting($k, mb_substr(trim((string) input($k)), 0, 600));
        }
        // El token del bot solo se cambia si se escribe uno nuevo; nunca se vuelve a mostrar.
        $token = trim((string) ($_POST['telegram_bot_token'] ?? ''));
        if ($token !== '') {
            if (!preg_match('/^\d{5,15}:[A-Za-z0-9_-]{30,60}$/', $token)) {
                flash('El token del bot no tiene el formato que da BotFather (números, dos puntos y letras). El resto de ajustes sí se ha guardado.', 'error');
                redirect('admin/ajustes');
            }
            save_setting('telegram_bot_token', $token);
            save_setting('telegram_bot_username', '');
            flash('Ajustes guardados. Ahora pulsa "Conectar el bot" para activar los avisos.');
            redirect('admin/ajustes');
        }
        flash('Ajustes guardados.');
        redirect('admin/ajustes');
    }
    $values = [];
    foreach (array_keys(SETTING_FIELDS) as $k) {
        $values[$k] = setting($k);
    }
    admin_render('settings', ['values' => $values]);
}

function admin_account(): void
{
    $user = require_login();
    $errors = [];
    if (is_post()) {
        csrf_check();
        $current = (string) ($_POST['current'] ?? '');
        $new = (string) ($_POST['new'] ?? '');
        $hash = (string) val('SELECT password_hash FROM users WHERE id = ?', [(int) $user['id']]);
        if (!password_verify($current, $hash)) {
            $errors[] = 'La contraseña actual no es correcta.';
        } elseif (mb_strlen($new) < 10) {
            $errors[] = 'La nueva contraseña debe tener al menos 10 caracteres.';
        } else {
            q('UPDATE users SET password_hash = ?, name = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), mb_substr((string) input('name'), 0, 120), (int) $user['id']]);
            flash('Contraseña cambiada.');
            redirect('admin/cuenta');
        }
    }
    admin_render('account', ['errors' => $errors]);
}


function admin_telegram_connect(): void
{
    require_login();
    csrf_check();
    if (telegram_bot_token() === '') {
        flash('Primero pega el token del bot en Ajustes y guarda.', 'error');
        redirect('admin/ajustes');
    }
    try {
        $username = telegram_connect_bot();
        flash('Bot @' . $username . ' conectado. Los avisos de precio ya aparecen en las fichas.');
    } catch (Throwable $e) {
        flash('Telegram no ha aceptado la conexión: ' . $e->getMessage(), 'error');
    }
    redirect('admin/ajustes');
}
