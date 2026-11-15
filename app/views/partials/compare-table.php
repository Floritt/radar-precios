<?php
/** Tabla comparativa dentro de un artículo ([comparativa:a,b,c]). @var array $items */
?>
<div class="table-scroll">
  <table class="compare">
    <thead>
      <tr><th scope="col">Producto</th><th scope="col">Mejor precio</th><th scope="col">Frente a su media</th><th scope="col"><span class="visually-hidden">Comprar</span></th></tr>
    </thead>
    <tbody>
      <?php foreach ($items as $p):
        $offers = product_offers($p);
        $best = best_offer($offers);
        $diff = ($best && $p['avg90']) ? $best['price'] / (float) $p['avg90'] - 1 : null; ?>
        <tr>
          <th scope="row"><a href="<?= e(product_url($p)) ?>"><?= e($p['name']) ?></a></th>
          <td class="num"><?= $best ? e(money($best['price'])) . ' <small>en ' . e($best['name']) . '</small>' : '<small>Sin precio ahora</small>' ?></td>
          <td class="num"><?php if ($diff !== null): ?><span class="trend <?= $diff < 0 ? 'trend--down' : 'trend--up' ?>"><?= e(pct($diff)) ?></span><?php else: ?><small>Sin datos</small><?php endif; ?></td>
          <td><?php if ($best): ?><a class="compare__go" href="<?= e($best['url']) ?>" rel="sponsored nofollow noopener" target="_blank" data-out data-product="<?= (int) $p['id'] ?>" data-store="<?= e($best['store']) ?>" data-from="compare">Ver oferta</a><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
