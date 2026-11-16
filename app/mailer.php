<?php
declare(strict_types=1);

/**
 * Envío de correo con la API de correo de Hostinger (https://api.mail.hostinger.com).
 * La clave de acceso y el identificador del buzón se guardan en la base de datos (settings) desde
 * cron/ops.php, nunca en el repositorio. Con la clave "log" (solo pruebas) los correos se escriben en storage/logs.
 */
function mail_enabled(): bool
{
    return setting('mail_token', '') !== '' && setting('mail_mailbox_id', '') !== '';
}

function send_mail(string $to, string $subject, string $text, string $html = ''): bool
{
    if (!mail_enabled() || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $token = (string) setting('mail_token');
    if ($token === 'log') {
        log_line('mail', "PARA $to | $subject\n$text\n");
        return true;
    }
    $payload = ['to' => [$to], 'displayName' => site_name(), 'subject' => $subject, 'text' => $text];
    if ($html !== '') {
        $payload['html'] = $html;
    }
    $ch = curl_init('https://api.mail.hostinger.com/api/v1/mailboxes/' . rawurlencode((string) setting('mail_mailbox_id')) . '/send');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 15,
    ]);
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($code < 200 || $code >= 300) {
        log_line('mail', 'ERROR ' . $code . ' al enviar: ' . mb_substr($body, 0, 300));
        return false;
    }
    return true;
}

/** Correo en HTML sencillo con los colores de la web: título, texto, un botón y una nota al pie. */
function mail_html(string $title, string $bodyHtml, string $buttonLabel, string $buttonUrl, string $footHtml): string
{
    $site = e(site_name());
    return '<!DOCTYPE html><html lang="es"><body style="margin:0;background:#F3F5FC;font-family:Arial,Helvetica,sans-serif;color:#10162F">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F3F5FC"><tr><td align="center" style="padding:24px 12px">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:14px;overflow:hidden">'
        . '<tr><td style="background:#1F3FD8;color:#ffffff;padding:16px 24px;font-size:18px;font-weight:bold">' . $site . '</td></tr>'
        . '<tr><td style="padding:26px 24px 8px"><h1 style="margin:0 0 12px;font-size:21px;line-height:1.25">' . e($title) . '</h1>'
        . '<div style="font-size:16px;line-height:1.55">' . $bodyHtml . '</div></td></tr>'
        . '<tr><td style="padding:10px 24px 26px"><a href="' . e($buttonUrl) . '" style="display:inline-block;background:#FFD43B;color:#10162F;font-weight:bold;font-size:16px;text-decoration:none;padding:12px 22px;border-radius:10px">' . e($buttonLabel) . '</a></td></tr>'
        . '<tr><td style="padding:16px 24px;border-top:1px solid #DDE2F1;font-size:13px;line-height:1.5;color:#535B7C">' . $footHtml . '</td></tr>'
        . '</table></td></tr></table></body></html>';
}
