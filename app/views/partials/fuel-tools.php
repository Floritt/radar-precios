<?php /** Buscador por municipio o código postal y botón «cerca de mí». @var string $fuel */ $fuel = $fuel ?? 'g95'; ?>
<div class="fuel-tools">
  <form class="fuel-search" method="get" action="<?= e(url('gasolina/buscar')) ?>" role="search">
    <label class="visually-hidden" for="fuel-q">Municipio o código postal</label>
    <input id="fuel-q" class="field" type="search" name="q" placeholder="Municipio o código postal" autocomplete="off" required minlength="2">
    <button class="btn btn--blue" type="submit"><?= icon('magnifying-glass') ?> Buscar</button>
  </form>
  <button class="btn fuel-near-btn" type="button" data-near data-fuel="<?= e($fuel) ?>" hidden><?= icon('gas-pump') ?> Cerca de mí</button>
  <p class="fuel-near-msg" data-near-msg hidden></p>
</div>
