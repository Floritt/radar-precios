<?php /** @var string $state @var ?array $a @var ?array $zone @var string $code */
$titles = ['confirmed' => 'Aviso activado', 'gone' => 'Este aviso ya no existe', 'ask' => '¿Borrar el aviso?', 'removed' => 'Aviso borrado'];
$fuel = $a ? fuel_key((string) $a['fuel']) : 'g95';
?>
<div class="wrap page narrow">
  <h1><?= e($titles[$state] ?? 'Aviso') ?></h1>
  <?php if ($state === 'confirmed'): ?>
    <p>Te escribiremos cuando <?= e(fuel_zone_rule($fuel, $zone, (string) $a['dir'], (float) $a['step'])) ?>.<?php if ($a['ref_price'] !== null): ?> Hoy está a <?= e(fuel_money($a['ref_price'])) ?> de media.<?php endif; ?></p>
    <p class="fineprint">Revisamos los precios cada hora y te escribimos como mucho una vez al día. Cada correo trae un enlace para borrar el aviso.</p>
    <p><a class="btn btn--blue" href="<?= e(url($zone['path'] . ($fuel === 'g95' ? '' : '?c=' . $fuel))) ?>">Ver las gasolineras <?= e($zone['in']) ?></a></p>
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
  <?php endif; ?>
  <p><a href="<?= e(url('gasolina/avisos')) ?>">Crear otro aviso</a></p>
</div>
