<?php
declare(strict_types=1);

/**
 * Avisos de precio por Telegram.
 * 1. El visitante pone un precio objetivo en la ficha y la web crea el aviso con un código.
 * 2. Abre el bot con ese código (t.me/bot?start=código); el bot recibe el mensaje y activa el aviso.
 * 3. Tras cada actualización de precios, check_alerts() envía los avisos que se cumplen, una sola vez.
 * Los mensajes llevan a la ficha de esta web, no directamente a Amazon: Amazon no permite enlaces de
 * afiliado en mensajes privados, y la compra desde la ficha sigue contando como tuya.
 */

function telegram_bot_token(): string
{
    return setting('telegram_bot_token') ?: (string) config('telegram.bot_token', '');
}

function telegram_bot_username(): string
{
    return setting('telegram_bot_username');
}

function telegram_alerts_enabled(): bool
{
    return telegram_bot_token() !== '' && telegram_bot_username() !== '';
}

/** Hay algún canal para avisar: correo o Telegram. */
function alerts_enabled(): bool
{
    return mail_enabled() || telegram_alerts_enabled();
}

/** Cómo se lo decimos al visitante: "por correo", "por Telegram" o los dos. */
function alert_channels_text(): string
{
    $c = array_filter([mail_enabled() ? 'por correo' : '', telegram_alerts_enabled() ? 'por Telegram' : '']);
    return implode(' o ', $c);
}

/** Llama a un método de la API de bots de Telegram. Devuelve el campo result o lanza una excepción. */
function telegram_api(string $method, array $params = [])
{
    $res = http_json('https://api.telegram.org/bot' . telegram_bot_token() . '/' . $method, [], $params, 15);
    if (empty($res['data']['ok'])) {
        throw new RuntimeException((string) ($res['data']['description'] ?? 'Telegram no responde (' . $res['status'] . ')'));
    }
    return $res['data']['result'] ?? null;
}

function telegram_message(string $chatId, string $html): bool
{
    try {
        telegram_api('sendMessage', ['chat_id' => $chatId, 'text' => $html, 'parse_mode' => 'HTML', 'disable_web_page_preview' => true]);
        return true;
    } catch (Throwable $e) {
        log_line('telegram', 'Mensaje a ' . $chatId . ': ' . $e->getMessage());
        // Si la persona ha bloqueado el bot, sus avisos ya no se pueden entregar.
        if (stripos($e->getMessage(), 'blocked') !== false || stripos($e->getMessage(), 'chat not found') !== false) {
            q("UPDATE alerts SET status = 'cancelled' WHERE chat_id = ? AND status = 'active'", [$chatId]);
        }
        return false;
    }
}

/** Conecta el bot: guarda su nombre de usuario y le indica a Telegram dónde enviar los mensajes. */
function telegram_connect_bot(): string
{
    $me = telegram_api('getMe');
    save_setting('telegram_bot_username', (string) ($me['username'] ?? ''));
    $secret = setting('telegram_webhook_secret');
    if ($secret === '') {
        $secret = bin2hex(random_bytes(16));
        save_setting('telegram_webhook_secret', $secret);
    }
    telegram_api('setWebhook', [
        'url' => absolute_url('telegram/' . $secret),
        'secret_token' => $secret,
        'allowed_updates' => ['message'],
        'drop_pending_updates' => true,
    ]);
    telegram_api('setMyCommands', ['commands' => [
        ['command' => 'avisos', 'description' => 'Ver mis avisos de precio'],
        ['command' => 'parar', 'description' => 'Cancelar todos mis avisos'],
    ]]);
    return (string) ($me['username'] ?? '');
}

function create_alert(int $productId, float $target): string
{
    $code = bin2hex(random_bytes(8));
    insert('alerts', ['product_id' => $productId, 'target_price' => round($target, 2), 'code' => $code, 'status' => 'pending', 'created_at' => now()]);
    return $code;
}

/**
 * Aviso por correo. Queda pendiente hasta que la persona pulsa el enlace de confirmación que le enviamos.
 * Si ya tenía un aviso para ese producto, se actualiza el precio en vez de crear otro.
 * Devuelve [código, hay que confirmar] o null si esa dirección ya tiene demasiados avisos.
 */
