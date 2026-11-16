<?php
declare(strict_types=1);

/**
 * Cambios de base de datos posteriores a la instalación. Se aplican solos una vez, en la primera
 * petición o tarea cron tras subir una versión nueva. schema_version en settings guarda el último aplicado.
 */
const SCHEMA_VERSION = 3;

function ensure_schema(): void
{
    if ((int) setting('schema_version', '1') >= SCHEMA_VERSION) {
        return;
    }
    $mysql = config('db.driver') === 'mysql';
    $id = $mysql ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $tail = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
    $money = $mysql ? 'DECIMAL(10,2)' : 'REAL';

    $statements = [
        // Productos que los visitantes piden seguir pegando un enlace de Amazon.
        "CREATE TABLE IF NOT EXISTS track_requests (
            id $id,
            asin VARCHAR(10) NOT NULL,
            requests INT NOT NULL DEFAULT 1,
            status VARCHAR(12) NOT NULL DEFAULT 'pending',
            product_id INT NULL,
            error VARCHAR(255) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )$tail",
        // Avisos de precio por Telegram o por correo.
        "CREATE TABLE IF NOT EXISTS alerts (
            id $id,
            product_id INT NOT NULL,
            target_price $money NOT NULL,
            code VARCHAR(32) NOT NULL,
            chat_id VARCHAR(32) NULL,
            status VARCHAR(12) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL,
            activated_at DATETIME NULL,
            sent_at DATETIME NULL,
            sent_price $money NULL
        )$tail",
        // Límite de peticiones por visitante (seguir productos, crear avisos).
        "CREATE TABLE IF NOT EXISTS hits (
            id $id,
            bucket VARCHAR(20) NOT NULL,
            ip_hash VARCHAR(64) NOT NULL,
            created_at DATETIME NOT NULL
        )$tail",
    ];
    $indexes = [
        ['uq_track_asin', 'track_requests', 'asin', true],
        ['uq_alert_code', 'alerts', 'code', true],
        ['idx_alert_status', 'alerts', 'status, product_id', false],
        ['idx_alert_chat', 'alerts', 'chat_id', false],
        ['idx_hits', 'hits', 'bucket, ip_hash, created_at', false],
    ];
    foreach ($statements as $sql) {
        db()->exec($sql);
    }
    // v3: avisos por correo.
    try {
        db()->exec('ALTER TABLE alerts ADD COLUMN email VARCHAR(190) NULL');
    } catch (Throwable $e) {
        // La columna ya existe.
    }
    $indexes[] = ['idx_alert_email', 'alerts', 'email', false];
    foreach ($indexes as [$name, $table, $cols, $unique]) {
        try {
            db()->exec(($unique ? 'CREATE UNIQUE INDEX ' : 'CREATE INDEX ') . ($mysql ? '' : 'IF NOT EXISTS ') . "$name ON $table ($cols)");
        } catch (Throwable $e) {
            // En MySQL no existe IF NOT EXISTS para índices: si ya está creado, falla y se ignora.
        }
    }
    save_setting('schema_version', (string) SCHEMA_VERSION);
}

/** Cuenta una acción del visitante y dice si ha superado el límite permitido en la ventana de tiempo. */
function rate_limited(string $bucket, int $max, int $seconds): bool
{
    $since = date('Y-m-d H:i:s', time() - $seconds);
    $count = (int) val('SELECT COUNT(*) FROM hits WHERE bucket = ? AND ip_hash = ? AND created_at > ?', [$bucket, client_ip_hash(), $since]);
    if ($count >= $max) {
        return true;
    }
    insert('hits', ['bucket' => $bucket, 'ip_hash' => client_ip_hash(), 'created_at' => now()]);
    if (random_int(1, 50) === 1) {
        q('DELETE FROM hits WHERE created_at < ?', [date('Y-m-d H:i:s', time() - 2 * 86400)]);
    }
    return false;
}
