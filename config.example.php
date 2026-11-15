<?php
/**
 * Copia este archivo como config.php y rellena tus datos.
 * El instalador (/install/) lo crea por ti si lo prefieres.
 */
return [
    // 'production' en tu hosting. 'local' muestra errores en pantalla (solo para pruebas).
    'env' => 'production',

    // Dirección pública de la web, sin barra al final.
    'base_url' => 'https://tudominio.es',

    'timezone' => 'Europe/Madrid',

    'db' => [
        // 'mysql' en Hostinger. 'sqlite' solo para pruebas en tu ordenador.
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'name' => '',
        'user' => '',
        'pass' => '',
        'sqlite_path' => __DIR__ . '/storage/database.sqlite',
    ],

    // Amazon Creators API. Se activa cuando Amazon te da acceso
    // (requiere 10 ventas en los últimos 30 días).
    'amazon' => [
        'enabled' => false,
        'client_id' => '',
        'client_secret' => '',
        'partner_tag' => '',              // Tu ID de afiliado, por ejemplo: tuweb-21
        'marketplace' => 'www.amazon.es',
        'token_url' => 'https://api.amazon.co.uk/auth/o2/token',
        'api_url' => 'https://creatorsapi.amazon/catalog/v1/getItems',
        'price_max_age_hours' => 24,      // Un precio más antiguo no se muestra.
        'show_history' => true,           // Gráfica con el historial de precios de Amazon.
        'max_per_run' => 600,             // Productos consultados en cada ejecución del cron.
    ],

    // Canal de Telegram: publica solo los productos que tocan su mínimo de 180 días.
    'telegram' => [
        'enabled' => false,
        'bot_token' => '',                // Te lo da @BotFather
        'chat_id' => '',                  // @nombredetucanal o el ID numérico
        'min_history_days' => 14,         // Historial mínimo antes de anunciar un mínimo.
        'repeat_days' => 7,               // No repetir el mismo producto antes de estos días.
    ],

    // Clave para lanzar el recolector por URL si tu plan no permite cron por línea de comandos.
    // Déjala vacía para desactivar esa opción.
    'cron_key' => '',
];
