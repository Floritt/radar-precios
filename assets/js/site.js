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
})();
