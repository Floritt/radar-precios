<?php /** Bloque de aviso por zona en las páginas de gasolina. @var array $zones @var string $title @var string $fuel */
if (!mail_enabled()) { return; } ?>
<section class="block" id="aviso" aria-labelledby="h-aviso">
  <div class="zone-band">
    <div class="zone-band__head">
      <h2 id="h-aviso"><?= e($title) ?></h2>
      <p>Cuando la media sube o baja, cuando alguna gasolinera baja de tu precio o un resumen cada lunes. Siempre con las más baratas de ese momento y como mucho un correo al día.</p>
    </div>
    <?php partial('fuel-zone-form', ['zones' => $zones, 'form' => ['zone' => array_key_first($zones), 'fuel' => $fuel ?? ''], 'idp' => 'zb', 'variant' => 'band']); ?>
  </div>
</section>
