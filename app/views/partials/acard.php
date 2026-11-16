<?php /** Tarjeta de artículo. @var array $post */
$isReview = $post['type'] === 'review'; ?>
<a class="acard" href="<?= e(post_url($post)) ?>"<?= tint_attr($post) ?>>
  <?php if ($post['cover_path']): ?>
    <span class="acard__img"><img src="<?= e(upload_url($post['cover_path'])) ?>" alt="" loading="lazy"></span>
  <?php else: ?>
    <?= article_cover($post, 'acard__img') ?>
  <?php endif; ?>
  <span class="acard__body">
    <span class="kicker"><?= e(POST_TYPES[$post['type']] ?? 'Artículo') ?><?= !empty($post['niche_name']) ? ' de ' . e(mb_strtolower(mb_substr($post['niche_name'], 0, 1)) . mb_substr($post['niche_name'], 1)) : '' ?></span>
    <span class="acard__title"><?= e($post['title']) ?></span>
    <?php if ($post['excerpt']): ?><span class="acard__excerpt"><?= e(excerpt($post['excerpt'], 150)) ?></span><?php endif; ?>
    <span class="acard__foot">
      <span class="byline"><?= e(date_es($post['published_at'] ?: $post['created_at'])) ?></span>
      <?php if ($isReview && $post['rating'] !== null): ?><span class="score"><?= e(number_format((float) $post['rating'], 1, ',', '')) ?><small>/10</small></span><?php endif; ?>
    </span>
  </span>
</a>
