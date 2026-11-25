<?php
/**
 * «Mis avisos»: los avisos de gasolina de un correo, con su enlace privado.
 * @var ?array $sub @var string $self @var ?array $flash @var ?string $error @var array $form @var int $editId
 * @var array $zoneAlerts @var array $stationAlerts
 */
?>
<div class="page-head fuel-head">
  <div class="wrap">
    <nav class="crumbs" aria-label="Migas de pan"><a href="<?= e(url('')) ?>">Inicio</a> / <a href="<?= e(url('gasolina')) ?>">Gasolina</a> / Mis avisos</nav>
    <div class="page-head__icon">
      <?= icon('bell') ?>
      <div>
        <h1>Mis avisos de gasolina</h1>
        <?php if ($sub): ?><p>Avisos de <strong><?= e($sub['email']) ?></strong>. Este enlace es privado: no lo compartas.</p><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="wrap page">
<?php if (!$sub): ?>
  <p>Este enlace no es válido. Abre el enlace «Gestionar mis avisos» de cualquiera de nuestros correos, o <a href="<?= e(url('gasolina/avisos')) ?>">crea un aviso nuevo</a>.</p>
<?php else: ?>
  <?php if ($flash): ?><p class="notice notice--ok" role="status"><?= e($flash['message']) ?></p><?php endif; ?>
  <?php if ($error): ?><p class="notice notice--error" role="alert"><?= e($error) ?></p><?php endif; ?>

  <div class="mng">
    <div class="mng__list">
      <section aria-labelledby="h-mng-zone">
        <h2 id="h-mng-zone">Por zona</h2>
        <?php if (!$zoneAlerts): ?><p class="mng__empty">Todavía no tienes avisos por zona.</p><?php endif; ?>
        <?php foreach ($zoneAlerts as $a): $id = (int) $a['id']; $z = $a['zoneInfo']; ?>
        <article class="mng-item">
          <div class="mng-item__head">
            <div>
              <p class="mng-item__title"><?= e(fuel_zone_summary($a, $z)) ?></p>
              <p class="mng-item__meta"><?= $a['sent_at'] ? 'Último correo: ' . e(fuel_day_text((string) $a['sent_at'])) : 'Aún no te hemos escrito' ?></p>
            </div>
            <form method="post" action="<?= e(url($self)) ?>"><?= csrf_field() ?><input type="hidden" name="accion" value="borrar-zona"><input type="hidden" name="id" value="<?= $id ?>"><button class="btn btn--sm mng-item__del" type="submit">Borrar</button></form>
          </div>
          <details class="mng-item__edit"<?= $editId === $id ? ' open' : '' ?>>
            <summary>Cambiar</summary>
            <?php partial('fuel-zone-form', [
                'zones' => fuel_zone_choices(str_starts_with((string) $a['zone'], 'm') ? [(string) $a['zone']] : []),
                'form' => $editId === $id ? $form : fuel_zone_form_from_alert($a),
                'idp' => 'e' . $id, 'variant' => 'card', 'action' => url($self), 'hidden' => ['accion' => 'cambiar', 'id' => $id],
                'askEmail' => false, 'submit' => 'Guardar cambios',
            ]); ?>
          </details>
        </article>
        <?php endforeach; ?>
      </section>

      <section aria-labelledby="h-mng-station">
        <h2 id="h-mng-station">Por gasolinera</h2>
        <?php if (!$stationAlerts): ?><p class="mng__empty">No tienes avisos de gasolineras concretas. Se crean desde la ficha de cada gasolinera.</p><?php endif; ?>
        <?php foreach ($stationAlerts as $a): $id = (int) $a['id']; $f = fuel_key((string) $a['fuel']); $st = ['id' => $a['sid'], 'name' => $a['name'], 'muni_name' => $a['muni_name']]; ?>
        <article class="mng-item">
          <div class="mng-item__head">
            <div>
              <p class="mng-item__title"><?= e(fuel_name($f)) ?> en <a href="<?= e(fuel_station_url($st)) ?>"><?= e($a['name']) ?></a> (<?= e((string) $a['muni_name']) ?>): si baja de <?= e(fuel_money($a['target_price'])) ?></p>
              <p class="mng-item__meta"><?= $a['status'] === 'sent' ? 'Ya te avisamos el ' . e(fuel_day_text((string) $a['sent_at'])) . '. Cambia el precio para volver a activarlo.' : 'Hoy está a ' . e(fuel_money($a[$f])) ?></p>
            </div>
            <form method="post" action="<?= e(url($self)) ?>"><?= csrf_field() ?><input type="hidden" name="accion" value="borrar-gasolinera"><input type="hidden" name="id" value="<?= $id ?>"><button class="btn btn--sm mng-item__del" type="submit">Borrar</button></form>
          </div>
          <form class="mng-item__price" method="post" action="<?= e(url($self)) ?>"><?= csrf_field() ?>
            <input type="hidden" name="accion" value="precio-gasolinera"><input type="hidden" name="id" value="<?= $id ?>">
            <label for="sp-<?= $id ?>">Nuevo precio</label>
            <div class="zone-form__unit"><input class="field" id="sp-<?= $id ?>" name="precio" inputmode="decimal" required value="<?= e(number_format((float) $a['target_price'], 3, ',', '')) ?>"><span>€/l</span></div>
            <button class="btn btn--sm" type="submit">Cambiar precio</button>
          </form>
        </article>
        <?php endforeach; ?>
      </section>
    </div>

    <aside class="mng__add">
      <div class="zone-card">
        <h2 class="zone-card__title">Añadir un aviso</h2>
        <p class="mng__lead">Funciona al momento, sin volver a confirmar el correo.</p>
        <?php partial('fuel-zone-form', ['zones' => fuel_zone_choices(), 'form' => $editId === -1 ? $form : fuel_zone_form_defaults(), 'idp' => 'mn', 'variant' => 'card', 'action' => url($self), 'hidden' => ['accion' => 'nuevo'], 'askEmail' => false]); ?>
      </div>
      <?php if ($zoneAlerts || $stationAlerts): ?>
      <details class="mng__all">
        <summary>Borrar todos mis avisos</summary>
        <p>Dejaremos de enviarte correos de gasolina.</p>
        <form method="post" action="<?= e(url($self)) ?>"><?= csrf_field() ?><input type="hidden" name="accion" value="borrar-todo"><button class="btn mng-item__del" type="submit">Sí, borrar todos</button></form>
      </details>
      <?php endif; ?>
    </aside>
  </div>
<?php endif; ?>
</div>
