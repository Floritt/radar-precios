<?php
declare(strict_types=1);

/**
 * Cliente mínimo de Amazon Creators API (sustituta de PA-API desde 2026).
 * Documentación: https://affiliate-program.amazon.com/creatorsapi/docs/en-us/introduction
 */

function amazon_enabled(): bool
{
    return (bool) config('amazon.enabled')
        && config('amazon.client_id') && config('amazon.client_secret') && config('amazon.partner_tag');
}

/** Extrae el ASIN de un enlace de Amazon o lo valida si ya es un ASIN. */
function extract_asin(string $input): ?string
{
    $input = trim($input);
    if (preg_match('~^[A-Z0-9]{10}$~i', $input)) {
        return strtoupper($input);
    }
    if (preg_match('~/(?:dp|gp/product|gp/aw/d|exec/obidos/asin|product)/([A-Z0-9]{10})~i', $input, $m)) {
        return strtoupper($m[1]);
    }
    return null;
}

/** Enlace de afiliado al producto. Usa el que devuelve la API cuando existe. */
function amazon_link(array $p): ?string
{
    if (!empty($p['amazon_url'])) {
        return $p['amazon_url'];
    }
    if (empty($p['asin'])) {
        return null;
    }
    $tag = config('amazon.partner_tag');
    $domain = config('amazon.marketplace', 'www.amazon.es');
    return 'https://' . $domain . '/dp/' . rawurlencode($p['asin']) . ($tag ? '?tag=' . rawurlencode($tag) : '');
}

function http_json(string $url, array $headers, array $body, int $timeout = 20): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json', 'Accept: application/json'], $headers),
        CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_SLASHES),
    ]);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($raw === false) {
        throw new RuntimeException('Sin respuesta de ' . parse_url($url, PHP_URL_HOST) . ': ' . $error);
    }
    $data = json_decode((string) $raw, true);
    return ['status' => $status, 'data' => is_array($data) ? $data : [], 'raw' => (string) $raw];
}

/** Token OAuth (client_credentials), guardado en storage/ hasta que caduca. */
function amazon_token(): string
{
    $cache = ROOT . '/storage/amazon_token.json';
    if (is_file($cache)) {
        $t = json_decode((string) file_get_contents($cache), true);
        if (!empty($t['access_token']) && ($t['expires_at'] ?? 0) > time() + 60) {
            return $t['access_token'];
        }
    }
    $res = http_json(config('amazon.token_url'), [], [
        'grant_type' => 'client_credentials',
        'client_id' => config('amazon.client_id'),
        'client_secret' => config('amazon.client_secret'),
        'scope' => 'creatorsapi::default',
    ]);
    if ($res['status'] !== 200 || empty($res['data']['access_token'])) {
        throw new RuntimeException('Amazon no ha dado acceso (HTTP ' . $res['status'] . '): ' . excerpt($res['raw'], 200));
    }
    $token = $res['data']['access_token'];
    file_put_contents($cache, json_encode([
        'access_token' => $token,
        'expires_at' => time() + (int) ($res['data']['expires_in'] ?? 3600),
    ]));
    @chmod($cache, 0600);
    return $token;
}

/**
 * Consulta hasta 10 ASIN. Devuelve ['items' => [asin => datos], 'errors' => [asin|general => mensaje]].
 */
function amazon_get_items(array $asins): array
{
    $asins = array_values(array_slice(array_unique($asins), 0, 10));
    $marketplace = config('amazon.marketplace', 'www.amazon.es');
    $res = http_json(config('amazon.api_url'), [
        'Authorization: Bearer ' . amazon_token(),
        'x-marketplace: ' . $marketplace,
    ], [
        'itemIds' => $asins,
        'itemIdType' => 'ASIN',
        'marketplace' => $marketplace,
        'partnerTag' => config('amazon.partner_tag'),
        'condition' => 'New',
        'languagesOfPreference' => ['es_ES'],
        'resources' => [
            'itemInfo.title',
            'images.primary.large',
            'offersV2.listings.price',
            'offersV2.listings.availability',
            'offersV2.listings.isBuyBoxWinner',
        ],
    ]);

    if ($res['status'] === 401 || $res['status'] === 403) {
        @unlink(ROOT . '/storage/amazon_token.json');
    }
    if ($res['status'] >= 400 && empty($res['data'])) {
        throw new RuntimeException('Error de Amazon (HTTP ' . $res['status'] . '): ' . excerpt($res['raw'], 200));
    }

    $data = $res['data'];
    $list = $data['itemsResult']['items'] ?? $data['itemResults']['items'] ?? $data['ItemsResult']['Items'] ?? [];
    $items = [];
    foreach ($list as $item) {
        $parsed = amazon_parse_item($item);
        if ($parsed['asin']) {
            $items[$parsed['asin']] = $parsed;
        }
    }
    $errors = [];
    foreach (($data['errors'] ?? $data['Errors'] ?? []) as $err) {
        $msg = ($err['code'] ?? $err['Code'] ?? 'Error') . ': ' . ($err['message'] ?? $err['Message'] ?? '');
        $matched = false;
        foreach ($asins as $a) {
            if (str_contains($msg, $a)) {
                $errors[$a] = excerpt($msg, 250);
                $matched = true;
            }
        }
        if (!$matched) {
            $errors['general'] = excerpt($msg, 250);
        }
    }
    if ($res['status'] >= 400 && !$errors) {
        $errors['general'] = 'HTTP ' . $res['status'] . ': ' . excerpt($res['raw'], 200);
    }
    return ['items' => $items, 'errors' => $errors];
}

