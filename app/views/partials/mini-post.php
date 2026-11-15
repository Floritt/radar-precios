<?php /** Artículo en la barra lateral. @var array $post */ ?>
<a class="mini-post" href="<?= e(post_url($post)) ?>">
  <?php if ($post['cover_path']): ?><span class="thumb"><img src="<?= e(upload_url($post['cover_path'])) ?>" alt="" loading="lazy"></span>
  <?php else: ?><span class="thumb thumb--ph"><?= placeholder_svg($post['niche_slug'] ?? '') ?></span><?php endif; ?>
  <span>
    <span class="mini-post__title"><?= e($post['title']) ?></span>
    <span class="mini-post__meta"><?= e(POST_TYPES[$post['type']] ?? '') ?><?= $post['type'] === 'review' && $post['rating'] !== null ? ', nota ' . e(number_format((float) $post['rating'], 1, ',', '')) : '' ?></span>
  </span>
</a>
