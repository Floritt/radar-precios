(function () {
  'use strict';

  function slugify(text) {
    return text.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
      .replace(/ñ/g, 'n').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  }

  // Dirección web automática a partir del nombre o título, mientras no se edite a mano
  var source = document.querySelector('[data-slug-source]');
  var target = document.querySelector('[data-slug-target]');
  if (source && target) {
    var touched = target.value !== '';
    target.addEventListener('input', function () { touched = target.value !== ''; });
    source.addEventListener('input', function () { if (!touched) target.value = slugify(source.value); });
  }

  // Comprueba el enlace de Amazon mientras se escribe
  var asin = document.querySelector('[data-asin]');
  var hint = document.querySelector('[data-asin-hint]');
  if (asin && hint) {
    var check = function () {
      var v = asin.value.trim();
      if (!v) { hint.textContent = ''; return; }
      var m = v.match(/^[A-Z0-9]{10}$/i) || v.match(/\/(?:dp|gp\/product|gp\/aw\/d|product)\/([A-Z0-9]{10})/i);
      var code = m ? (m[1] || m[0]).toUpperCase() : null;
      hint.classList.toggle('is-bad', !code);
      hint.textContent = code ? 'ASIN detectado: ' + code : 'No encuentro un ASIN en este enlace. Abre la ficha del producto en Amazon.es y copia su dirección.';
    };
    asin.addEventListener('input', check);
    check();
  }

  // Muestra los campos de reseña solo cuando el tipo es reseña
  var typeSelect = document.querySelector('[data-type-select]');
  var reviewBlock = document.querySelector('[data-review-only]');
  if (typeSelect && reviewBlock) {
    typeSelect.addEventListener('change', function () { reviewBlock.hidden = typeSelect.value !== 'review'; });
  }

  // Confirmación antes de borrar
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (ev) {
      if (!window.confirm(f.getAttribute('data-confirm'))) ev.preventDefault();
    });
  });

  document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
  });
})();
