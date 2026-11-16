<?php /** Pestañas de carburante. @var string $fuel @var string $base */ ?>
<nav class="fuel-tabs" aria-label="Carburante">
  <?php foreach (FUELS_MAIN as $f): ?>
    <a href="<?= e($base . ($f === 'g95' ? '' : (str_contains($base, '?') ? '&' : '?') . 'c=' . $f)) ?>"<?= $f === $fuel ? ' aria-current="true"' : '' ?>><?= e(fuel_name($f)) ?></a>
  <?php endforeach; ?>
</nav>
