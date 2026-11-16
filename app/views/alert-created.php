<?php
/** @var array $p @var float $target @var ?string $error @var ?string $startUrl */
?>
<div class="wrap page">
  <div class="follow">
    <?php if ($error): ?>
      <h1>No hemos podido crear el aviso</h1>
      <p class="follow__lead"><?= e($error) ?></p>
      <p><a class="btn" href="<?= e(product_url($p)) ?>">Volver a la ficha</a></p>
    <?php else: ?>
      <h1>Último paso: actívalo en Telegram</h1>
      <p class="follow__lead">Te avisaremos cuando <strong><?= e($p['name']) ?></strong> baje de <strong><?= e(money($target)) ?></strong>.</p>
      <div class="panel panel__body follow__done">
        <p>Pulsa el botón, se abrirá Telegram con nuestro bot. Dale a <strong>Iniciar</strong> y el aviso quedará activado.</p>
        <p><a class="btn btn--buy" href="<?= e($startUrl) ?>" rel="noopener">Activar aviso en Telegram</a></p>
        <p class="fineprint">Si no lo activas en 7 días, se borra. Solo guardamos tu identificador de Telegram y el precio que has elegido, para poder escribirte. Escribe /parar al bot cuando quieras y se borran tus avisos.</p>
      </div>
      <p><a href="<?= e(product_url($p)) ?>">Volver a la ficha del producto</a></p>
    <?php endif; ?>
  </div>
</div>
