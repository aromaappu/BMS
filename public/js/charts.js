/* Shared, lifecycle-safe Chart.js rendering for dashboard, analytics and reports. */
(function () {
  'use strict';
  const active = new Map();
  const colors = ['#4f46e5', '#0d9488', '#f59e0b', '#6366f1', '#3b82f6', '#e879a1', '#8b5cf6', '#14b8a6', '#f97316', '#94a3b8'];

  function destroy(id) {
    const old = active.get(id);
    if (old) { old.destroy(); active.delete(id); }
  }
  function clear() {
    Array.from(active.keys()).forEach(destroy);
  }
  function empty(container, message) {
    container.classList.add('is-empty');
    container.innerHTML = '<p class="chart-empty">' + message + '</p>';
  }
  function shortLabel(value) {
    const s = String(value ?? '');
    return s.length > 15 ? s.slice(0, 14) + '…' : s;
  }
  function numberTick(n) {
    return Number(n).toLocaleString('en-IN', { notation: 'compact', maximumFractionDigits: 1 });
  }
  function create(id, kind, labels, values, config = {}) {
    destroy(id);
    const canvas = document.getElementById(id);
    if (!canvas) return;
    const container = canvas.parentElement;
    if (!window.Chart) {
      empty(container, 'Charts unavailable. Check your internet connection and refresh.');
      return;
    }
    const validLabels = Array.isArray(labels) ? labels : [];
    const validValues = Array.isArray(values) ? values.map(v => Number.isFinite(Number(v)) ? Number(v) : 0) : [];
    if (!validLabels.length || !validValues.length) {
      empty(container, 'No data for this period yet.');
      return;
    }
    container.classList.remove('is-empty');
    const isRing = kind === 'doughnut';
    const isLine = kind === 'line';
    const horizontal = Boolean(config.horizontal);
    const barColor = config.color || colors[0];
    const isCurrency = config.currency !== false;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, isLine ? 'rgba(79,70,229,.25)' : 'rgba(13,148,136,.17)');
    gradient.addColorStop(1, 'rgba(79,70,229,0)');
    const dataset = {
      label: config.label || 'Amount',
      data: validValues.slice(0, validLabels.length),
      borderWidth: isLine ? 3 : 0,
      backgroundColor: isRing ? validLabels.map((_,i) => colors[i % colors.length]) : (isLine ? gradient : barColor),
      borderColor: isLine ? barColor : undefined,
      borderRadius: isRing ? 0 : 7,
      borderSkipped: false,
      maxBarThickness: horizontal ? 22 : 42,
      fill: isLine,
      tension: .36,
      pointRadius: isLine ? 3 : 0,
      pointHoverRadius: isLine ? 5 : 0,
      pointBackgroundColor: '#fff',
      pointBorderWidth: 2,
      hoverOffset: isRing ? 6 : 0
    };
    if (horizontal) container.style.height = Math.max(240, Math.min(440, validLabels.length * 38 + 80)) + 'px';
    else container.style.height = '';
    const axes = isRing ? {} : {
      x: {
        grid: { display: false },
        border: { display: false },
        ticks: {
          color: '#75839a', maxRotation: 0, autoSkip: true, maxTicksLimit: horizontal ? 5 : 7,
          callback: horizontal ? function (v) { return numberTick(v); } : function (v) { return shortLabel(this.getLabelForValue(v)); }
        }
      },
      y: {
        beginAtZero: !horizontal,
        grid: { color: '#eef1f7', drawTicks: false },
        border: { display: false, dash: [4, 4] },
        ticks: {
          color: '#75839a', padding: 10,
          callback: horizontal ? function (v) { return shortLabel(this.getLabelForValue(v)); } : function (v) { return numberTick(v); }
        }
      }
    };
    const chart = new Chart(ctx, {
      type: isRing ? 'doughnut' : isLine ? 'line' : 'bar',
      data: { labels: validLabels, datasets: [dataset] },
      options: {
        responsive: true, maintainAspectRatio: false,
        animation: { duration: 350 },
        interaction: { mode: isRing ? 'nearest' : 'index', intersect: false },
        layout: { padding: { top: 6, right: 8, bottom: 4 } },
        indexAxis: horizontal ? 'y' : 'x',
        cutout: isRing ? '68%' : undefined,
        plugins: {
          legend: {
            display: isRing, position: 'bottom',
            labels: { usePointStyle: true, boxWidth: 9, padding: 15, font: { size: 11 }, color: '#58687f' }
          },
          tooltip: {
            backgroundColor: '#19263f', padding: 12, cornerRadius: 9,
            displayColors: isRing,
            callbacks: {
              title: items => (items.length ? String(items[0].label) : ''),
              label: item => (isRing ? item.label + ': ' : '') +
                (isCurrency ? money(item.raw) : Number(item.raw).toLocaleString('en-IN'))
            }
          }
        },
        scales: axes
      }
    });
    active.set(id, chart);
    return chart;
  }
  window.bmsCharts = { create, destroy, clear };
}());