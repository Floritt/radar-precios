(function () {
  'use strict';

  var dataEl = document.getElementById('price-data');
  var canvas = document.getElementById('price-chart');
  if (!dataEl || !canvas || !window.Chart) return;

  var payload = JSON.parse(dataEl.textContent);
  var styles = getComputedStyle(document.documentElement);
  var color = function (name) { return styles.getPropertyValue('--' + name).trim() || '#10162F'; };
  var storeColors = { amazon: color('amazon'), temu: color('temu'), shein: color('shein'), other: color('other') };
  var months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
  var euro = new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' });
  var euro0 = new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 });

  var label = function (iso) {
    var p = iso.split('-');
    return parseInt(p[2], 10) + ' ' + months[parseInt(p[1], 10) - 1];
  };

  Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
  Chart.defaults.font.size = 13;
  Chart.defaults.color = color('gris');

  // Posición del precio más bajo de la serie visible (la última vez que se dio)
  function lowest(values) {
    var idx = -1;
    values.forEach(function (v, i) { if (v != null && (idx < 0 || v <= values[idx])) idx = i; });
    return idx;
  }

  var chart = null;
  function draw(days) {
    var n = payload.labels.length;
    var from = Math.max(0, n - days);
    var alone = payload.series.length === 1;
    var datasets = payload.series.map(function (s, i) {
      var c = storeColors[s.store] || color('tinta');
      return {
        label: s.name,
        data: s.values.slice(from),
        borderColor: c,
        backgroundColor: alone ? 'rgba(31, 63, 216, 0.08)' : c,
        fill: alone,
        borderWidth: i === 0 ? 3 : 2,
        pointRadius: 0,
        pointHoverRadius: 4,
        pointBackgroundColor: c,
        stepped: 'before',
        spanGaps: false
      };
    });
    if (datasets.length) {
      var main = datasets[0].data;
      var at = lowest(main);
      if (at >= 0) {
        datasets.push({
          label: 'Precio más bajo',
          data: main.map(function (v, i) { return i === at ? v : null; }),
          showLine: false,
          pointRadius: 7,
          pointHoverRadius: 8,
          pointBackgroundColor: color('amarillo'),
          pointBorderColor: color('tinta'),
          pointBorderWidth: 2,
          isMarker: true
        });
      }
    }
    var config = {
      type: 'line',
      data: { labels: payload.labels.slice(from).map(label), datasets: datasets },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 200 },
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: {
            display: payload.series.length > 1,
            position: 'bottom',
            labels: { usePointStyle: true, boxWidth: 8, boxHeight: 8, filter: function (item, data) { return !data.datasets[item.datasetIndex].isMarker; } }
          },
          tooltip: {
            backgroundColor: color('tinta'),
            padding: 10,
            cornerRadius: 8,
            filter: function (ctx) { return !ctx.dataset.isMarker; },
            callbacks: { label: function (ctx) { return ctx.parsed.y == null ? null : ctx.dataset.label + ': ' + euro.format(ctx.parsed.y); } }
          }
        },
        scales: {
          x: { grid: { display: false }, border: { color: color('linea') }, ticks: { maxTicksLimit: 6, maxRotation: 0 } },
          y: { grid: { color: color('linea') }, border: { display: false }, ticks: { callback: function (v) { return euro0.format(v); } } }
        }
      }
    };
    if (chart) chart.destroy();
    chart = new Chart(canvas, config);
  }

  var buttons = document.querySelectorAll('[data-range]');
  buttons.forEach(function (b) {
    b.addEventListener('click', function () {
      buttons.forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
      draw(parseInt(b.getAttribute('data-range'), 10));
    });
  });
  draw(90);
})();
