<?php
/**
 * Formulario de aviso por zona: zona, carburante, si avisar al bajar, al subir o en los dos casos, cuántos
 * céntimos y el correo. Se envía siempre a /gasolina/avisos.
 * @var array $zones clave => nombre @var array $form @var string $idp @var string $variant card|band
 */
$form = ($form ?? []) + ['zone' => 'es', 'cp' => '', 'fuel' => '', 'down' => true, 'up' => true, 'step' => '0.02', 'email' => ''];
$idp = $idp ?? 'za';
$variant = $variant ?? 'card';
$single = count($zones) === 1;
// «Un código postal» va segundo en las listas largas (portada, avisos) y al final en las cortas (provincia, municipio).
if (!$single) {
    $cpOpt = ['cp' => 'Un código postal'];
    $zones = count($zones) > 3 ? array_slice($zones, 0, 1, true) + $cpOpt + array_slice($zones, 1, null, true) : $zones + $cpOpt;
}
$isCp = $form['zone'] === 'cp';
?>
<form class="zone-form zone-form--<?= e($variant) ?>" method="post" action="<?= e(url('gasolina/avisos')) ?>">
  <?= csrf_field() ?>
  <div class="zone-form__f zone-form__zone">
    <label for="<?= e($idp) ?>-zone">Zona</label>
    <?php if ($single): $k = array_key_first($zones); ?>
      <input type="hidden" name="zona" value="<?= e($k) ?>">
      <p class="zone-form__fixed" id="<?= e($idp) ?>-zone"><?= e($zones[$k]) ?></p>
    <?php else: ?>
      <select class="field" id="<?= e($idp) ?>-zone" name="zona" required data-zone-select>
        <?php foreach ($zones as $k => $label): ?><option value="<?= e($k) ?>"<?= $k === $form['zone'] ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
      </select>
      <div class="zone-form__cp" data-zone-cp<?= $isCp ? '' : ' data-start-hidden' ?>>
        <label for="<?= e($idp) ?>-cp">Código postal</label>
        <input class="field" id="<?= e($idp) ?>-cp" name="cp" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" autocomplete="postal-code" placeholder="08201" value="<?= e($form['cp']) ?>">
      </div>
    <?php endif; ?>
  </div>
  <div class="zone-form__f zone-form__fuel">
    <label for="<?= e($idp) ?>-fuel">Carburante</label>
    <select class="field" id="<?= e($idp) ?>-fuel" name="c" required>
      <option value=""<?= $form['fuel'] === '' ? ' selected' : '' ?> disabled>Elige</option>
      <?php foreach (FUELS_MAIN as $f): ?><option value="<?= e($f) ?>"<?= $f === $form['fuel'] ? ' selected' : '' ?>><?= e(fuel_name($f)) ?></option><?php endforeach; ?>
    </select>
  </div>
  <fieldset class="zone-form__f zone-form__dir">
    <legend>Avísame</legend>
    <label class="zone-form__check"><input type="checkbox" name="baja" value="1"<?= $form['down'] ? ' checked' : '' ?>> <?= icon('trend-down') ?> Si baja</label>
    <label class="zone-form__check"><input type="checkbox" name="sube" value="1"<?= $form['up'] ? ' checked' : '' ?>> <?= icon('trend-up') ?> Si sube</label>
  </fieldset>
  <div class="zone-form__f zone-form__step">
    <label for="<?= e($idp) ?>-step">Cambio mínimo</label>
    <select class="field" id="<?= e($idp) ?>-step" name="paso">
      <?php foreach (ZONE_STEPS as $s): $v = number_format($s, 2, '.', ''); ?><option value="<?= e($v) ?>"<?= abs((float) $form['step'] - $s) < 0.001 ? ' selected' : '' ?>><?= (int) round($s * 100) ?> <?= $s < 0.015 ? 'céntimo' : 'céntimos' ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="zone-form__f zone-form__mail">
    <label for="<?= e($idp) ?>-mail">Tu correo</label>
    <input class="field" id="<?= e($idp) ?>-mail" type="email" name="correo" required autocomplete="email" placeholder="tu@correo.es" value="<?= e($form['email']) ?>">
  </div>
  <button class="btn btn--buy zone-form__go" type="submit"><?= icon('bell') ?> Crear aviso</button>
  <p class="zone-form__note">Gratis. Te pedimos confirmarlo por correo, te escribimos como mucho una vez al día y lo borras con un clic. <a href="<?= e(url('legal/privacidad')) ?>">Privacidad</a></p>
</form>
