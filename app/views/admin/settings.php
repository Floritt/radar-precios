<?php /** @var array $values */ ?>
<h1>Ajustes</h1>
<form method="post" class="form">
  <?= csrf_field() ?>
  <section class="card">
    <h2>La web</h2>
    <?php foreach (['site_name', 'tagline', 'home_lead', 'default_author'] as $k): ?>
      <label><?= e(SETTING_FIELDS[$k]) ?>
        <?php if ($k === 'home_lead' || $k === 'tagline'): ?>
          <textarea name="<?= e($k) ?>" rows="2"><?= e($values[$k]) ?></textarea>
        <?php else: ?>
          <input name="<?= e($k) ?>" value="<?= e($values[$k]) ?>">
        <?php endif; ?>
      </label>
    <?php endforeach; ?>
  </section>
  <section class="card">
    <h2>Afiliados</h2>
    <label><?= e(SETTING_FIELDS['amazon_tag']) ?> <small>Lo ves arriba a la izquierda en afiliados.amazon.es. En España acaba en -21. Se añade solo a todos los enlaces de Amazon.</small><input name="amazon_tag" value="<?= e($values['amazon_tag']) ?>" placeholder="tunombre-21" autocomplete="off"></label>
    <p class="muted">Los enlaces de Temu, Shein y otras tiendas se pegan ya con tu código de afiliado en cada producto.</p>
  </section>
  <section class="card">
    <h2>Bot de Telegram: avisos de precio</h2>
    <p class="muted">Con el bot, los visitantes pueden pedir que les avises cuando un producto baje de un precio. Crea el bot hablando con <strong>@BotFather</strong> en Telegram (comando /newbot) y pega aquí el token que te da.</p>
    <label>Token del bot <small><?= telegram_bot_token() !== '' ? 'Ya hay un token guardado. Déjalo vacío para mantenerlo.' : 'Todavía no hay ningún token guardado.' ?></small><input type="password" name="telegram_bot_token" autocomplete="off" placeholder="<?= telegram_bot_token() !== '' ? '••••••••' : '123456789:AA...' ?>"></label>
    <p><?= telegram_bot_username() !== '' ? 'Bot conectado: <strong>@' . e(telegram_bot_username()) . '</strong>' : 'Bot sin conectar.' ?></p>
    <label><?= e(SETTING_FIELDS['telegram_channel']) ?> <small>Opcional. El @nombre de tu canal, con el bot añadido como administrador.</small><input name="telegram_channel" value="<?= e($values['telegram_channel']) ?>" placeholder="@tucanal"></label>
  </section>
  <section class="card">
    <h2>Canales</h2>
    <p class="muted">Si los rellenas, aparecen en la portada y en el pie.</p>
    <?php foreach (['telegram_url', 'whatsapp_url'] as $k): ?>
      <label><?= e(SETTING_FIELDS[$k]) ?><input type="url" name="<?= e($k) ?>" value="<?= e($values[$k]) ?>" placeholder="https://"></label>
    <?php endforeach; ?>
  </section>
  <section class="card">
    <h2>Datos legales</h2>
    <p class="muted">Se usan en el aviso legal y la política de privacidad. La LSSI obliga a mostrarlos.</p>
    <?php foreach (['legal_owner', 'legal_nif', 'legal_address', 'legal_email'] as $k): ?>
      <label><?= e(SETTING_FIELDS[$k]) ?><input name="<?= e($k) ?>" value="<?= e($values[$k]) ?>"></label>
    <?php endforeach; ?>
  </section>
  <div class="actions"><button class="btn btn--primary" type="submit">Guardar ajustes</button></div>
</form>
<?php if (telegram_bot_token() !== ''): ?>
<section class="card">
  <h2>Conectar el bot</h2>
  <p>Pulsa después de guardar un token nuevo. Le dice a Telegram que envíe a esta web los mensajes del bot.</p>
  <form method="post" action="<?= e(url('admin/telegram/conectar')) ?>"><?= csrf_field() ?><button class="btn" type="submit">Conectar el bot</button></form>
</section>
<?php endif; ?>
<section class="card">
  <h2>Claves y conexiones</h2>
  <p>Las claves de Amazon, Telegram y la base de datos están en el archivo <code>config.php</code> del servidor, no aquí, para que nunca se vean desde el navegador. El README explica cada una.</p>
</section>
