<?php
/** Rutina de instalación compartida por el instalador web (index.php) y el de consola (cli.php). */
declare(strict_types=1);

function install_config(array $o): array
{
    return [
        'env' => 'production',
        'base_url' => rtrim($o['base_url'], '/'),
        'timezone' => 'Europe/Madrid',
        'db' => [
            'driver' => $o['driver'],
            'host' => $o['db_host'],
            'port' => (int) $o['db_port'],
            'name' => $o['db_name'],
            'user' => $o['db_user'],
            'pass' => $o['db_pass'],
            'sqlite_path' => dirname(__DIR__) . '/storage/database.sqlite',
        ],
        'amazon' => [
            'enabled' => false, 'client_id' => '', 'client_secret' => '', 'partner_tag' => '',
            'marketplace' => 'www.amazon.es',
            'token_url' => 'https://api.amazon.co.uk/auth/o2/token',
            'api_url' => 'https://creatorsapi.amazon/catalog/v1/getItems',
            'price_max_age_hours' => 24, 'show_history' => true, 'max_per_run' => 600,
        ],
        'telegram' => ['enabled' => false, 'bot_token' => '', 'chat_id' => '', 'min_history_days' => 14, 'repeat_days' => 7],
        'cron_key' => bin2hex(random_bytes(16)),
    ];
}

/**
 * Crea config.php, las tablas y los datos iniciales. Si $admin trae correo y contraseña crea el usuario;
 * si no, deja preparado un enlace de alta de un solo uso y devuelve su token.
 */
function install_run(array $o, ?array $admin): ?string
{
    $root = dirname(__DIR__);
    $configFile = $root . '/config.php';
    $config = install_config($o);
    if ($config['db']['driver'] === 'mysql') {
        new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['db']['host'], $config['db']['port'], $config['db']['name']),
            $config['db']['user'], $config['db']['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }
    $php = "<?php\n// Generado por el instalador el " . date('d/m/Y H:i') . ". Edita aquí las claves de Amazon y Telegram.\nreturn " . var_export($config, true) . ";\n";
    if (file_put_contents($configFile, $php) === false) {
        throw new RuntimeException('No se pudo crear config.php. Da permiso de escritura a la carpeta o súbelo a mano a partir de config.example.php.');
    }
    @chmod($configFile, 0640);
    try {
        require_once $root . '/app/bootstrap.php';
        $schema = file_get_contents(__DIR__ . '/schema.' . $config['db']['driver'] . '.sql');
        foreach (array_filter(array_map('trim', explode(';', (string) $schema))) as $stmt) {
            db()->exec($stmt);
        }
        $token = null;
        if ($admin && $admin['email'] !== '' && $admin['password'] !== '') {
            if (!val('SELECT id FROM users WHERE email = ?', [mb_strtolower($admin['email'])])) {
                insert('users', ['email' => mb_strtolower($admin['email']), 'name' => $o['author'], 'password_hash' => password_hash($admin['password'], PASSWORD_DEFAULT), 'created_at' => now()]);
            }
        } elseif (!val('SELECT COUNT(*) FROM users')) {
            $token = bin2hex(random_bytes(20));
            save_setting('setup_token_hash', hash('sha256', $token));
        }
        require_once __DIR__ . '/seed.php';
        seed_base($o['site_name'], $o['author']);
        if (!empty($o['demo']) && !val('SELECT COUNT(*) FROM products')) {
            seed_demo($o['author']);
        }
        file_put_contents($root . '/storage/install.lock', date('c'));
        return $token;
    } catch (Throwable $e) {
        @unlink($configFile);
        throw $e;
    }
}
