<div class="wrap page">
  <div class="panel panel__body">
    <h1>No encontramos esta página</h1>
    <p>Puede que el producto se haya retirado o que el enlace esté mal escrito.</p>
    <form class="search-page" action="<?= e(url('buscar')) ?>" method="get" role="search">
      <label class="visually-hidden" for="nq">Buscar</label>
      <input id="nq" name="q" type="search" placeholder="Buscar productos">
      <button type="submit">Buscar</button>
    </form>
    <p style="margin-top:14px"><a href="<?= e(url('')) ?>">Ir a la portada</a></p>
  </div>
</div>
