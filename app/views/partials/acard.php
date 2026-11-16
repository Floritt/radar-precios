<?php /** Tarjeta de artículo. Con $label = 'type' muestra el tipo (guía, comparativa) en vez de la categoría. @var array $post */
$isReview = $post['type'] === 'review';
$kind = POST_TYPES[$post['type']] ?? 'Artículo'; ?>
<a class="acard" href="<?= e(post_url($post)) ?>">
  <?php if ($post['cover_path']): ?><span class="acard__img"><img src="<?= e(upload_url($post['cover_path'])) ?>" alt="" loading="lazy"></span><?php endif; ?>
  <span class="acard__body">
    <?php if (($label ?? '') === 'type' || empty($post['niche_name'])): ?><span class="cat"><?= e($kind) ?></span><?php else: ?><?= cat_label($post) ?><?php endif; ?>
    <span class="acard__title"><?= e($post['title']) ?></span>
    <?php if ($post['excerpt']): ?><span class="acard__excerpt"><?= e($post['excerpt']) ?></span><?php endif; ?>
    <?php if ($isReview && $post['rating'] !== null): ?><span class="acard__foot"><span>Nuestra nota</span><span class="score"><?= e(number_format((float) $post['rating'], 1, ',', '')) ?><small>/10</small></span></span><?php endif; ?>
  </span>
</a>
