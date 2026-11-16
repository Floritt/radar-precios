<?php
/** @var ?array $result @var ?string $error @var string $value */
?>
<div class="wrap page">
  <div class="follow">
    <h1>Sigue el precio de cualquier producto de Amazon</h1>
    <p class="follow__lead">Pega el enlace del producto. Te enseñamos cómo cambia su precio y, si quieres, te avisamos por Telegram cuando baje al precio que tú digas.</p>

    <?php if ($result && $result['status'] === 'queued'): ?>
      <div class="panel panel__body follow__done">
        <h2>Apuntado</h2>
        <p>Hemos guardado el producto. Empezaremos a seguir su precio en cuanto lo añadamos al catálogo, normalmente en unas horas. Vuelve a pegar el mismo enlace más tarde para ver su ficha.</p>
      </div>
    <?php endif; ?>

    <form class="panel panel__body follow__form" method="post" action="<?= e(url('seguir')) ?>">
      <?= csrf_field() ?>
      <label for="enlace">Enlace de Amazon</label>
      <div class="follow__row">
        <input id="enlace" name="enlace" type="url" inputmode="url" required value="<?= e($value) ?>" placeholder="https://www.amazon.es/dp/... o https://amzn.eu/d/..." autocomplete="off"<?= $error ? ' aria-invalid="true" aria-describedby="enlace-error"' : '' ?>>
        <button class="btn btn--buy" type="submit">Seguir precio</button>
      </div>
      <?php if ($error): ?><p class="form-error" id="enlace-error"><?= e($error) ?></p><?php endif; ?>
      <p class="fineprint">Desde el móvil: en la app de Amazon pulsa Compartir, copia el enlace y pégalo aquí.</p>
    </form>

    <div class="follow__steps">
      <div><strong>Pega el enlace</strong><p>De la web o de la app de Amazon.es.</p></div>
      <div><strong>Mira el historial</strong><p>Precio de hoy, media de 90 días y mínimo de los últimos meses.</p></div>
      <div><strong>Pon tu precio</strong><p>Te escribimos por Telegram cuando baje de esa cifra.</p></div>
    </div>
  </div>
</div>
