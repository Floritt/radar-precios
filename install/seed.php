<?php
declare(strict_types=1);

/** Nichos iniciales (temporada de Navidad) y ajustes por defecto. */
function seed_base(string $siteName, string $author): void
{
    $niches = [
        ['Decoración de Navidad', '#1F7A4D', 'Árboles artificiales, luces, adornos y belenes para vestir la casa estas fiestas.'],
        ['Juguetes y juegos de mesa', '#D7392B', 'Regalos para Papá Noel y Reyes: construcciones, muñecos y juegos para jugar en familia.'],
        ['Perfumes y sets de regalo', '#A23B72', 'Colonias, eaux de parfum y estuches de regalo para acertar sin complicarse.'],
        ['Jerséis y pijamas navideños', '#B8740A', 'Jerséis de renos, pijamas a juego para toda la familia y detalles para el amigo invisible.'],
        ['Cocina para las fiestas', '#0E7C86', 'Pequeño electrodoméstico y menaje para las comidas y cenas de Navidad.'],
        ['Tecnología para regalar', '#3B4A63', 'Auriculares, relojes inteligentes y altavoces que siempre aciertan como regalo.'],
    ];
    foreach ($niches as $i => [$name, $color, $intro]) {
        if (!val('SELECT id FROM niches WHERE slug = ?', [slugify($name)])) {
            insert('niches', ['name' => $name, 'slug' => slugify($name), 'intro' => $intro, 'color' => $color, 'sort_order' => $i + 1, 'created_at' => now()]);
        }
    }
    $defaults = [
        'site_name' => $siteName,
        'tagline' => 'Historial de precios y reseñas para comprar los regalos en el mejor momento.',
        'default_author' => $author,
    ];
    foreach ($defaults as $k => $v) {
        if (setting($k) === '') {
            save_setting($k, $v);
        }
    }
}

