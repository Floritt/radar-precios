<?php /** Accesorios de coche al pie de las páginas de gasolina. */
$items = car_products(4);
$n = car_niche();
if ($items && $n): ?>
<section class="block car-strip" aria-labelledby="h-car-strip">
  <div class="sec__head">
    <h2 id="h-car-strip">Para tu coche</h2>
    <a href="<?= e(niche_url($n)) ?>">Ver todos los accesorios</a>
  </div>
  <div class="car-grid car-grid--strip"><?php foreach ($items as $p): partial('car-card', ['p' => $p]); endforeach; ?></div>
</section>
<?php endif; ?>
