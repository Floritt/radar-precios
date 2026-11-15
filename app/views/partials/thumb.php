<?php /** Imagen del producto o icono si no tiene. @var array $p */
$img = product_image($p); ?>
<?php if ($img): ?><span class="thumb"><img src="<?= e($img) ?>" alt="" loading="lazy"></span><?php else: ?><span class="thumb thumb--ph"><?= placeholder_svg($p['niche_slug'] ?? '') ?></span><?php endif; ?>
