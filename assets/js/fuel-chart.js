(function () {
  'use strict';

  var dataEl = document.getElementById('fuel-data');
  var canvas = document.getElementById('fuel-chart');
  if (!dataEl || !canvas || !window.Chart) return;

  var payload = JSON.parse(dataEl.textContent);
  var styles = getComputedStyle(document.documentElement);
  var color = function (name, fallback) { return styles.getPropertyValue('--' + name).trim() || fallback; };
  var colors = { g95: color('azul', '#1F3FD8'), diesel: color('tinta', '#10162F'), g98: color('verde', '#0B7A53') };
  var months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
  var fmt = new Intl.NumberFormat('es-ES', { minimumFractionDigits: 3, maximumFractionDigits: 3 });
  var label = function (iso) { var p = iso.split('-'); return parseInt(p[2], 10) + ' ' + months[parseInt(p[1], 10) - 1]; };

  Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
  Chart.defaults.font.size = 13;
  Chart.defaults.color = color('gris', '#535B7C');

  new Chart(canvas, {
    type: 'line',
    data: {
      labels: payload.labels.map(label),
      datasets: payload.series.map(function (s) {
        return { label: s.name, data: s.values, borderColor: colors[s.key] || '#10162F', backgroundColor: colors[s.key] || '#10162F', borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 4, tension: 0.25, spanGaps: true };
      })
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 12, boxHeight: 12, usePointStyle: true } },
        tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + fmt.format(c.parsed.y) + ' €/l'; } } }
      },
      scales: {
        x: { grid: { display: false }, ticks: { maxTicksLimit: 6, maxRotation: 0 } },
        y: { grid: { color: color('linea', '#DDE2F1') }, ticks: { callback: function (v) { return fmt.format(v); } } }
      }
    }
  });
})();