function create_email_alert(int $productId, float $target, string $email): ?array
{
    $email = mb_strtolower(trim($email));
    $old = row("SELECT * FROM alerts WHERE product_id = ? AND email = ? AND status IN ('pending', 'active')", [$productId, $email]);
    if ($old) {
        update('alerts', ['target_price' => round($target, 2)], (int) $old['id']);
        return [$old['code'], $old['status'] === 'pending'];
    }
    if ((int) val("SELECT COUNT(*) FROM alerts WHERE email = ? AND status IN ('pending', 'active')", [$email]) >= 40) {
        return null;
    }
    $code = bin2hex(random_bytes(16));
    insert('alerts', ['product_id' => $productId, 'target_price' => round($target, 2), 'code' => $code, 'email' => $email, 'status' => 'pending', 'created_at' => now()]);
    return [$code, true];
}

function alert_unsubscribe_url(string $code): string
{
    return absolute_url('aviso/baja/' . $code);
}

/** Correo para confirmar que la dirección es de quien ha pedido el aviso. */
function send_alert_confirmation(array $p, float $target, string $email, string $code): bool
{
    $url = absolute_url('aviso/confirmar/' . $code);
    $text = "Hola:\n\nHas pedido que te avisemos cuando «{$p['name']}» baje de " . money($target) . ".\n\n"
        . "Confirma el aviso en este enlace:\n$url\n\n"
        . "Si no has sido tú, no hagas nada: el aviso se borra solo a los 7 días.\n\n" . site_name();
    $html = mail_html(
        'Confirma tu aviso de precio',
        '<p style="margin:0 0 12px">Has pedido que te avisemos cuando <strong>' . e($p['name']) . '</strong> baje de <strong>' . e(money($target)) . '</strong>.</p>'
            . '<p style="margin:0">Pulsa el botón para confirmar que este correo es tuyo.</p>',
        'Confirmar aviso',
        $url,
        'Si no has sido tú, no hagas nada: el aviso se borra solo a los 7 días.'
    );
    return send_mail($email, 'Confirma tu aviso de precio: ' . $p['name'], $text, $html);
}

/** Correo del aviso. Enlaza a la ficha de la web, nunca a la tienda, y no incluye precios de Amazon. */
function send_alert_mail(array $p, array $alert): bool
{
    $url = absolute_url('producto/' . $p['slug']);
    $off = alert_unsubscribe_url((string) $alert['code']);
    $target = money((float) $alert['target_price']);
    $text = "«{$p['name']}» ha bajado del precio que marcaste ($target).\n\n"
        . "Mira el precio de ahora y su historial:\n$url\n\n"
        . "Los precios cambian a menudo: el válido es el que veas en la tienda al comprar.\n"
        . "Este aviso no se repetirá. Para borrar tus avisos: $off\n\n" . site_name();
    $html = mail_html(
        'Ha bajado de precio',
        '<p style="margin:0 0 12px"><strong>' . e($p['name']) . '</strong> ha bajado del precio que marcaste (' . e($target) . ').</p>'
            . '<p style="margin:0">Los precios cambian a menudo: el válido es el que veas en la tienda al comprar.</p>',
        'Ver el precio de ahora',
        $url,
        'Este aviso no se repetirá. <a href="' . e($off) . '" style="color:#1F3FD8">Borrar mis avisos</a>.'
    );
    return send_mail((string) $alert['email'], 'Ha bajado de precio: ' . $p['name'], $text, $html);
}

function alert_start_url(string $code): string
{
    return 'https://t.me/' . rawurlencode(telegram_bot_username()) . '?start=' . rawurlencode($code);
}

