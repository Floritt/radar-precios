<?php
declare(strict_types=1);

/**
 * Reglas de precios.
 * Amazon: solo se muestra un precio obtenido con su API y comprobado hace menos de
 * amazon.price_max_age_hours horas, siempre con su fecha y hora (normas de Amazon Afiliados).
 * Temu, Shein y otras tiendas: precios introducidos a mano desde el panel, con la fecha en que se vieron.
 */

function amazon_price_visible(array $p): bool
{
    if ($p['amazon_price'] === null || $p['amazon_price'] === '' || empty($p['amazon_price_at'])) {
        return false;
    }
    if (!empty($p['is_demo'])) {
        return true;
    }
    $maxAge = (int) config('amazon.price_max_age_hours', 24) * 3600;
    return (time() - strtotime($p['amazon_price_at'])) <= $maxAge;
}

function manual_latest(int $productId, string $store): ?array
{
    return row(
        'SELECT price, captured_at FROM prices WHERE product_id = ? AND store = ? ORDER BY captured_at DESC LIMIT 1',
        [$productId, $store]
    );
}

/** Ofertas actuales de un producto, una por tienda que tenga enlace. */
function product_offers(array $p): array
{
    $offers = [];
    $amazonUrl = amazon_link($p);
    if ($amazonUrl) {
        $visible = amazon_price_visible($p);
        $offers['amazon'] = [
            'store' => 'amazon',
            'name' => 'Amazon',
            'url' => $amazonUrl,
            'price' => $visible ? (float) $p['amazon_price'] : null,
            'list_price' => $visible && $p['amazon_list_price'] ? (float) $p['amazon_list_price'] : null,
            'at' => $visible ? $p['amazon_price_at'] : null,
            'manual' => false,
        ];
    }
    $manualStores = [
        'temu' => [$p['temu_url'] ?? '', 'Temu'],
        'shein' => [$p['shein_url'] ?? '', 'Shein'],
        'other' => [$p['other_url'] ?? '', ($p['other_name'] ?? '') ?: 'Otra tienda'],
    ];
    foreach ($manualStores as $store => [$link, $name]) {
        if (!$link) {
            continue;
        }
        $latest = manual_latest((int) $p['id'], $store);
        $offers[$store] = [
            'store' => $store,
            'name' => $name,
            'url' => $link,
            'price' => $latest ? (float) $latest['price'] : null,
            'list_price' => null,
            'at' => $latest['captured_at'] ?? null,
            'manual' => true,
        ];
    }
    return $offers;
}

/** La oferta más barata entre las que tienen precio que se puede mostrar. */
function best_offer(array $offers): ?array
{
    $best = null;
    foreach ($offers as $o) {
        if ($o['price'] === null) {
            continue;
        }
        if ($o['manual'] && $o['at'] && strtotime($o['at']) < time() - 30 * 86400) {
            continue; // Un precio manual de hace más de 30 días no cuenta como actual.
        }
        if ($best === null || $o['price'] < $best['price']) {
            $best = $o;
        }
    }
    return $best;
}

/**
 * Serie diaria de precios de una tienda: [ 'YYYY-MM-DD' => precio|null ].
 * Cada día toma el último precio conocido hasta esa fecha.
 */
function daily_series(int $productId, string $store, int $days, ?string $until = null): array
{
    $end = strtotime(date('Y-m-d', $until ? strtotime($until) : time()));
    $start = $end - ($days - 1) * 86400;
    $startSql = date('Y-m-d 00:00:00', $start);

    $before = row(
        'SELECT price FROM prices WHERE product_id = ? AND store = ? AND captured_at < ? ORDER BY captured_at DESC LIMIT 1',
        [$productId, $store, $startSql]
    );
    $points = rows(
        'SELECT price, captured_at FROM prices WHERE product_id = ? AND store = ? AND captured_at >= ? ORDER BY captured_at',
        [$productId, $store, $startSql]
    );
    $byDay = [];
    foreach ($points as $pt) {
        $byDay[substr($pt['captured_at'], 0, 10)] = (float) $pt['price'];
    }
    $series = [];
    $last = $before ? (float) $before['price'] : null;
    for ($t = $start; $t <= $end; $t += 86400) {
        $d = date('Y-m-d', $t);
        if (array_key_exists($d, $byDay)) {
            $last = $byDay[$d];
        }
        $series[$d] = $last;
    }
    return $series;
}

/** Estadísticas de una serie diaria: actual, media 90 días, mínimo 180 días, cambio 30 días y si está en mínimo. */
function series_stats(array $series): array
{
    $values = array_values($series);
    $known = array_values(array_filter($values, fn($v) => $v !== null));
    if (!$known) {
        return ['current' => null, 'avg90' => null, 'min' => null, 'max' => null, 'min180' => null, 'change30' => null, 'at_low' => false, 'days' => 0];
    }
    $current = end($values);
    $last90 = array_values(array_filter(array_slice($values, -90), fn($v) => $v !== null));
    $previous = array_values(array_filter(array_slice($values, 0, -1), fn($v) => $v !== null));
    $ago30 = $values[count($values) - 31] ?? null;

    // Inicio del tramo actual: el último día en que el precio era distinto del de hoy.
    $runStart = count($values) - 1;
    while ($runStart > 0 && $values[$runStart - 1] === $current) {
        $runStart--;
    }
    $beforeRun = array_values(array_filter(array_slice($values, 0, $runStart), fn($v) => $v !== null));
    $minHistory = (int) config('telegram.min_history_days', 14);
    $atLow = $current !== null && $beforeRun && count($known) >= $minHistory
        && $current <= min($beforeRun) && (count($values) - 1 - $runStart) <= 21;
    return [
        'current' => $current,
        'avg90' => $last90 ? array_sum($last90) / count($last90) : null,
        'min' => min($known),
        'max' => max($known),
        'min180' => $previous ? min($previous) : null,
        'change30' => ($ago30 && $current) ? ($current - $ago30) / $ago30 : null,
        'at_low' => $atLow,
        'days' => count($known),
    ];
}