/** Extrae título, imagen, enlace y precio de un artículo de la respuesta (acepta camelCase y PascalCase). */
function amazon_parse_item(array $item): array
{
    $g = function (array $arr, array $paths) {
        foreach ($paths as $path) {
            $v = $arr;
            foreach (explode('.', $path) as $k) {
                if (!is_array($v) || !array_key_exists($k, $v)) {
                    $v = null;
                    break;
                }
                $v = $v[$k];
            }
            if ($v !== null) {
                return $v;
            }
        }
        return null;
    };

    $listings = $g($item, ['offersV2.listings', 'OffersV2.Listings']) ?: [];
    $listing = null;
    foreach ($listings as $l) {
        if ($g($l, ['isBuyBoxWinner', 'IsBuyBoxWinner'])) {
            $listing = $l;
            break;
        }
    }
    $listing ??= $listings[0] ?? null;

    $price = $listing ? $g($listing, ['price.money.amount', 'Price.Money.Amount']) : null;
    $listPrice = $listing ? $g($listing, ['price.savingBasis.money.amount', 'Price.SavingBasis.Money.Amount']) : null;

    return [
        'asin' => (string) $g($item, ['asin', 'ASIN']),
        'title' => $g($item, ['itemInfo.title.displayValue', 'ItemInfo.Title.DisplayValue']),
        'url' => $g($item, ['detailPageURL', 'DetailPageURL']),
        'image' => $g($item, ['images.primary.large.url', 'Images.Primary.Large.URL', 'images.primary.medium.url']),
        'price' => $price !== null ? (float) $price : null,
        'list_price' => $listPrice !== null ? (float) $listPrice : null,
        'currency' => $listing ? $g($listing, ['price.money.currency', 'Price.Money.Currency']) : null,
    ];
}

/**
 * Guarda el resultado de Amazon en un producto: precio actual comprobado ahora
 * y una fila de historial si el precio cambió o si han pasado más de 20 horas.
 */
function amazon_store_result(array $product, ?array $item, ?string $error = null): void
{
    $id = (int) $product['id'];
    $now = now();
    if (!$item || $item['price'] === null) {
        update('products', [
            'amazon_price' => null,
            'amazon_list_price' => null,
            'amazon_checked_at' => $now,
            'amazon_error' => $error ?: 'Sin oferta disponible en Amazon ahora mismo',
        ], $id);
        refresh_product_stats($id);
        return;
    }
    update('products', [
        'amazon_title' => $item['title'] ? mb_substr($item['title'], 0, 400) : $product['amazon_title'],
        'amazon_url' => $item['url'] ?: $product['amazon_url'],
        'amazon_image_url' => $item['image'] ?: $product['amazon_image_url'],
        'amazon_price' => $item['price'],
        'amazon_list_price' => $item['list_price'],
        'amazon_price_at' => $now,
        'amazon_checked_at' => $now,
        'amazon_error' => null,
    ], $id);

    $last = row("SELECT price, captured_at FROM prices WHERE product_id = ? AND store = 'amazon' ORDER BY captured_at DESC LIMIT 1", [$id]);
    $changed = !$last || abs((float) $last['price'] - $item['price']) >= 0.005;
    $old = !$last || strtotime($last['captured_at']) < time() - 20 * 3600;
    if ($changed || $old) {
        insert('prices', [
            'product_id' => $id,
            'store' => 'amazon',
            'price' => $item['price'],
            'currency' => $item['currency'] ?: 'EUR',
            'source' => 'api',
            'captured_at' => $now,
        ]);
    }
    refresh_product_stats($id);
}