/** Procesa un mensaje recibido por el bot. */
function handle_telegram_update(array $update): void
{
    $msg = $update['message'] ?? null;
    if (!$msg || empty($msg['chat']['id']) || !isset($msg['text'])) {
        return;
    }
    $chatId = (string) $msg['chat']['id'];
    $text = trim((string) $msg['text']);
    $help = "Escribe /avisos para ver tus avisos o /parar para cancelarlos.";

    if (preg_match('~^/start(?:\s+([a-f0-9]{16}))?~', $text, $m)) {
        $alert = !empty($m[1]) ? row('SELECT a.*, p.name, p.slug FROM alerts a JOIN products p ON p.id = a.product_id WHERE a.code = ?', [$m[1]]) : null;
        if ($alert && $alert['status'] === 'pending') {
            q("UPDATE alerts SET chat_id = ?, status = 'active', activated_at = ? WHERE id = ?", [$chatId, now(), (int) $alert['id']]);
            telegram_message($chatId, 'Aviso activado. Te escribiré cuando <b>' . e($alert['name']) . '</b> baje de <b>' . e(money((float) $alert['target_price'])) . "</b>.\n\n" . $help);
        } elseif ($alert && $alert['chat_id'] === $chatId) {
            telegram_message($chatId, "Este aviso ya estaba activado.\n\n" . $help);
        } else {
            telegram_message($chatId, 'Hola. Para crear un aviso, entra en la ficha de un producto en ' . e(site_name()) . ' y pon el precio al que quieres comprarlo.' . "\n\n" . $help);
        }
        return;
    }
    if (str_starts_with($text, '/avisos')) {
        $list = rows("SELECT a.target_price, p.name FROM alerts a JOIN products p ON p.id = a.product_id WHERE a.chat_id = ? AND a.status = 'active' ORDER BY a.activated_at", [$chatId]);
        if (!$list) {
            telegram_message($chatId, 'No tienes avisos activos.');
            return;
        }
        $lines = array_map(fn ($a) => '• ' . e($a['name']) . ': por debajo de ' . e(money((float) $a['target_price'])), $list);
        telegram_message($chatId, "Tus avisos activos:\n" . implode("\n", $lines) . "\n\nEscribe /parar para cancelarlos todos.");
        return;
    }
    if (str_starts_with($text, '/parar')) {
        $n = q("UPDATE alerts SET status = 'cancelled' WHERE chat_id = ? AND status IN ('active', 'pending')", [$chatId])->rowCount();
        telegram_message($chatId, $n ? 'Hecho, he cancelado tus avisos. No te escribiré más.' : 'No tenías avisos activos.');
        return;
    }
    telegram_message($chatId, 'Solo sé avisarte de bajadas de precio. ' . $help);
}

/** Envía los avisos cuyo producto está ya al precio objetivo o por debajo. Devuelve cuántos ha enviado. */
function check_alerts(): int
{
    if (!alerts_enabled()) {
        return 0;
    }
    q("DELETE FROM alerts WHERE status = 'pending' AND created_at < ?", [date('Y-m-d H:i:s', time() - 7 * 86400)]);
    q("DELETE FROM alerts WHERE status = 'sent' AND sent_at < ?", [date('Y-m-d H:i:s', time() - 30 * 86400)]);
    $due = rows(
        "SELECT a.*, p.slug, p.name, p.best_price, p.best_store, p.amazon_price_at, p.updated_at AS product_updated
         FROM alerts a JOIN products p ON p.id = a.product_id
         WHERE a.status = 'active' AND p.best_price IS NOT NULL AND p.best_price <= a.target_price"
    );
    $sent = 0;
    foreach ($due as $a) {
        $p = row('SELECT * FROM products WHERE id = ?', [(int) $a['product_id']]);
        $best = $p ? best_offer(product_offers($p)) : null;
        if (!$best || $best['price'] === null || $best['price'] > (float) $a['target_price']) {
            continue; // El precio guardado ya no es visible o ha cambiado: se revisa en la próxima pasada.
        }
        $text = 'Ha bajado: <b>' . e($p['name']) . "</b>\n"
            . 'Ahora: <b>' . e(money($best['price'])) . '</b> en ' . e($best['name']) . ' (tu aviso: ' . e(money((float) $a['target_price'])) . ")\n"
            . '<i>Precio a ' . e(date_short($best['at'])) . ". Puede haber cambiado; el válido es el que veas en la tienda.</i>\n\n"
            . '<a href="' . e(absolute_url('producto/' . $p['slug'])) . '">Ver el producto y su historial</a>' . "\n\n"
            . 'Este aviso ya no se repetirá. Puedes crear otro desde la ficha.';
        $ok = !empty($a['email']) ? send_alert_mail($p, $a) : ($a['chat_id'] ? telegram_message((string) $a['chat_id'], $text) : false);
        if ($ok) {
            update('alerts', ['status' => 'sent', 'sent_at' => now(), 'sent_price' => $best['price']], (int) $a['id']);
            $sent++;
        }
    }
    return $sent;
}
