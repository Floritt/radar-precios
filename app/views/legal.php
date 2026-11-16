<?php
/** @var string $slug @var string $title */
$owner = setting('legal_owner', '[Nombre y apellidos o razón social]');
$nif = setting('legal_nif', '[NIF]');
$address = setting('legal_address', '[Domicilio]');
$email = setting('legal_email', '[correo de contacto]');
$site = site_name();
$domain = parse_url((string) config('base_url'), PHP_URL_HOST) ?: 'esta web';
?>
<div class="page-head">
  <div class="wrap"><h1><?= e($title) ?></h1></div>
</div>
<div class="wrap page">
  <article class="art--narrow">
  <div class="prose">
<?php if ($slug === 'aviso-legal'): ?>
    <p>En cumplimiento del artículo 10 de la Ley 34/2002, de servicios de la sociedad de la información y de comercio electrónico (LSSI), se informa de los datos del titular de <?= e($domain) ?>.</p>
    <ul>
      <li>Titular: <?= e($owner) ?></li>
      <li>NIF: <?= e($nif) ?></li>
      <li>Domicilio: <?= e($address) ?></li>
      <li>Correo electrónico: <?= e($email) ?></li>
    </ul>
    <h2>Objeto</h2>
    <p><?= e($site) ?> publica historiales de precios, guías de compra y reseñas de productos que se venden en tiendas online. No vendemos productos: las compras se realizan en la tienda correspondiente, que es la única responsable del pedido, el envío, la garantía y las devoluciones.</p>
    <h2>Precios</h2>
    <p>Los precios se muestran con la fecha y hora en que se comprobaron. Las tiendas los cambian con frecuencia, por lo que el precio válido es siempre el que aparece en la tienda en el momento de comprar.</p>
    <h2>Propiedad intelectual</h2>
    <p>Los textos, gráficas y diseño de esta web pertenecen a su titular salvo que se indique lo contrario. Las marcas y nombres de productos pertenecen a sus respectivos propietarios y se citan solo para identificarlos.</p>
    <h2>Responsabilidad</h2>
    <p>Revisamos la información con cuidado, pero no podemos garantizar que esté libre de errores. Si detectas alguno, escríbenos a <?= e($email) ?> y lo corregiremos.</p>
    <h2>Legislación aplicable</h2>
    <p>Este aviso se rige por la legislación española.</p>

<?php elseif ($slug === 'privacidad'): ?>
    <p>Esta web trata los mínimos datos personales posibles. Aquí te explicamos cuáles y por qué, conforme al Reglamento (UE) 2016/679 (RGPD) y la Ley Orgánica 3/2018 (LOPDGDD).</p>
    <h2>Responsable</h2>
    <p><?= e($owner) ?>, NIF <?= e($nif) ?>, <?= e($address) ?>. Contacto: <?= e($email) ?>.</p>
    <h2>Qué datos tratamos</h2>
    <ul>
      <li><strong>Navegación:</strong> el servidor guarda registros técnicos (dirección IP, fecha, página visitada) para mantener la web segura y funcionando. Se conservan un máximo de 90 días.</li>
      <li><strong>Clics hacia tiendas:</strong> contamos cuántas veces se pulsa cada botón de compra, sin guardar ningún dato que te identifique.</li>
      <li><strong>Correos que nos envíes:</strong> los usamos solo para responderte y los borramos cuando dejan de ser necesarios.</li>
    </ul>
    <p>No usamos herramientas de analítica de terceros ni publicidad personalizada. Las fuentes y librerías de la web se sirven desde nuestro propio servidor, sin conexiones a terceros al cargar la página.</p>
    <h2>Base legal</h2>
    <p>El interés legítimo en mantener la web segura (registros técnicos) y tu consentimiento cuando nos escribes.</p>
    <h2>Tiendas enlazadas</h2>
    <p>Al pulsar un enlace a Amazon u otra tienda, sales de esta web. A partir de ahí se aplica la política de privacidad de esa tienda.</p>
    <h2>Avisos de precio</h2>
    <p>Si creas un aviso de precio, guardamos el producto, el precio que has elegido y tu correo electrónico (o el identificador de tu chat de Telegram, si lo activas en nuestro bot), solo para poder enviarte ese aviso. No usamos esos datos para nada más ni los cedemos a nadie.</p>
    <p>La base legal es tu consentimiento, que das al confirmar el aviso desde el correo que te enviamos o al activarlo en el bot. Puedes retirarlo cuando quieras: cada correo lleva un enlace para borrar tus avisos, y en Telegram basta con escribir /parar al bot. Al borrarlos, tu correo desaparece de nuestra base de datos.</p>
    <p>Los avisos sin confirmar se borran a los 7 días y los ya enviados, a los 30 días. Los correos se envían con el servicio de correo de nuestro proveedor de alojamiento, Hostinger, que actúa como encargado del tratamiento.</p>
    <h2>Productos que nos pides seguir</h2>
    <p>Cuando pegas un enlace de Amazon para que sigamos su precio, guardamos el código del producto. No lo asociamos a ti. Para evitar abusos, guardamos durante 48 horas una huella cifrada de tu dirección IP que no permite identificarte.</p>
    <h2>Tus derechos</h2>
    <p>Puedes pedir acceso, rectificación, supresión, oposición, limitación o portabilidad de tus datos escribiendo a <?= e($email) ?>. Si crees que no hemos atendido bien tu solicitud, puedes reclamar ante la Agencia Española de Protección de Datos (aepd.es).</p>

