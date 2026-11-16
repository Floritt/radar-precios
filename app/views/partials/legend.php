<?php /** Leyenda de los colores del veredicto de precio. @var string $class */ ?>
<div class="legend <?= e($class ?? '') ?>">
  <h2 class="aside-title">Cómo leer el precio</h2>
  <ul>
    <li><i class="is-low"></i><span>Amarillo: está en su precio más bajo de los últimos 6 meses.</span></li>
    <li><i class="is-good"></i><span>Verde: está por debajo de su media de 90 días.</span></li>
    <li><i></i><span>Gris: está en su precio habitual.</span></li>
    <li><i class="is-high"></i><span>Rojo: está por encima de su media. Mejor esperar.</span></li>
  </ul>
</div>
