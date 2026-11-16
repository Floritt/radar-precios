<?php /** Artículo en una lista compacta. @var array $post */ ?>
<a class="story" href="<?= e(post_url($post)) ?>">
  <?= cat_label($post) ?>
  <span class="story__title"><?= e($post['title']) ?></span>
  <?php if (empty($plain)): ?><span class="story__meta"><?= e(POST_TYPES[$post['type']] ?? '') ?><?= $post['type'] === 'review' && $post['rating'] !== null ? ', nota ' . e(number_format((float) $post['rating'], 1, ',', '')) : '' ?></span><?php endif; ?>
  <?php if ($post['cover_path']): ?><span class="story__img"><img src="<?= e(upload_url($post['cover_path'])) ?>" alt="" loading="lazy"></span><?php endif; ?>
</a>
