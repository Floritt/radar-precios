<?php
/** @var array $p @var float $target @var ?string $error @var ?string $startUrl @var ?string $email @var bool $needsConfirm */
?>
<div class="wrap page">
  <div class="narrow">
    <?php if ($error): ?>
      <h1>No hemos podido crear el aviso</h1>
      <p class="lead-text"><?= e($error) ?></p>
      <p><a class="btn" href="<?= e(product_url($p)) ?>">Volver a la ficha</a></p>
    <?php elseif ($email && $needsConfirm): ?>
      <h1>Último paso: confirma tu correo</h1>
      <p class="lead-text">Te hemos enviado un correo a <strong><?= e($email) ?></strong>. Pulsa el botón que lleva dentro y el aviso quedará activado.</p>
      <div class="notice">
        <p>Te avisaremos cuando <strong><?= e($p['name']) ?></strong> baje de <strong><?= e(money($target)) ?></strong>.</p>
        <p class="fineprint">Si no lo ves en unos minutos, mira en la carpeta de correo no deseado. Si no lo confirmas en 7 días, se borra.</p>
      </div>
      <p><a href="<?= e(product_url($p)) ?>">Volver a la ficha del producto</a></p>
    <?php elseif ($email): ?>
      <h1>Aviso actualizado</h1>
      <p class="lead-text">Te avisaremos por correo cuando <strong><?= e($p['name']) ?></strong> baje de <strong><?= e(money($target)) ?></strong>.</p>
      <p><a href="<?= e(product_url($p)) ?>">Volver a la ficha del producto</a></p>
    <?php else: ?>
      <h1>Último paso: actívalo en Telegram</h1>
      <p class="lead-text">Te avisaremos cuando <strong><?= e($p['name']) ?></strong> baje de <strong><?= e(money($target)) ?></strong>.</p>
      <div class="notice">
        <p>Pulsa el botón y se abrirá Telegram con nuestro bot. Dale a <strong>Iniciar</strong> y el aviso quedará activado.</p>
        <p><a class="btn btn--buy" href="<?= e($startUrl) ?>" rel="noopener">Activar aviso en Telegram</a></p>
        <p class="fineprint">Si no lo activas en 7 días, se borra. Solo guardamos tu identificador de Telegram y el precio que has elegido, para poder escribirte. Escribe /parar al bot cuando quieras y se borran tus avisos.</p>
      </div>
      <p><a href="<?= e(product_url($p)) ?>">Volver a la ficha del producto</a></p>
    <?php endif; ?>
  </div>
</div>
