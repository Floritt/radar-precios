# Cuándo Comprar

Web de historial de precios, reseñas y guías de compra, monetizada con enlaces de afiliado de Amazon. PHP 8 y MySQL, sin frameworks ni servicios externos: funciona en cualquier hosting compartido como Hostinger.

## Qué incluye

- **Portada** con las bajadas de precio del día, los productos más vistos, reseñas y guías.
- **Ofertas** (/ofertas): listado de bajadas, mínimos históricos y novedades.
- **Nichos** (empieza con 6 de Navidad) con sus productos ordenables por bajada o precio.
- **Ficha de producto** con el mejor precio, veredicto ("buen momento", "mejor esperar"…), botones a cada tienda, gráfica de 30, 90 y 180 días y resumen de precios.
- **Reseñas** con nota, pros, contras y veredicto, y **guías y artículos**. En cualquier texto puedes insertar fichas de producto y tablas comparativas.
- **Panel de administración** para nichos, productos, precios manuales de otras tiendas, artículos, ajustes y datos legales. Cuenta los clics a cada tienda.
- **Recolector de precios** de Amazon con su API oficial (Creators API) y publicación automática de mínimos en tu canal de Telegram.
- **Páginas legales** (aviso legal, privacidad, cookies y afiliación), sitemap.xml, robots.txt y datos estructurados para Google.
- Sin cookies de terceros ni analítica: no hace falta banner de cookies. La gráfica, la tipografía (Red Hat, licencia OFL) y los iconos (Phosphor, licencia MIT) están alojados en tu servidor.

## Requisitos

- PHP 8.1 o superior con PDO MySQL, cURL y mbstring (Hostinger los trae activados).
- Una base de datos MySQL o MariaDB.

## Instalación en Hostinger

1. **Crea la base de datos.** hPanel > Bases de datos > Bases de datos MySQL. Apunta el nombre, el usuario y la contraseña.
2. **Sube los archivos.** hPanel > Administrador de archivos > `public_html` del dominio. Sube el ZIP y extráelo ahí, de forma que `index.php` quede directamente dentro de `public_html`.
3. **Abre el instalador.** Entra en `https://tudominio.es/install/` y rellena el formulario. Crea `config.php`, las tablas, tu usuario y los nichos de Navidad. Si marcas "contenido de ejemplo", verás productos con precios inventados y un aviso amarillo; se borran desde el panel con un clic.
4. **Borra la carpeta `install`** del servidor. Queda bloqueada sola, pero es mejor quitarla.
5. **Activa HTTPS.** Con el certificado SSL activo, quita el `#` de las dos líneas de "Fuerza HTTPS" en `.htaccess`.
6. **Completa tus datos legales** en Panel > Ajustes (titular, NIF, domicilio, correo).

El panel está en `https://tudominio.es/admin`.


### Instalar sin navegador

Desde una tarea cron o por SSH:

```
php install/cli.php --db-name=NOMBRE --db-user=USUARIO --db-pass=CONTRASEÑA --base-url=https://tu-dominio --author="Tu nombre" --demo
```

No crea el usuario del panel: imprime un enlace `.../admin/crear-cuenta?token=...` de un solo uso para que elijas tu correo y contraseña. Mientras haya contenido de ejemplo, la web pide a Google que no la indexe.

## Tarea programada (cron)

Recalcula medias y tendencias cada día y, cuando Amazon esté activado, consulta sus precios.

hPanel > Avanzado > Cron Jobs > comando PHP, cada hora:

```
php /home/TU_USUARIO/domains/TU_DOMINIO/public_html/cron/update-prices.php
```

La ruta exacta aparece en el Administrador de archivos. Si tu plan no permite comandos PHP, usa la opción de URL con la clave `cron_key` de `config.php`:

```
https://tudominio.es/tareas/precios?key=TU_CRON_KEY
```

El resultado de la última ejecución se ve en el escritorio del panel.

