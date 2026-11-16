<?php
declare(strict_types=1);

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    start_session();
    $id = (int) ($_SESSION['user_id'] ?? 0);
    $user = $id ? row('SELECT id, email, name FROM users WHERE id = ?', [$id]) : null;
    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        redirect('admin/entrar');
    }
    return $user;
}

function client_ip_hash(): string
{
    // HMAC con la clave secreta de la instalación: el código es público y una sal fija se podría revertir.
    return hash_hmac('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? ''), (string) config('cron_key', 'rp'));
}

function too_many_attempts(): bool
{
    $since = date('Y-m-d H:i:s', time() - 15 * 60);
    return (int) val('SELECT COUNT(*) FROM login_attempts WHERE ip_hash = ? AND created_at > ?', [client_ip_hash(), $since]) >= 5;
}

function attempt_login(string $email, string $password): bool
{
    $user = row('SELECT * FROM users WHERE email = ?', [mb_strtolower(trim($email))]);
    if ($user && password_verify($password, $user['password_hash'])) {
        start_session();
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        q('DELETE FROM login_attempts WHERE ip_hash = ?', [client_ip_hash()]);
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }
        return true;
    }
    insert('login_attempts', ['ip_hash' => client_ip_hash(), 'created_at' => now()]);
    usleep(400000);
    return false;
}

function logout(): void
{
    start_session();
    $_SESSION = [];
    session_destroy();
}
