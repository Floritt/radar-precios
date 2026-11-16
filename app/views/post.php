<?php
/** @var array $post @var ?array $product @var array $more @var array $section @var bool $isDraft */
$isReview = $post['type'] === 'review';
$pros = lines($post['pros']);
$cons = lines($post['cons']);
$showProduct = $product && $product['status'] === 'published';
?>
<div class="wrap">
  <header class="art-head">
    <nav class="crumbs" aria-label="Migas de pan">
      <a href="<?= e(url('')) ?>">Inicio</a> / <a href="<?= e(url($section[1])) ?>"><?= e($section[0]) ?></a>
      <?php if ($post['niche_name']): ?> / <a href="<?= e(url('nicho/' . $post['niche_slug'])) ?>"><?= e($post['niche_name']) ?></a><?php endif; ?>
    </nav>
    <?php if ($isDraft): ?><p class="draft-note">Borrador: solo lo ves tú porque has entrado en el panel.</p><?php endif; ?>
    <h1><?= e($post['title']) ?></h1>
    <?php if ($post['excerpt']): ?><p class="art__lead"><?= e($post['excerpt']) ?></p><?php endif; ?>
    <p class="byline">
      <?= $post['author'] ? 'Por <strong>' . e($post['author']) . '</strong>. ' : '' ?><?= e(date_es($post['published_at'] ?: $post['created_at'])) ?><?php if ($post['updated_at'] && substr($post['updated_at'], 0, 10) > substr((string) $post['published_at'], 0, 10)): ?>, actualizado el <?= e(date_es($post['updated_at'])) ?><?php endif; ?>. <?= reading_minutes($post['body']) ?> min de lectura.
    </p>
    <p class="affiliate-note">Algunos enlaces de esta página son de afiliado: si compras a través de ellos, cobramos una pequeña comisión y a ti te cuesta lo mismo.</p>
  </header>
  <?php if ($post['cover_path']): ?><figure class="art__cover"><img src="<?= e(upload_url($post['cover_path'])) ?>" alt=""></figure><?php endif; ?>

  <div class="layout art-wrap">
    <article>
      <?php if ($isReview && ($post['rating'] !== null || $pros || $cons || $post['verdict'])): ?>
        <section class="review-box" aria-label="Resumen de la reseña">
          <div class="review-box__top">
            <?php if ($post['rating'] !== null): ?><span class="score score--lg"><?= e(number_format((float) $post['rating'], 1, ',', '')) ?><small>/10</small></span><?php endif; ?>
            <?php if ($post['verdict']): ?><p class="review-box__verdict"><?= e($post['verdict']) ?></p><?php endif; ?>
          </div>
          <?php if ($pros || $cons): ?>
            <div class="proscons">
              <div><h2>A favor</h2><ul class="list-pros"><?php foreach ($pros as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ul></div>
              <div><h2>En contra</h2><ul class="list-cons"><?php foreach ($cons as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ul></div>
            </div>
          <?php endif; ?>
        </section>
      <?php endif; ?>

      <?php
      $bodyHtml = render_markdown($post['body']);
      preg_match_all('~<h2 id="([^"]+)">(.*?)</h2>~s', $bodyHtml, $toc, PREG_SET_ORDER);
      ?>
      <?php if (count($toc) >= 4): ?>
        <nav class="toc" aria-label="En este artículo">
          <p class="toc__title">En este artículo</p>
          <ol><?php foreach ($toc as $t): ?><li><a href="#<?= e($t[1]) ?>"><?= strip_tags($t[2]) ?></a></li><?php endforeach; ?></ol>
        </nav>
      <?php endif; ?>
      <div class="prose"><?= $bodyHtml ?></div>
    </article>

    <aside<?= $showProduct ? '' : ' class="sticky"' ?>>
      <?php if ($showProduct): $offers = product_offers($product); $best = best_offer($offers); $pimg = product_image($product); ?>
        <section class="side-product">
          <?php if ($pimg): ?><div class="side-product__img"><img src="<?= e($pimg) ?>" alt="" loading="lazy"></div><?php endif; ?>
          <p class="side-product__name"><a href="<?= e(product_url($product)) ?>"><?= e($product['name']) ?></a></p>
          <?php if ($best): ?>
            <p class="side-product__price"><?= e(money($best['price'])) ?></p>
            <p class="muted" style="margin:0">Mejor precio, en <?= e($best['name']) ?></p>
            <a class="btn btn--buy" href="<?= e($best['url']) ?>" rel="sponsored nofollow noopener" target="_blank" data-out data-product="<?= (int) $product['id'] ?>" data-store="<?= e($best['store']) ?>" data-from="sidebar">Ver oferta</a>
          <?php endif; ?>
          <a class="btn" href="<?= e(product_url($product)) ?>">Ver historial de precios</a>
        </section>
      <?php endif; ?>
      <?php if ($more): ?>
        <h2 class="aside-title">Sigue leyendo</h2>
        <?php foreach ($more as $m): partial('mini-post', ['post' => $m]); endforeach; ?>
      <?php endif; ?>
    </aside>
  </div>
</div>
