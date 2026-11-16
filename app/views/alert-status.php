<?php
/** @var string $state confirmed | ask | removed | gone @var ?array $alert */
?>
<div class="wrap page">
  <div class="narrow">
    <?php if ($state === 'confirmed'): ?>
      <h1>Aviso activado</h1>
      <p class="lead-text">Te escribiremos a <strong><?= e($alert['email']) ?></strong> cuando <strong><?= e($alert['name']) ?></strong> baje de <strong><?= e(money((float) $alert['target_price'])) ?></strong>.</p>
      <p><a class="btn btn--blue" href="<?= e(url('producto/' . $alert['slug'])) ?>">Ver la ficha del producto</a></p>
    <?php elseif ($state === 'ask'): ?>
      <h1>Borrar aviso de precio</h1>
      <p class="lead-text">Aviso de <strong><?= e($alert['name']) ?></strong> para <strong><?= e($alert['email']) ?></strong>.</p>
      <form class="notice" method="post">
        <?= csrf_field() ?>
        <p>Elige qué quieres borrar. Tu correo desaparece de nuestra base de datos al momento.</p>
        <p style="display:flex;flex-wrap:wrap;gap:10px;margin:0">
          <button class="btn btn--blue" type="submit">Borrar este aviso</button>
          <button class="btn" type="submit" name="todos" value="1">Borrar todos mis avisos</button>
        </p>
      </form>
    <?php elseif ($state === 'removed'): ?>
      <h1>Aviso borrado</h1>
      <p class="lead-text">Ya no recibirás ese correo. Puedes crear otro aviso cuando quieras desde la ficha de cualquier producto.</p>
      <p><a class="btn" href="<?= e(url('')) ?>">Ir a la portada</a></p>
    <?php else: ?>
      <h1>Este aviso ya no existe</h1>
      <p class="lead-text">Puede que ya se enviara, que lo borraras o que caducara por no confirmarlo a tiempo.</p>
      <p><a class="btn" href="<?= e(url('')) ?>">Ir a la portada</a></p>
    <?php endif; ?>
  </div>
</div>