<?php elseif ($slug === 'cookies'): ?>
    <p><?= e($site) ?> no usa cookies de analítica ni de publicidad, por eso no te mostramos ningún aviso para aceptarlas.</p>
    <h2>Cookies que usamos</h2>
    <div class="table-scroll"><table>
      <thead><tr><th>Cookie</th><th>Para qué sirve</th><th>Duración</th></tr></thead>
      <tbody><tr><td>rp_sess</td><td>Mantener la sesión del panel de administración. Solo se crea si alguien entra en el panel.</td><td>Hasta cerrar el navegador</td></tr></tbody>
    </table></div>
    <p>Es una cookie técnica, necesaria para el funcionamiento del panel, y está exenta de consentimiento según el artículo 22.2 de la LSSI.</p>
    <h2>Cookies de las tiendas</h2>
    <p>Cuando pulsas un enlace y entras en Amazon u otra tienda, esa tienda puede instalar sus propias cookies, entre ellas la que le permite saber que llegaste desde aquí. Puedes consultarlas y gestionarlas en la política de cookies de cada tienda.</p>
    <p>Si en el futuro añadimos analítica o publicidad, pediremos tu consentimiento antes y actualizaremos esta página.</p>

<?php else: ?>
    <p><?= e($site) ?> es gratuita para ti. Nos financiamos con los programas de afiliados de las tiendas que enlazamos: cuando compras después de pulsar uno de nuestros enlaces, la tienda nos paga una pequeña comisión. A ti te cuesta exactamente lo mismo.</p>
    <h2>Programas en los que participamos</h2>
    <ul>
      <li><strong>Amazon:</strong> como afiliado de Amazon, obtengo ingresos por las compras adscritas que cumplen los requisitos aplicables.</li>
    </ul>
    <h2>Lo que la comisión no cambia</h2>
    <ul>
      <li>El historial de precios es el que es. Si un precio es malo, lo decimos, aunque eso suponga que no compres.</li>
      <li>Las notas de las reseñas no dependen de la comisión de la tienda.</li>
      <li>Ninguna marca paga por aparecer ni por salir mejor parada.</li>
    </ul>
    <h2>De dónde salen los precios</h2>
    <p>Los precios de Amazon se obtienen de su servicio oficial para afiliados y se muestran con la fecha y hora de la comprobación. Los de otras tiendas los revisamos nosotros e indicamos el día en que los vimos.</p>
<?php endif; ?>
  </div>
  </article>
</div>
