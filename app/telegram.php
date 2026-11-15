<?php
declare(strict_types=1);

function telegram_enabled(): bool
{
    return (bool) config('telegram.enabled') && config('telegram.bot_token') && config('telegram.chat_id');
}

function telegram_send(string $html): bool
{
    $url = 'https://api.telegram.org/bot' . config('telegram.bot_token') . '/sendMessage';
    try {
        $res = http_json($url, [], [
            'chat_id' => config('telegram.chat_id'),
            'text' => $html,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => false,
        ], 15);
    } catch (Throwable $e) {
        log_line('telegram', 'Error: ' . $e->getMessage());
        return false;
    }
    if (empty($res['data']['ok'])) {
        log_line('telegram', 'Telegram rechazó el mensaje: ' . excerpt($res['raw'], 200));
        return false;
    }
    return true;
}

/**
 * Si el producto acaba de tocar su mínimo de 180 días en Amazon, lo publica en el canal.
 * No repite el mismo producto en los días que marque telegram.repeat_days.
 */
function telegram_announce_low(int $productId): bool
{
    if (!telegram_enabled()) {
        return false;
    }
    $p = row("SELECT * FROM products WHERE id = ? AND status = 'published'", [$productId]);
    if (!$p || !(int) $p['at_low'] || !amazon_price_visible($p) || !empty($p['is_demo'])) {
        return false;
    }
    $since = date('Y-m-d H:i:s', time() - (int) config('telegram.repeat_days', 7) * 86400);
    if (val('SELECT COUNT(*) FROM telegram_log WHERE product_id = ? AND sent_at > ?', [$productId, $since])) {
        return false;
    }
    $text = '<b>' . e($p['name']) . '</b>' . "\n"
        . 'Precio más bajo de los últimos 180 días en Amazon: <b>' . e(money($p['amazon_price'])) . '</b>' . "\n"
        . '<i>Precio a ' . e(date_short($p['amazon_price_at'])) . '. Puede haber cambiado.</i>' . "\n\n"
        . '<a href="' . e(absolute_url('producto/' . $p['slug'])) . '">Ver historial</a>  |  '
        . '<a href="' . e(amazon_link($p)) . '">Ver en Amazon</a>' . "\n\n"
        . 'Como afiliado de Amazon, obtengo ingresos por las compras adscritas que cumplen los requisitos aplicables.';
    if (telegram_send($text)) {
        insert('telegram_log', ['product_id' => $productId, 'price' => $p['amazon_price'], 'sent_at' => now()]);
        return true;
    }
    return false;
}