## Amazon: cómo funcionan los precios

Amazon solo permite mostrar en tu web precios obtenidos con su API, con la fecha y hora de la comprobación. La web cumple esto así:

- **Hasta tener acceso a la API:** cada producto muestra el botón "Ver en Amazon" sin precio. El enlace lleva tu ID de afiliado si lo pones en `config.php` (`partner_tag`).
- **Con acceso:** Amazon pide 10 ventas en los últimos 30 días. Entonces, en Associates Central, crea las credenciales de Creators API y ponlas en `config.php`:

```php
'amazon' => [
    'enabled' => true,
    'client_id' => 'TU_CLIENT_ID',
    'client_secret' => 'TU_CLIENT_SECRET',
    'partner_tag' => 'tuweb-21',
    ...
],
```

A partir de ahí, el cron trae precio, título, foto y enlace oficial de cada producto con ASIN, guarda el historial y oculta cualquier precio con más de 24 horas. Los precios de Amazon no se pueden escribir a mano en el panel.

Pendiente de confirmar con el soporte de Afiliados: si guardar los precios para la gráfica histórica está permitido en tu cuenta. Si te dicen que no, pon `'show_history' => false` y la gráfica mostrará solo las otras tiendas.

## Otras tiendas

No tienen API abierta de precios. En la ficha del producto (panel) pega tu enlace de afiliado y apunta el precio y el día en "Precios de otras tiendas". Esos puntos forman su línea en la gráfica. Un precio manual de hace más de 30 días ya no cuenta como mejor precio.

## Canal de Telegram

1. Crea un canal y un bot con @BotFather (te da el `bot_token`).
2. Añade el bot como administrador del canal.
3. En `config.php`: `'enabled' => true`, el `bot_token` y `'chat_id' => '@nombredetucanal'`.
4. Pon el enlace del canal en Panel > Ajustes para que aparezca en la web.

Cada vez que un producto toca su mínimo de 180 días en Amazon, el cron lo publica con su precio, la hora de comprobación y el aviso de afiliado. No repite un producto en 7 días.

## Escribir contenido

- **Producto nuevo:** Panel > Productos > Añadir. Pega el enlace de Amazon.es; se guarda el ASIN solo.
- **Reseña:** Panel > Artículos > Nueva reseña. Nota sobre 10, pros y contras (uno por línea), veredicto y el producto analizado.
- **Atajos dentro del texto**, cada uno en su propia línea:
  - `[producto:direccion-del-producto]` inserta la ficha con precio y botones.
  - `[comparativa:producto-uno,producto-dos,producto-tres]` inserta una tabla comparativa.
- **Programar:** pon una fecha futura y estado "Publicado"; aparecerá ese día.

La "dirección del producto" es la parte final de su URL, por ejemplo `freidora-de-aire-5-5-litros`.

## Copias de seguridad

Hostinger hace copias automáticas. Para una manual: exporta la base de datos desde phpMyAdmin y descarga la carpeta `uploads/` y el archivo `config.php`.

## Estructura

```
index.php            Rutas de la web y del panel
config.php           Tus claves (lo crea el instalador; nunca lo compartas)
app/                 Código: precios, Amazon, Telegram, vistas
assets/              CSS, JavaScript y Chart.js
cron/                Recolector de precios
uploads/             Imágenes que subes desde el panel
storage/             Registros y archivos temporales
install/             Instalador (bórralo tras instalar)
```

## Pruebas en tu ordenador

Con PHP instalado, sin MySQL: en `config.php` usa `'driver' => 'sqlite'` y arranca `php -S localhost:8000` desde la carpeta. Las tablas para SQLite están en `install/schema.sqlite.sql`.

## Licencias de terceros

- Chart.js 4.4.1: licencia MIT (`assets/vendor/LICENSE-chartjs.md`).
- Parsedown 1.7.4: licencia MIT.
