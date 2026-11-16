(function () {
  'use strict';

  // Menú en móvil
  var toggle = document.querySelector('.menu-toggle');
  var nav = document.getElementById('mobile-menu');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.textContent = open ? 'Cerrar' : 'Menú';
    });
  }

  // Ordenar listados al cambiar el desplegable
  document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
  });

  // Cuenta los clics hacia las tiendas sin redirigir el enlace
  var base = (function () {
    var brand = document.querySelector('.brand');
    var home = brand ? brand.getAttribute('href') : '/';
    return home.replace(/\/?$/, '/');
  })();
  var endpoint = base + 'api/clic';
  var body = document.body;
  var kind = body.getAttribute('data-kind');
  var ref = body.getAttribute('data-ref') || '0';

  // Cuenta la visita a la página (una vez por carga, sin cookies ni datos personales)
  if (kind && navigator.sendBeacon) {
    var visit = new FormData();
    visit.append('kind', kind);
    visit.append('ref', ref);
    try { visit.append('tz', Intl.DateTimeFormat().resolvedOptions().timeZone || ''); } catch (e) { /* navegador antiguo */ }
    visit.append('lang', navigator.language || '');
    visit.append('w', String(window.screen ? window.screen.width : 0));
    visit.append('src', document.referrer || '');
    try { navigator.sendBeacon(base + 'api/visita', visit); } catch (e) { /* sin conexión: no pasa nada */ }
  }
  document.addEventListener('click', function (ev) {
    var a = ev.target.closest('a[data-out]');
    if (!a || !navigator.sendBeacon) return;
    var data = new FormData();
    data.append('product', a.getAttribute('data-product'));
    data.append('store', a.getAttribute('data-store'));
    data.append('from', a.getAttribute('data-from') || '');
    data.append('label', a.getAttribute('data-label') || '');
    if (kind === 'post') { data.append('post', ref); }
    try { navigator.sendBeacon(endpoint, data); } catch (e) { /* sin conexión: no pasa nada */ }
  });

  // Gasolina: «cerca de mí» pide la posición al navegador y abre la búsqueda. No se guarda en ningún sitio.
  var near = document.querySelector('[data-near]');
  if (near && navigator.geolocation) {
    near.hidden = false;
    var msg = document.querySelector('[data-near-msg]');
    near.addEventListener('click', function () {
      near.disabled = true;
      if (msg) { msg.hidden = false; msg.textContent = 'Buscando tu ubicación…'; }
      navigator.geolocation.getCurrentPosition(function (pos) {
        var lat = pos.coords.latitude.toFixed(4);
        var lng = pos.coords.longitude.toFixed(4);
        window.location.href = base + 'gasolina/cerca?lat=' + lat + '&lng=' + lng + '&c=' + (near.getAttribute('data-fuel') || 'g95');
      }, function () {
        near.disabled = false;
        if (msg) { msg.textContent = 'No hemos podido saber tu ubicación. Busca tu municipio o código postal.'; }
      }, { timeout: 10000, maximumAge: 300000 });
    });
  }

  // Aviso de gasolina: al elegir carburante se propone un precio 3 céntimos por debajo del de hoy.
  var fuelSelect = document.querySelector('[data-fuel-select]');
  var fuelPrice = document.querySelector('[data-fuel-price]');
  if (fuelSelect && fuelPrice) {
    fuelSelect.addEventListener('change', function () {
      var opt = fuelSelect.options[fuelSelect.selectedIndex];
      var suggest = opt ? opt.getAttribute('data-suggest') : '';
      if (suggest && (fuelPrice.value === '' || fuelPrice.dataset.auto === '1')) {
        fuelPrice.value = suggest;
        fuelPrice.dataset.auto = '1';
      }
    });
    fuelPrice.addEventListener('input', function () { fuelPrice.dataset.auto = '0'; });
  }
})();
