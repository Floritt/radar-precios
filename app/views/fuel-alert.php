<?php /** @var string $state @var ?string $error @var ?array $s @var string $fuel @var float $target @var ?string $email @var string $code */
$titles = ['created' => 'Revisa tu correo', 'error' => 'No hemos podido crear el aviso', 'confirmed' => 'Aviso activado', 'gone' => 'Este aviso ya no existe', 'ask' => '¿Borrar el aviso?', 'removed' => 'Aviso borrado'];
?>
<div class="wrap page narrow">
  <h1><?= e($titles[$state] ?? 'Aviso') ?></h1>
  <?php if ($state === 'created'): ?>
    <p>Te hemos enviado un correo a <strong><?= e($email) ?></strong>. Pulsa el enlace que contiene para activar el aviso de <?= e(mb_strtolower(fuel_name($fuel))) ?> en <?= e($s['name']) ?> por debajo de <?= e(fuel_money($target)) ?>.</p>
    <p class="fineprint">Si no lo ves, mira en la carpeta de correo no deseado. Sin confirmar, el aviso se borra a los 7 días.</p>
  <?php elseif ($state === 'error'): ?>
    <p><?= e($error) ?></p>
  <?php elseif ($state === 'confirmed'): ?>
    <p>Te escribiremos cuando la <?= e(mb_strtolower(fuel_name($fuel))) ?> en <?= e($s['name']) ?> (<?= e($s['muni_name']) ?>) baje de <?= e(fuel_money($target)) ?>. Revisamos los precios cada hora.</p>
  <?php elseif ($state === 'gone'): ?>
    <p>Puede que ya se enviara o que se borrara. Puedes crear otro desde la ficha de cualquier gasolinera.</p>
  <?php elseif ($state === 'ask'): ?>
    <form method="post"><?= csrf_field() ?>
      <button class="btn btn--blue" type="submit">Borrar este aviso</button>
      <button class="btn" type="submit" name="todos" value="1">Borrar todos mis avisos de gasolina</button>
    </form>
  <?php elseif ($state === 'removed'): ?>
    <p>Listo. No te enviaremos más correos de ese aviso.</p>
  <?php endif; ?>
  <?php if ($s && in_array($state, ['created', 'error', 'confirmed'], true)): ?><p><a href="<?= e(fuel_station_url($s)) ?>">Volver a la gasolinera</a></p><?php endif; ?>
  <p><a href="<?= e(url('gasolina')) ?>">Ir a Gasolina</a></p>
</div>
