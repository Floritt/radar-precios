<?php /** Foto del producto o, si no tiene, portada con su nombre y el color de la categoría. @var array $p */
$img = product_image($p); ?>
<?php if ($img): ?><span class="thumb"><img src="<?= e($img) ?>" alt="" loading="lazy"></span><?php else: ?><span class="thumb cover cover--s<?= cover_shade((string) $p['slug']) ?>"<?= tint_attr($p) ?>><span class="cover__name"><?= e($p['name']) ?></span><?php if (!empty($p['brand'])): ?><span class="cover__brand"><?= e($p['brand']) ?></span><?php endif; ?><?= placeholder_svg($p['niche_slug'] ?? '') ?></span><?php endif; ?>
