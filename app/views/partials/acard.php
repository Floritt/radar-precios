<?php /** Tarjeta de artículo (revista). @var array $post */
$isReview = $post['type'] === 'review'; ?>
<a class="acard" href="<?= e(post_url($post)) ?>">
  <?php if ($post['cover_path']): ?>
    <span class="acard__img"><img src="<?= e(upload_url($post['cover_path'])) ?>" alt="" loading="lazy"></span>
  <?php else: ?>
    <span class="acard__img thumb--ph"<?= tint_attr($post) ?>><?= placeholder_svg($post['niche_slug'] ?? '') ?></span>
  <?php endif; ?>
  <span class="acard__body">
    <span class="acard__kicker"><?= e(POST_TYPES[$post['type']] ?? 'Artículo') ?><?= !empty($post['niche_name']) ? ' en ' . e($post['niche_name']) : '' ?></span>
    <span class="acard__title"><?= e($post['title']) ?></span>
    <?php if ($post['excerpt']): ?><span class="acard__excerpt"><?= e(excerpt($post['excerpt'], 140)) ?></span><?php endif; ?>
    <span class="acard__foot">
      <span class="byline"><?= e(date_es($post['published_at'] ?: $post['created_at'])) ?></span>
      <?php if ($isReview && $post['rating'] !== null): ?><span class="score"><?= e(number_format((float) $post['rating'], 1, ',', '')) ?><small>/10</small></span><?php endif; ?>
    </span>
  </span>
</a>
