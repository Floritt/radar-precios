<?php
declare(strict_types=1);

/**
 * Recolector de precios. Consulta Amazon (Creators API) para los productos con ASIN,
 * guarda el historial, recalcula los resúmenes y avisa en Telegram de los mínimos.
 * Devuelve un resumen en texto.
 */
function run_price_update(int $maxProducts = 0): string
{
    @set_time_limit(600);
    $lock = fopen(ROOT . '/storage/cron.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
        return 'Ya hay otra actualización en marcha. Se omite esta.';
    }

    $started = microtime(true);
    $checked = 0;
    $withPrice = 0;
    $announced = 0;
    $problems = [];

    if (amazon_enabled()) {
        $limit = $maxProducts ?: (int) config('amazon.max_per_run', 600);
        $products = rows(
            "SELECT * FROM products WHERE asin IS NOT NULL AND asin <> '' AND track = 1 AND is_demo = 0
             ORDER BY CASE WHEN amazon_checked_at IS NULL THEN 0 ELSE 1 END, amazon_checked_at ASC LIMIT " . (int) $limit
        );
        foreach (array_chunk($products, 10) as $i => $batch) {
            if ($i > 0) {
                usleep(1100000); // Amazon empieza con un límite de 1 consulta por segundo.
            }
            $byAsin = [];
            foreach ($batch as $p) {
                $byAsin[$p['asin']][] = $p;
            }
            try {
                $res = amazon_get_items(array_keys($byAsin));
            } catch (Throwable $e) {
                $problems[] = $e->getMessage();
                log_line('amazon', $e->getMessage());
                if (str_contains($e->getMessage(), 'acceso')) {
                    break; // Credenciales mal: no tiene sentido seguir.
                }
                continue;
            }
            if (!empty($res['errors']['general'])) {
                $problems[] = $res['errors']['general'];
            }
            foreach ($byAsin as $asin => $list) {
                $item = $res['items'][$asin] ?? null;
                foreach ($list as $p) {
                    $wasLow = (int) $p['at_low'];
                    amazon_store_result($p, $item, $res['errors'][$asin] ?? null);
                    $checked++;
                    if ($item && $item['price'] !== null) {
                        $withPrice++;
                    }
                    if (!$wasLow && telegram_announce_low((int) $p['id'])) {
                        $announced++;
                    }
                }
            }
        }
    }

    // Recalcula los resúmenes de todo el catálogo para que medias y tendencias avancen cada día.
    foreach (rows('SELECT id FROM products') as $r) {
        refresh_product_stats((int) $r['id']);
    }

    $summary = amazon_enabled()
        ? sprintf('%d productos consultados en Amazon, %d con precio, %d publicados en Telegram.', $checked, $withPrice, $announced)
        : 'Conexión con Amazon desactivada: solo se han recalculado los resúmenes.';
    if ($problems) {
        $summary .= ' Avisos: ' . excerpt(implode(' | ', array_unique($problems)), 300);
    }
    $summary .= sprintf(' (%.1f s)', microtime(true) - $started);

    save_setting('cron_last_run', now());
    save_setting('cron_last_result', $summary);
    log_line('cron', $summary);

    flock($lock, LOCK_UN);
    fclose($lock);
    return $summary;
}
