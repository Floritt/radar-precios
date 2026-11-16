<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require APP . '/controllers/public.php';
require APP . '/controllers/admin.php';

// Un solo dominio: cualquier otro nombre que apunte a la web (alias, www, subdominio antiguo) redirige al de base_url.
$canonicalHost = strtolower((string) parse_url((string) config('base_url'), PHP_URL_HOST));
$requestHost = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
if (config('env') === 'production' && $canonicalHost !== '' && $requestHost !== '' && $requestHost !== $canonicalHost) {
    header('Location: ' . rtrim((string) config('base_url'), '/') . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
    exit;
}

send_security_headers();
try {
    ensure_schema();
} catch (Throwable $e) {
    log_line('db', 'Migración: ' . $e->getMessage());
}

$routes = [
    // Público
    ['GET', '#^/$#', 'page_home'],
    ['GET', '#^/nicho/([a-z0-9\-]+)$#', 'page_niche'],
    ['GET', '#^/producto/([a-z0-9\-]+)$#', 'page_product'],
    ['GET', '#^/ofertas$#', 'page_deals'],
    ['GET', '#^/blog$#', 'page_blog'],
    ['GET', '#^/blog/([a-z0-9\-]+)$#', 'page_post'],
    ['GET', '#^/comparativas$#', 'page_comparisons'],
    ['GET', '#^/comparativas/([a-z0-9\-]+)$#', 'page_post'],
    ['GET', '#^/resenas$#', 'page_reviews'],
    ['GET', '#^/resenas/([a-z0-9\-]+)$#', 'page_post'],
    ['GET', '#^/buscar$#', 'page_search'],
    ['GET|POST', '#^/seguir$#', 'page_follow'],
    ['POST', '#^/aviso$#', 'action_alert'],
    ['GET', '#^/aviso/confirmar/([a-f0-9]{32})$#', 'alert_confirm'],
    ['GET|POST', '#^/aviso/baja/([a-f0-9]{32})$#', 'alert_unsubscribe'],
    ['POST', '#^/telegram/([a-f0-9]{32})$#', 'telegram_webhook'],
    ['GET', '#^/legal/([a-z\-]+)$#', 'page_legal'],
    ['GET', '#^/sitemap\.xml$#', 'page_sitemap'],
    ['GET', '#^/robots\.txt$#', 'page_robots'],
    ['POST', '#^/api/clic$#', 'api_click'],
    ['POST', '#^/api/visita$#', 'api_visit'],
    ['GET', '#^/tareas/precios$#', 'task_prices'],

    // Panel
    ['GET|POST', '#^/admin/entrar$#', 'admin_login'],
    ['GET|POST', '#^/admin/crear-cuenta$#', 'admin_setup'],
    ['POST', '#^/admin/salir$#', 'admin_logout'],
    ['GET', '#^/admin$#', 'admin_dashboard'],
    ['GET', '#^/admin/nichos$#', 'admin_niches'],
    ['GET|POST', '#^/admin/nichos/(nuevo|\d+)$#', 'admin_niche_edit'],
    ['POST', '#^/admin/nichos/(\d+)/borrar$#', 'admin_niche_delete'],
    ['GET', '#^/admin/productos$#', 'admin_products'],
    ['GET|POST', '#^/admin/productos/(nuevo|\d+)$#', 'admin_product_edit'],
    ['POST', '#^/admin/productos/(\d+)/borrar$#', 'admin_product_delete'],
    ['POST', '#^/admin/productos/(\d+)/precio$#', 'admin_price_add'],
    ['POST', '#^/admin/productos/(\d+)/amazon$#', 'admin_product_amazon'],
    ['POST', '#^/admin/precios/(\d+)/borrar$#', 'admin_price_delete'],
    ['GET', '#^/admin/articulos$#', 'admin_posts'],
    ['GET|POST', '#^/admin/articulos/(nuevo|\d+)$#', 'admin_post_edit'],
    ['POST', '#^/admin/articulos/(\d+)/borrar$#', 'admin_post_delete'],
    ['GET', '#^/admin/estadisticas$#', 'admin_stats'],
    ['GET|POST', '#^/admin/ajustes$#', 'admin_settings'],
    ['POST', '#^/admin/telegram/conectar$#', 'admin_telegram_connect'],
    ['GET|POST', '#^/admin/cuenta$#', 'admin_account'],
    ['POST', '#^/admin/ejemplo/borrar$#', 'admin_demo_delete'],
];

$path = request_path();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'HEAD') {
    $method = 'GET';
}

try {
    foreach ($routes as [$methods, $pattern, $handler]) {
        if (preg_match($pattern, $path, $m)) {
            if (!in_array($method, explode('|', $methods), true)) {
                http_response_code(405);
                exit('Método no permitido');
            }
            array_shift($m);
            $handler(...$m);
            exit;
        }
    }
    not_found();
} catch (PDOException $e) {
    error_log('BD: ' . $e->getMessage());
    http_response_code(500);
    echo config('env') === 'local' ? e($e->getMessage()) : 'No se puede conectar con la base de datos ahora mismo. Inténtalo en unos minutos.';
}
