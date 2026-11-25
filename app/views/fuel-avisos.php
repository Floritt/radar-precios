<?php
/** @var string $state @var ?string $error @var array $form @var array $zones @var ?array $zone @var bool $mailOn */
?>
<div class="page-head fuel-head">
  <div class="wrap">
    <nav class="crumbs" aria-label="Migas de pan"><a href="<?= e(url('')) ?>">Inicio</a> / <a href="<?= e(url('gasolina')) ?>">Gasolina</a> / Avisos</nav>
    <div class="page-head__icon">
      <?= icon('bell') ?>
      <div>
        <h1>Avisos de precio de la gasolina</h1>
        <p>Te escribimos cuando la gasolina baje o suba en tu código postal, tu provincia o toda España, cuando alguna gasolinera baje de tu precio o cada lunes con un resumen. Gratis.</p>
      </div>
    </div>
  </div>
</div>

<div class="wrap page">
  <div class="zone-page">
    <div class="zone-page__main">
      <?php if ($state === 'created'): ?>
        <div class="notice notice--ok" role="status">
          <h2>Revisa tu correo</h2>
          <p>Te hemos enviado un mensaje a <strong><?= e($form['email']) ?></strong>. Pulsa el enlace que contiene para activar el aviso <?= e(fuel_of($form['fuel'])) ?> <?= e($zone['in']) ?>.</p>
          <p class="fineprint">Si no lo ves, mira en la carpeta de correo no deseado. Sin confirmar, el aviso se borra a los 7 días.</p>
          <p><a href="<?= e(url($zone['path'])) ?>">Ver las gasolineras <?= e($zone['in']) ?></a></p>
        </div>
      <?php elseif ($state === 'active'): ?>
        <div class="notice notice--ok" role="status">
          <h2>Aviso activado</h2>
          <p>Ese correo ya estaba confirmado, así que el aviso funciona desde ya: te escribiremos <?= e(fuel_zone_rule(['fuel' => $form['fuel'], 'mode' => $form['mode'], 'dir' => $form['mode'] !== 'change' ? 'both' : ($form['down'] && $form['up'] ? 'both' : ($form['down'] ? 'down' : 'up')), 'step' => (float) $form['step'], 'target' => str_replace(',', '.', $form['target'])], $zone)) ?>.</p>
          <p class="fineprint">Te hemos enviado un correo con el enlace para gestionar tus avisos.</p>
          <p><a href="<?= e(url($zone['path'])) ?>">Ver las gasolineras <?= e($zone['in']) ?></a></p>
        </div>
      <?php elseif (!$mailOn): ?>
        <p class="notice">Los avisos por correo no están disponibles ahora mismo. Vuelve a intentarlo más tarde.</p>
      <?php else: ?>
        <?php if ($error): ?><p class="notice notice--error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <div class="zone-card">
          <h2 class="zone-card__title">Crea tu aviso</h2>
          <?php partial('fuel-zone-form', ['zones' => $zones, 'form' => $form, 'idp' => 'zp', 'variant' => 'card']); ?>
        </div>
      <?php endif; ?>
    </div>

    <aside class="zone-page__side">
      <h2>Cómo funciona</h2>
      <ol class="zone-steps">
        <li><strong>Eliges zona y carburante.</strong> Toda España, una provincia, tu municipio o tu código postal.</li>
        <li><strong>Miramos los precios cada hora.</strong> Con los datos oficiales que las gasolineras envían al Ministerio.</li>
        <li><strong>Eliges el tipo de aviso:</strong> cuando la media baje o suba los céntimos que digas, cuando alguna gasolinera baje de tu precio o un resumen cada lunes. Siempre con las más baratas de ese momento y como mucho un correo al día.</li>
      </ol>
      <h2>¿Hay que confirmar?</h2>
      <p>Solo la primera vez, para comprobar que el correo es tuyo. Después, cada correo trae el enlace «Gestionar mis avisos» para cambiarlos, borrarlos o añadir otros sin confirmar de nuevo.</p>
      <h2>¿Para tu municipio?</h2>
      <p>Busca tu municipio y crea el aviso desde su página. Allí también puedes crear un aviso para una gasolinera concreta, con el precio que quieras.</p>
      <?php partial('fuel-tools', ['fuel' => 'g95']); ?>
    </aside>
  </div>
</div>
