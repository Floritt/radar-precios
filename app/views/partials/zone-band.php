<?php /** Bloque de aviso por zona en las páginas de gasolina. @var array $zones @var string $title @var string $fuel */
if (!mail_enabled()) { return; } ?>
<section class="block" id="aviso" aria-labelledby="h-aviso">
  <div class="zone-band">
    <div class="zone-band__head">
      <h2 id="h-aviso"><?= e($title) ?></h2>
      <p>Un correo cuando la media sube o baja los céntimos que elijas, con las tres gasolineras más baratas de ese momento. Como mucho uno al día.</p>
    </div>
    <?php partial('fuel-zone-form', ['zones' => $zones, 'form' => ['zone' => array_key_first($zones), 'fuel' => $fuel ?? ''], 'idp' => 'zb', 'variant' => 'band']); ?>
  </div>
</section>