/** Veredicto para el lector según el precio actual frente a su media de 90 días. */
function price_verdict(?array $stats): array
{
    if (!$stats || $stats['current'] === null || !$stats['avg90'] || $stats['days'] < 7) {
        return ['key' => 'new', 'label' => 'Aún sin historial suficiente', 'text' => 'Llevamos poco tiempo siguiendo este producto. En unos días podrás ver si el precio es bueno.'];
    }
    $diff = $stats['current'] / $stats['avg90'] - 1;
    if ($stats['at_low']) {
        return ['key' => 'low', 'label' => 'En su precio más bajo', 'text' => 'Es el precio más bajo que hemos visto en los últimos 180 días.'];
    }
    if ($diff <= -0.07) {
        return ['key' => 'good', 'label' => 'Buen momento para comprar', 'text' => 'Está un ' . pct(-$diff, false) . ' por debajo de su precio medio de 90 días.'];
    }
    if ($diff >= 0.03) {
        return ['key' => 'high', 'label' => 'Mejor esperar', 'text' => 'Está un ' . pct($diff, false) . ' por encima de su precio medio de 90 días.'];
    }
    return ['key' => 'normal', 'label' => 'Precio habitual', 'text' => 'Está en línea con su precio medio de 90 días.'];
}

/** Tienda de referencia para el historial: Amazon si tiene datos; si no, la primera con datos. */
function primary_store(int $productId): ?string
{
    $stores = array_column(rows('SELECT DISTINCT store FROM prices WHERE product_id = ?', [$productId]), 'store');
    if (in_array('amazon', $stores, true)) {
        return 'amazon';
    }
    return $stores[0] ?? null;
}

/** Recalcula los campos resumen de un producto (mejor precio, media, mínimo, tendencia). */
function refresh_product_stats(int $productId): void
{
    $p = row('SELECT * FROM products WHERE id = ?', [$productId]);
    if (!$p) {
        return;
    }
    $best = best_offer(product_offers($p));
    $store = primary_store($productId);
    $stats = $store ? series_stats(daily_series($productId, $store, 180, $store === 'amazon' ? ($p['amazon_price_at'] ?: null) : null)) : null;

    update('products', [
        'best_price' => $best ? $best['price'] : null,
        'best_store' => $best ? $best['store'] : null,
        'avg90' => $stats && $stats['avg90'] ? round($stats['avg90'], 2) : null,
        'min180' => $stats && $stats['min180'] ? round($stats['min180'], 2) : null,
        'change30' => $stats && $stats['change30'] !== null ? round($stats['change30'], 4) : null,
        'at_low' => $stats && $stats['at_low'] ? 1 : 0,
        'low_store' => $stats && $stats['at_low'] ? $store : null,
        'stats_store' => $store,
    ], $productId);
}

/** Datos para la gráfica de la ficha: 180 días, una serie por tienda con historial. */
function chart_payload(array $p): array
{
    $id = (int) $p['id'];
    $series = [];
    $labels = [];
    foreach (array_keys(STORES) as $store) {
        if ($store === 'amazon' && !config('amazon.show_history', true) && empty($p['is_demo'])) {
            continue;
        }
        if (!val('SELECT COUNT(*) FROM prices WHERE product_id = ? AND store = ?', [$id, $store])) {
            continue;
        }
        if ($store === 'amazon' && !amazon_price_visible($p)) {
            continue;
        }
        $s = daily_series($id, $store, 180);
        $labels = array_keys($s);
        $series[] = [
            'store' => $store,
            'name' => $store === 'other' ? (($p['other_name'] ?? '') ?: 'Otra tienda') : STORES[$store],
            'values' => array_values($s),
        ];
    }
    return ['labels' => $labels, 'series' => $series];
}

/** Productos publicados para listados, con su nicho. */
function product_list(string $where = '1=1', array $params = [], string $order = 'p.updated_at DESC', int $limit = 24): array
{
    return rows(
        "SELECT p.*, n.name AS niche_name, n.slug AS niche_slug, n.color AS niche_color
         FROM products p LEFT JOIN niches n ON n.id = p.niche_id
         WHERE p.status = 'published' AND ($where)
         ORDER BY $order LIMIT $limit",
        $params
    );
}

/** Precio que se puede enseñar en una etiqueta de listado (respeta la caducidad de Amazon). */
function tag_price(array $p): ?array
{
    if ($p['best_price'] === null || !$p['best_store']) {
        return null;
    }
    if ($p['best_store'] === 'amazon' && !amazon_price_visible($p)) {
        return null;
    }
    return ['price' => (float) $p['best_price'], 'store' => $p['best_store']];
}
