<?php
/**
 * Las gasolineras más baratas, con la península y Baleares por un lado y Canarias, Ceuta y Melilla por otro.
 * @var array $split @var string $fuel @var string $base @var string $hid
 */
$main = $split['main'];
$low = $split['low'];
?>
<?php if ($main['stations']): ?>
  <?php partial('fuel-table', ['stations' => $main['stations'], 'fuel' => $fuel, 'avg' => $main['avg'], 'showMuni' => true, 'base' => $base]); ?>
<?php endif; ?>
<?php if ($low['stations']): ?>
  <h3 class="fuel-low__title" id="<?= e($hid) ?>-low">En Canarias, Ceuta y Melilla</h3>
  <p class="fuel-note">Allí los carburantes pagan menos impuestos, por eso salen aparte.</p>
  <?php partial('fuel-table', ['stations' => $low['stations'], 'fuel' => $fuel, 'avg' => $low['avg'], 'showMuni' => true, 'base' => $base]); ?>
<?php endif; ?>