/** Productos y artículos de ejemplo con precios inventados (marcados con is_demo = 1). */
function seed_demo(string $author): void
{
    mt_srand(20261115);
    $r = fn(float $a, float $b) => $a + (mt_rand() / mt_getrandmax()) * ($b - $a);
    $round = fn(float $v) => $v < 5 ? round($v, 2) : max(0.99, round($v) - 0.01);

    $catalog = [
        'decoracion-de-navidad' => [
            ['Árbol de Navidad artificial 180 cm', 79, 'at', 'Árbol de ramas densas con soporte metálico. Montaje por secciones.'],
            ['Luces LED de cortina 3 x 3 metros', 24, 'at', 'Cortina de luz cálida con mando y ocho modos.'],
            ['Belén de 11 figuras de resina', 39, 'a', 'Nacimiento clásico pintado a mano, figuras de unos 10 cm.'],
        ],
        'juguetes-y-juegos-de-mesa' => [
            ['Juego de construcción de 1.000 piezas', 59, 'at', 'Set de bloques compatibles, a partir de 8 años.'],
            ['Juego de mesa familiar de estrategia', 34, 'a', 'De 2 a 5 jugadores, partidas de unos 45 minutos.'],
            ['Muñeco interactivo con sonidos', 29, 'at', 'Responde al tacto y a la voz. Funciona con pilas.'],
        ],
        'perfumes-y-sets-de-regalo' => [
            ['Eau de parfum floral 100 ml con estuche', 62, 'as', 'Estuche de regalo con loción corporal a juego.'],
            ['Set de colonia y gel para hombre', 35, 'as', 'Colonia de 100 ml y gel de ducha de 200 ml.'],
        ],
        'jerseis-y-pijamas-navidenos' => [
            ['Jersey navideño de renos unisex', 29, 'ats', 'Punto suave, tallas de la S a la XXL.'],
            ['Pijama familiar de cuadros a juego', 22, 'ats', 'Tallas para adulto y niño, algodón.'],
        ],
        'cocina-para-las-fiestas' => [
            ['Freidora de aire 5,5 litros', 99, 'at', 'Cesta antiadherente apta para lavavajillas.'],
            ['Robot amasador de 1.200 W', 129, 'a', 'Bol de acero de 5 litros y tres accesorios.'],
            ['Vajilla de 18 piezas de porcelana', 49, 'at', 'Servicio para seis personas.'],
        ],
        'tecnologia-para-regalar' => [
            ['Auriculares inalámbricos con cancelación de ruido', 89, 'at', 'Hasta 30 horas de batería con estuche.'],
            ['Reloj inteligente con GPS', 119, 'at', 'Pulsómetro, GPS integrado y una semana de batería.'],
            ['Altavoz Bluetooth resistente al agua', 45, 'at', 'Certificación IPX7 y 12 horas de autonomía.'],
        ],
    ];

    $today = strtotime(date('Y-m-d'));
    $days = 180;
    foreach ($catalog as $nicheSlug => $items) {
        $nicheId = (int) val('SELECT id FROM niches WHERE slug = ?', [$nicheSlug]);
        foreach ($items as [$name, $base, $stores, $summary]) {
            $now = now();
            $id = insert('products', [
                'niche_id' => $nicheId ?: null,
                'name' => $name,
                'slug' => unique_slug('products', $name),
                'brand' => '',
                'summary' => $summary . "\n\nProducto de ejemplo con precios inventados.",
                'amazon_url' => 'https://www.amazon.es/s?k=' . rawurlencode($name),
                'temu_url' => str_contains($stores, 't') ? 'https://www.temu.com/es/' : null,
                'shein_url' => str_contains($stores, 's') ? 'https://es.shein.com/' : null,
                'status' => 'published',
                'track' => 0,
                'is_demo' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Amazon: precio estable con cambios esporádicos, Prime Day en octubre y ofertas previas al Black Friday.
            $level = $base * $r(0.95, 1.08);
            $prime = mt_rand(0, 9) < 6;
            $primeDepth = $r(0.10, 0.22);
            $early = mt_rand(0, 9) < 3;
            $earlyDepth = $r(0.10, 0.24);
            $earlyStart = $days - 1 - mt_rand(1, 6);
            $last = null;
            $amazonPrice = null;
            for ($d = 0; $d < $days; $d++) {
                if (mt_rand(0, 99) < 3) {
                    $level = $base * $r(0.93, 1.08);
                }
                $v = $level;
                if ($prime && ($d === $days - 39 || $d === $days - 38)) {
                    $v = $base * (1 - $primeDepth);
                }
                if ($early && $d >= $earlyStart) {
                    $v = min($v, $base * (1 - $earlyDepth));
                }
                $v = $round($v);
                if ($v !== $last) {
                    insert('prices', ['product_id' => $id, 'store' => 'amazon', 'price' => $v, 'currency' => 'EUR', 'source' => 'demo',
                        'captured_at' => date('Y-m-d 09:00:00', $today - ($days - 1 - $d) * 86400)]);
                    $last = $v;
                }
                $amazonPrice = $v;
            }
            update('products', ['amazon_price' => $amazonPrice, 'amazon_price_at' => now(), 'amazon_checked_at' => now()], $id);

            // Temu y Shein: precios apuntados a mano cada pocos días.
            foreach (['t' => 'temu', 's' => 'shein'] as $flag => $store) {
                if (!str_contains($stores, $flag)) {
                    continue;
                }
                $b = $base * ($store === 'temu' ? $r(0.5, 0.7) : $r(0.6, 0.8));
                for ($d = 0; $d < $days; $d += mt_rand(4, 9)) {
                    $v = $round($b * $r(0.85, 1.12));
                    insert('prices', ['product_id' => $id, 'store' => $store, 'price' => $v, 'currency' => 'EUR', 'source' => 'manual',
                        'captured_at' => date('Y-m-d 12:00:00', $today - ($days - 1 - $d) * 86400)]);
                }
                insert('prices', ['product_id' => $id, 'store' => $store, 'price' => $round($b * $r(0.85, 1.05)), 'currency' => 'EUR', 'source' => 'manual',
                    'captured_at' => date('Y-m-d 12:00:00', $today - 86400)]);
            }
            refresh_product_stats($id);
        }
    }

    $nid = fn(string $slug) => (int) val('SELECT id FROM niches WHERE slug = ?', [$slug]) ?: null;
    $pslug = fn(string $name) => (string) val('SELECT slug FROM products WHERE name = ? AND is_demo = 1', [$name]);
    $pid = fn(string $name) => (int) val('SELECT id FROM products WHERE name = ? AND is_demo = 1', [$name]) ?: null;

    $posts = [
        [
            'type' => 'guide',
            'niche' => null,
            'title' => 'Cómo saber si una oferta de Black Friday es real',
            'excerpt' => 'Un descuento del 40 % no significa nada si el precio subió la semana anterior. Tres comprobaciones rápidas antes de pagar.',
            'body' => "Las tiendas compiten estos días con porcentajes enormes, pero el porcentaje se calcula sobre el precio que la tienda elige como referencia. La única forma de saber si pagas menos es mirar qué ha costado el producto los últimos meses.\n\n## 1. Mira el historial, no el precio tachado\n\nEl precio tachado puede ser un precio de venta recomendado que casi nunca se aplica. En la ficha de cada producto verás su precio de los últimos 180 días: si el de hoy está por debajo de la media de 90 días, es una rebaja de verdad.\n\n## 2. Compara con su mínimo\n\nSi el producto ya estuvo más barato en octubre, quizá vuelva a bajar. Cuando un producto toca su mínimo de 180 días lo marcamos en amarillo, como las etiquetas de oferta del súper.\n\n## 3. Mira otras tiendas\n\nEl mismo producto, o uno casi igual, puede costar bastante menos en Temu o en Shein. Ten en cuenta los plazos de entrega si es para regalar.\n\n[producto:" . $pslug('Freidora de aire 5,5 litros') . "]\n\n## Resumen\n\n- Desconfía del porcentaje y mira el historial.\n- Un precio en mínimo de 180 días es una buena señal.\n- Compara tiendas, pero cuenta con el envío y la devolución.",
        ],
        [
            'type' => 'guide',
            'niche' => 'jerseis-y-pijamas-navidenos',
            'title' => 'Regalos para el amigo invisible por menos de 30 euros',
            'excerpt' => 'Ideas que no acaban en un cajón, con el precio de hoy en cada tienda para que no te pases del presupuesto.',
            'body' => "El amigo invisible tiene una regla: no pasarse del presupuesto. Estas ideas suelen quedarse por debajo de 30 euros, y en la tabla tienes el mejor precio de hoy y cómo está frente a su media.\n\n[comparativa:" . $pslug('Jersey navideño de renos unisex') . ',' . $pslug('Pijama familiar de cuadros a juego') . ',' . $pslug('Luces LED de cortina 3 x 3 metros') . "]\n\n## Cómo elegir\n\nSi no conoces bien a la persona, apuesta por algo de uso común en estas fechas, como un jersey navideño o unas luces. Si es para alguien de la familia, el pijama a juego suele hacer gracia en la foto de Nochebuena.\n\n*Productos y precios de ejemplo.*",
        ],
        [
            'type' => 'review',
            'niche' => 'cocina-para-las-fiestas',
            'product' => 'Freidora de aire 5,5 litros',
            'title' => 'Plantilla de reseña: freidora de aire 5,5 litros',
            'excerpt' => 'Ejemplo de cómo queda una reseña. Sustituye este texto por tu experiencia real con el producto.',
            'rating' => 8.0,
            'verdict' => 'Aquí va tu conclusión en una frase: para quién es y si merece la pena al precio de hoy.',
            'pros' => "Escribe aquí lo mejor que hayas comprobado\nUna idea por línea",
            'cons' => "Escribe aquí lo que no te ha gustado\nSé concreto: ruido, tamaño, limpieza",
            'body' => "Esta es una reseña de ejemplo para que veas la estructura. Bórrala o edítala desde el panel.\n\n## Cómo lo hemos probado\n\nCuenta cuánto tiempo lo has usado y para qué. Una reseña es útil cuando se nota que alguien ha usado el producto de verdad.\n\n## Lo que hay que saber antes de comprar\n\nExplica tamaño, consumo, ruido o lo que sea relevante en este tipo de producto.\n\n## ¿Merece la pena?\n\nRelaciona tu opinión con el precio: mira el historial del producto y di a partir de qué precio lo comprarías.",
        ],
    ];

    $minutes = 0;
    foreach ($posts as $p) {
        $minutes += 150;
        $at = date('Y-m-d H:i:s', time() - $minutes * 60);
        insert('posts', [
            'type' => $p['type'],
            'niche_id' => $p['niche'] ? $nid($p['niche']) : null,
            'product_id' => isset($p['product']) ? $pid($p['product']) : null,
            'title' => $p['title'],
            'slug' => unique_slug('posts', $p['title']),
            'excerpt' => $p['excerpt'],
            'body' => $p['body'],
            'rating' => $p['rating'] ?? null,
            'pros' => $p['pros'] ?? null,
            'cons' => $p['cons'] ?? null,
            'verdict' => $p['verdict'] ?? null,
            'author' => $author,
            'status' => 'published',
            'is_demo' => 1,
            'published_at' => $at,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }
}
