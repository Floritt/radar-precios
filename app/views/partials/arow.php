<?php /** Artículo en lista compacta. @var array $post */ ?>
<a class="arow" href="<?= e(post_url($post)) ?>"<?= tint_attr($post) ?>>
  <?php if ($post['cover_path']): ?><span class="arow__img"><img src="<?= e(upload_url($post['cover_path'])) ?>" alt="" loading="lazy"></span>
  <?php else: ?><?= article_cover($post, 'arow__img') ?><?php endif; ?>
  <span>
    <span class="kicker"><?= e(!empty($post['niche_name']) ? $post['niche_name'] : (POST_TYPES[$post['type']] ?? 'Artículo')) ?></span>
    <span class="arow__title"><?= e($post['title']) ?></span>
    <?php if ($post['excerpt']): ?><span class="arow__excerpt"><?= e($post['excerpt']) ?></span><?php endif; ?>
  </span>
</a>
