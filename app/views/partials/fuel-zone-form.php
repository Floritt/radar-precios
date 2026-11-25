<?php
/**
 * Formulario de aviso por zona: zona (o código postal), carburante, tipo de aviso con sus datos y el correo.
 * @var array $zones clave => nombre @var array $form @var string $idp @var string $variant card|band
 * Opcionales: $action (a dónde se envía), $hidden (campos ocultos), $askEmail (pedir correo), $submit (texto del botón).
 */
$form = ($form ?? []) + fuel_zone_form_defaults();
$idp = $idp ?? 'za';
$variant = $variant ?? 'card';
$action = $action ?? url('gasolina/avisos');
$hidden = $hidden ?? [];
$askEmail = $askEmail ?? true;
$submit = $submit ?? 'Crear aviso';
$single = count($zones) === 1;
// «Un código postal» va segundo en las listas largas (portada, avisos) y al final en las cortas (provincia, municipio).
if (!$single) {
    $cpOpt = ['cp' => 'Un código postal'];
    $zones = count($zones) > 3 ? array_slice($zones, 0, 1, true) + $cpOpt + array_slice($zones, 1, null, true) : $zones + $cpOpt;
}
$mode = array_key_exists($form['mode'], ZONE_MODES) ? $form['mode'] : 'change';
?>
<form class="zone-form zone-form--<?= e($variant) ?><?= $askEmail ? '' : ' zone-form--noemail' ?>" method="post" action="<?= e($action) ?>">
  <?= csrf_field() ?>
  <?php foreach ($hidden as $k => $v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e((string) $v) ?>"><?php endforeach; ?>
  <div class="zone-form__f zone-form__zone">
    <label for="<?= e($idp) ?>-zone">Zona</label>
    <?php if ($single): $k = array_key_first($zones); ?>
      <input type="hidden" name="zona" value="<?= e($k) ?>">
      <p class="zone-form__fixed" id="<?= e($idp) ?>-zone"><?= e($zones[$k]) ?></p>
    <?php else: ?>
      <select class="field" id="<?= e($idp) ?>-zone" name="zona" required data-zone-select>
        <?php foreach ($zones as $k => $label): ?><option value="<?= e($k) ?>"<?= $k === $form['zone'] ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
      </select>
      <div class="zone-form__cp" data-zone-cp>
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
  <?php if ($askEmail): ?>
  <div class="zone-form__f zone-form__mail">
    <label for="<?= e($idp) ?>-mail">Tu correo</label>
    <input class="field" id="<?= e($idp) ?>-mail" type="email" name="correo" required autocomplete="email" placeholder="tu@correo.es" value="<?= e($form['email']) ?>">
  </div>
  <?php endif; ?>

  <fieldset class="zone-form__f zone-form__mode">
    <legend>Qué aviso quieres</legend>
    <div class="zone-form__seg">
      <?php foreach (ZONE_MODES as $m => $label): ?>
        <label class="zone-form__opt"><input type="radio" name="modo" value="<?= e($m) ?>"<?= $m === $mode ? ' checked' : '' ?> data-mode><?= e($label) ?></label>
      <?php endforeach; ?>
    </div>
  </fieldset>

  <div class="zone-form__panel" data-mode-panel="change">
    <fieldset class="zone-form__f zone-form__dir">
      <legend>Avísame</legend>
      <label class="zone-form__check"><input type="checkbox" name="baja" value="1"<?= $form['down'] ? ' checked' : '' ?>> <?= icon('trend-down') ?> Si baja</label>
      <label class="zone-form__check"><input type="checkbox" name="sube" value="1"<?= $form['up'] ? ' checked' : '' ?>> <?= icon('trend-up') ?> Si sube</label>
    </fieldset>
    <div class="zone-form__f zone-form__step">
      <label for="<?= e($idp) ?>-step">Cambio mínimo de la media</label>
      <select class="field" id="<?= e($idp) ?>-step" name="paso">
        <?php foreach (ZONE_STEPS as $s): $v = number_format($s, 2, '.', ''); ?><option value="<?= e($v) ?>"<?= abs((float) $form['step'] - $s) < 0.001 ? ' selected' : '' ?>><?= e(fuel_step_text($s)) ?></option><?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="zone-form__panel" data-mode-panel="below">
    <div class="zone-form__f zone-form__price">
      <label for="<?= e($idp) ?>-price">Avísame si alguna gasolinera baja de</label>
      <div class="zone-form__unit"><input class="field" id="<?= e($idp) ?>-price" name="precio" inputmode="decimal" placeholder="1,450" value="<?= e($form['target']) ?>"><span>€/l</span></div>
    </div>
    <p class="zone-form__hint">Te escribimos cuando la más barata de la zona llegue a ese precio, con las que están por debajo.</p>
  </div>
  <div class="zone-form__panel" data-mode-panel="weekly">
    <p class="zone-form__hint">Cada lunes por la mañana: la media de la zona, cuánto ha cambiado en la semana y las 3 gasolineras más baratas.</p>
  </div>

  <button class="btn btn--buy zone-form__go" type="submit"><?= icon('bell') ?> <?= e($submit) ?></button>
  <?php if ($askEmail): ?>
  <p class="zone-form__note">Gratis. Solo la primera vez te pedimos confirmar el correo. Como mucho un correo al día, y en cada uno puedes gestionar o borrar tus avisos. <a href="<?= e(url('legal/privacidad')) ?>">Privacidad</a></p>
  <?php endif; ?>
</form>
