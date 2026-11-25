<?php
/**
 * Comprobaciones de mantenimiento desde consola: php cron/ops.php
 * Muestra el estado de la instalación y, si aún no hay usuario del panel, un enlace de alta de un solo uso.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
chdir(dirname(__DIR__));
require 'app/bootstrap.php';

$tokenFile = 'storage/setup_token.txt';
if ((int) val('SELECT COUNT(*) FROM users') === 0) {
    if (!is_file($tokenFile)) {
        $t = bin2hex(random_bytes(20));
        file_put_contents($tokenFile, $t);
        save_setting('setup_token_hash', hash('sha256', $t));
    }
    echo 'ALTA ', rtrim((string) config('base_url'), '/'), '/admin/crear-cuenta?token=', trim((string) file_get_contents($tokenFile)), "\n";
} else {
    @unlink($tokenFile);
    echo "Usuario del panel ya creado\n";
}

ensure_schema();
// php cron/ops.php --correo=IDENTIFICADOR_DEL_BUZON:CLAVE  guarda el acceso a la API de correo (o --correo=quitar)
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--correo=')) {
        $v = substr($arg, 9);
        if ($v === 'quitar') {
            save_setting('mail_mailbox_id', '');
            save_setting('mail_token', '');
        } elseif (preg_match('/^([A-Za-z0-9]{8,64}):(\S{3,})$/', $v, $m)) {
            save_setting('mail_mailbox_id', $m[1]);
            save_setting('mail_token', $m[2]);
        }
    }
    // php cron/ops.php --afiliado=mitienda-21  guarda el ID de seguimiento de Amazon Afiliados
    if (str_starts_with($arg, '--afiliado=') && preg_match('/^[A-Za-z0-9_-]{3,40}$/', substr($arg, 11))) {
        save_setting('amazon_tag', substr($arg, 11));
    }
    // --titular=BASE64 cambia el nombre del titular que sale en el aviso legal; --sin-nif quita el NIF de la web.
    if (str_starts_with($arg, '--titular=') && ($t = base64_decode(substr($arg, 10), true)) !== false && trim($t) !== '') {
        save_setting('legal_owner', mb_substr(trim($t), 0, 120));
    }
    // --lema=BASE64 cambia la frase que sale bajo el logo en el pie de página.
    if (str_starts_with($arg, '--lema=') && ($t = base64_decode(substr($arg, 7), true)) !== false && trim($t) !== '') {
        save_setting('tagline', mb_substr(trim($t), 0, 160));
    }
    if ($arg === '--sin-nif') {
        save_setting('legal_nif', '');
    }
}
echo 'ID de afiliado: ', amazon_tag() ?: 'sin poner', "\n";
echo 'Aviso legal: titular ', setting('legal_owner') ?: 'sin poner', ' | NIF ', setting('legal_nif') !== '' ? 'visible' : 'quitado', "\n";
echo 'Lema: ', setting('tagline') ?: 'sin poner', "\n";
echo 'Correo: ', mail_enabled() ? 'activado' : 'sin configurar', "\n";
if (mail_enabled() && setting('mail_token') !== 'log') {
    // Comprueba la clave y el buzón sin enviar ningún correo.
    $ch = curl_init('https://api.mail.hostinger.com/api/v1/me');
    curl_setopt_array($ch, [CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . setting('mail_token')], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 12]);
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    echo 'API de correo: ', $code, str_contains($body, (string) setting('mail_mailbox_id')) ? ' | buzon reconocido' : ' | buzon NO reconocido', "\n";
}
echo 'Esquema v', setting('schema_version'), ' | avisos activos ', val("SELECT COUNT(*) FROM alerts WHERE status = 'active'"), ' | avisos por zona activos ', val("SELECT COUNT(*) FROM zone_alerts WHERE status = 'active'"), ' | peticiones en cola ', val("SELECT COUNT(*) FROM track_requests WHERE status = 'pending'"), "\n";
echo 'PHP ', PHP_VERSION, ' | productos ', val('SELECT COUNT(*) FROM products'), ' | nichos ', val('SELECT COUNT(*) FROM niches'),
    ' | ultima tarea de precios: ', setting('cron_last_run', 'nunca'), "\n";

$base = rtrim((string) config('base_url'), '/');
foreach (['/', '/gasolina', '/gasolina/avisos', '/nicho/accesorios-de-coche', '/producto/baliza-v16-conectada', '/seguir', '/blog', '/comparativas', '/admin/entrar', '/config.php', '/storage/install.lock', '/install/cli.php', '/.git/config'] as $u) {
    $h = @get_headers($base . $u);
    echo $u, ' ', $h ? $h[0] : 'sin respuesta', "\n";
}
