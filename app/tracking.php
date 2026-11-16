<?php
declare(strict_types=1);

/**
 * "Pega un enlace y lo seguimos": convierte un enlace de Amazon (también los cortos amzn.eu del móvil)
 * en un ASIN y crea la ficha del producto. Sin la API de Amazon activa, la petición queda en cola
 * y la tarea de precios la procesa cuando se active.
 */

/** Saca el ASIN de un texto pegado. Sigue los enlaces cortos (amzn.eu, amzn.to, a.co) sin descargar la página. */
function resolve_amazon_input(string $input): ?string
{
    $input = trim($input);
    if ($input === '') {
        return null;
    }
    if ($asin = extract_asin($input)) {
        return $asin;
    }
    if (!preg_match('~https?://(?:www\.)?(amzn\.eu|amzn\.to|a\.co)/\S+~i', $input, $m)) {
        return null;
    }
    $url = $m[0];
    for ($hop = 0; $hop < 4; $hop++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; RadarDePrecios/1.0)',
        ]);
        $headers = (string) curl_exec($ch);
        curl_close($ch);
        if (!preg_match('/^location:\s*(\S+)/mi', $headers, $loc)) {
            return null;
        }
        $next = trim($loc[1]);
        if ($asin = extract_asin($next)) {
            return $asin;
        }
        $host = (string) parse_url($next, PHP_URL_HOST);
        if (!preg_match('~(^|\.)(amazon\.[a-z.]+|amzn\.eu|amzn\.to|a\.co)$~i', $host)) {
            return null; // Solo seguimos redirecciones dentro de Amazon.
        }
        $url = $next;
    }
    return null;
}

/** Nombre corto a partir del título de Amazon, que suele ser larguísimo. */
function short_product_name(string $title): string
{
    $title = trim(preg_replace('/\s+/', ' ', $title));
    if (mb_strlen($title) <= 90) {
        return $title;
    }
    foreach ([' | ', ' - ', ', ', ' – '] as $sep) {
        $pos = mb_strpos($title, $sep, 25);
        if ($pos !== false && $pos <= 90) {
            return mb_substr($title, 0, $pos);
        }
    }
    $cut = mb_substr($title, 0, 90);
    return mb_substr($cut, 0, (int) mb_strrpos($cut, ' ')) ?: $cut;
}

/** Crea la ficha oculta (status unlisted) con los datos que devuelve la API y guarda el primer precio. */
function create_product_from_item(string $asin, array $item): int
{
    $name = short_product_name((string) ($item['title'] ?: 'Producto de Amazon ' . $asin));
    $at = now();
    $id = (int) insert('products', [
        'niche_id' => null,
        'name' => $name,
        'slug' => unique_slug('products', slugify($name) ?: strtolower($asin)),
        'brand' => '',
        'summary' => '',
        'asin' => $asin,
        'status' => 'unlisted',
        'track' => 1,
        'is_demo' => 0,
        'created_at' => $at,
        'updated_at' => $at,
    ]);
    amazon_store_result(row('SELECT * FROM products WHERE id = ?', [$id]), $item);
    refresh_product_stats($id);
    return $id;
}

/**
 * Pide seguir un ASIN. Devuelve ['status' => existing|created|queued|error, 'product' => ?array, 'message' => string].
 */
function request_tracking(string $asin): array
{
    $existing = row("SELECT * FROM products WHERE asin = ? AND status IN ('published', 'unlisted') ORDER BY status = 'published' DESC LIMIT 1", [$asin]);
    if ($existing) {
        return ['status' => 'existing', 'product' => $existing, 'message' => 'Ya seguimos este producto.'];
    }
    if (amazon_enabled()) {
        try {
            $res = amazon_get_items([$asin]);
            $item = $res['items'][$asin] ?? null;
            if ($item && !empty($item['title'])) {
                $id = create_product_from_item($asin, $item);
                return ['status' => 'created', 'product' => row('SELECT * FROM products WHERE id = ?', [$id]), 'message' => 'Empezamos a seguir este producto.'];
            }
            return ['status' => 'error', 'product' => null, 'message' => 'Amazon.es no nos devuelve ese producto. Comprueba que el enlace sea de Amazon España y que el producto siga a la venta.'];
        } catch (Throwable $e) {
            log_line('amazon', 'Seguir ' . $asin . ': ' . $e->getMessage());
            // Si la API falla, la petición se guarda en cola igual que cuando está desactivada.
        }
    }
    $at = now();
    $prev = row('SELECT id FROM track_requests WHERE asin = ?', [$asin]);
    if ($prev) {
        q('UPDATE track_requests SET requests = requests + 1, updated_at = ? WHERE id = ?', [$at, (int) $prev['id']]);
    } else {
        insert('track_requests', ['asin' => $asin, 'requests' => 1, 'status' => 'pending', 'created_at' => $at, 'updated_at' => $at]);
    }
    return ['status' => 'queued', 'product' => null, 'message' => 'Apuntado.'];
}

/** Procesa la cola de peticiones cuando la API de Amazon está activa. Lo llama la tarea de precios. */
function process_track_requests(int $max = 100): int
{
    if (!amazon_enabled()) {
        return 0;
    }
    $pending = rows("SELECT * FROM track_requests WHERE status = 'pending' ORDER BY requests DESC, created_at ASC LIMIT " . $max);
    $created = 0;
    foreach (array_chunk($pending, 10) as $i => $batch) {
        if ($i > 0) {
            usleep(1100000);
        }
        try {
            $res = amazon_get_items(array_column($batch, 'asin'));
        } catch (Throwable $e) {
            log_line('amazon', 'Cola de seguimiento: ' . $e->getMessage());
            break;
        }
        foreach ($batch as $r) {
            $asin = $r['asin'];
            $existing = val('SELECT id FROM products WHERE asin = ? LIMIT 1', [$asin]);
            $item = $res['items'][$asin] ?? null;
            if ($existing) {
                update('track_requests', ['status' => 'done', 'product_id' => (int) $existing, 'updated_at' => now()], (int) $r['id']);
            } elseif ($item && !empty($item['title'])) {
                $id = create_product_from_item($asin, $item);
                update('track_requests', ['status' => 'done', 'product_id' => $id, 'updated_at' => now()], (int) $r['id']);
                $created++;
            } else {
                update('track_requests', ['status' => 'error', 'error' => mb_substr((string) ($res['errors'][$asin] ?? 'Sin datos en Amazon.es'), 0, 250), 'updated_at' => now()], (int) $r['id']);
            }
        }
    }
    return $created;
}
