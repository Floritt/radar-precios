<?php /** @var string $state @var ?array $a @var ?array $zone @var string $code @var ?string $manage */
$titles = ['confirmed' => 'Aviso activado', 'gone' => 'Este aviso ya no existe', 'ask' => '¿Borrar el aviso?', 'removed' => 'Aviso borrado'];
$fuel = $a ? fuel_key((string) $a['fuel']) : 'g95';
?>
<div class="wrap page narrow">
  <h1><?= e($titles[$state] ?? 'Aviso') ?></h1>
  <?php if ($state === 'confirmed'): ?>
    <p>Te escribiremos <?= e(fuel_zone_rule($a, $zone)) ?>.<?php if ($a['ref_price'] !== null): ?> Hoy la media está a <?= e(fuel_money($a['ref_price'])) ?>.<?php endif; ?></p>
    <p class="btn-row"><a class="btn btn--blue" href="<?= e(url($zone['path'] . ($fuel === 'g95' ? '' : '?c=' . $fuel))) ?>">Ver las gasolineras <?= e($zone['in']) ?></a><?php if (!empty($manage)): ?> <a class="btn" href="<?= e($manage) ?>">Gestionar mis avisos</a><?php endif; ?></p>
    <p class="fineprint">En «Gestionar mis avisos» puedes cambiar o borrar tus avisos y añadir otros sin volver a confirmar. El mismo enlace va al pie de cada correo.</p>
  <?php elseif ($state === 'gone'): ?>
    <p>Puede que se borrara o que no llegaras a confirmarlo en 7 días. Puedes crear otro cuando quieras.</p>
  <?php elseif ($state === 'ask'): ?>
    <p>Aviso <?= e(fuel_of($fuel)) ?> <?= e($zone['in'] ?? '') ?>.</p>
    <form method="post" class="btn-row"><?= csrf_field() ?>
      <button class="btn btn--blue" type="submit">Borrar este aviso</button>
      <button class="btn" type="submit" name="todos" value="1">Borrar todos mis avisos de gasolina</button>
    </form>
  <?php elseif ($state === 'removed'): ?>
    <p>Listo. No te enviaremos más correos de ese aviso.</p>
    <?php if (!empty($manage)): ?><p><a href="<?= e($manage) ?>">Ver el resto de mis avisos</a></p><?php endif; ?>
  <?php endif; ?>
  <p><a href="<?= e(url('gasolina/avisos')) ?>">Crear otro aviso</a></p>
</div>
