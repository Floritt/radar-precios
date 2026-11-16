<?php
/** @var ?array $result @var ?string $error @var string $value */
$alerts = alerts_enabled();
?>
<section class="hero hero--plain">
  <div class="wrap">
    <h1>Sigue el precio de cualquier producto de Amazon</h1>
    <p class="hero__lead">Pega el enlace del producto y te enseñamos cómo ha cambiado su precio.<?= $alerts ? ' Si quieres, te avisamos ' . e(alert_channels_text()) . ' cuando baje al precio que tú digas.' : '' ?></p>
    <form class="paste" method="post" action="<?= e(url('seguir')) ?>">
      <?= csrf_field() ?>
      <label class="visually-hidden" for="enlace">Enlace de Amazon</label>
      <input id="enlace" name="enlace" type="url" inputmode="url" required value="<?= e($value) ?>" placeholder="Pega aquí el enlace de Amazon" autocomplete="off"<?= $error ? ' aria-invalid="true" aria-describedby="enlace-error"' : '' ?>>
      <button class="btn btn--buy" type="submit">Seguir precio</button>
    </form>
    <?php if ($error): ?><p class="hero__error" id="enlace-error"><?= e($error) ?></p><?php endif; ?>
    <p class="hero__hint">Desde el móvil: en la app de Amazon pulsa Compartir, copia el enlace y pégalo aquí.</p>
  </div>
</section>
<div class="wrap page">
  <?php if ($result && $result['status'] === 'queued'): ?>
    <div class="notice notice--ok">
      <h2>Apuntado</h2>
      <p>Hemos guardado el producto. Empezaremos a seguir su precio en cuanto lo añadamos al catálogo, normalmente en unas horas. Vuelve a pegar el mismo enlace más tarde para ver su ficha.</p>
    </div>
  <?php endif; ?>
  <ol class="steps">
    <li><strong>Pega el enlace</strong><p>De la web o de la app de Amazon.es.</p></li>
    <li><strong>Mira el historial</strong><p>Precio de hoy, media de 90 días y mínimo de los últimos meses.</p></li>
    <?php if ($alerts): ?>
      <li><strong>Pon tu precio</strong><p>Te escribimos <?= e(alert_channels_text()) ?> cuando baje de esa cifra.</p></li>
    <?php else: ?>
      <li><strong>Decide cuándo comprar</strong><p>Si está por debajo de su media, es buen momento. Si no, quizá convenga esperar.</p></li>
    <?php endif; ?>
  </ol>
</div>
