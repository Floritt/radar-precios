(function () {
  'use strict';

  var dataEl = document.getElementById('price-data');
  var canvas = document.getElementById('price-chart');
  if (!dataEl || !canvas || !window.Chart) return;

  var payload = JSON.parse(dataEl.textContent);
  var styles = getComputedStyle(document.documentElement);
  var color = function (name) { return styles.getPropertyValue('--' + name).trim() || '#1D2129'; };
  var storeColors = { amazon: color('amazon'), temu: color('temu'), shein: color('shein'), other: color('other') };
  var months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
  var euro = new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' });
  var euro0 = new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 });

  var label = function (iso) {
    var p = iso.split('-');
    return parseInt(p[2], 10) + ' ' + months[parseInt(p[1], 10) - 1];
  };

  Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
  Chart.defaults.color = color('muted');

  var chart = null;
  function draw(days) {
    var n = payload.labels.length;
    var from = Math.max(0, n - days);
    var datasets = payload.series.map(function (s) {
      var c = storeColors[s.store] || color('text');
      return {
        label: s.name,
        data: s.values.slice(from),
        borderColor: c,
        backgroundColor: c,
        borderWidth: s.store === 'amazon' ? 2.5 : 2,
        pointRadius: 0,
        pointHoverRadius: 4,
        stepped: 'before',
        spanGaps: false
      };
    });
    var config = {
      type: 'line',
      data: { labels: payload.labels.slice(from).map(label), datasets: datasets },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 200 },
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { display: datasets.length > 1, position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, boxHeight: 8 } },
          tooltip: {
            backgroundColor: color('text'),
            callbacks: { label: function (ctx) { return ctx.parsed.y == null ? null : ctx.dataset.label + ': ' + euro.format(ctx.parsed.y); } }
          }
        },
        scales: {
          x: { grid: { display: false }, ticks: { maxTicksLimit: 6, maxRotation: 0 } },
          y: { grid: { color: color('border') }, ticks: { callback: function (v) { return euro0.format(v); } } }
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
