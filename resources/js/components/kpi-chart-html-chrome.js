/**
 * Y-axis tick labels + legend as HTML (Chart.js cannot keep ticks left reliably).
 */
const KPI_HTML_Y_AXIS_WIDTH = 132;
/** Top strip for HTML chart title (inside chart wrap, above plot). */
export const KPI_HTML_TITLE_TOP_PX = 32;
export const KPI_PLOT_PAD_TOP = KPI_HTML_TITLE_TOP_PX + 4;

const escapeHtml = (value) => String(value ?? '')
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;');

import { formatKpiNumber } from './kpi-number-format.js';

const formatTickValue = (value, unit) => {
  const formatted = formatKpiNumber(value, { unit });
  if (!formatted) return '';
  if (!unit) return formatted;
  if (unit === '%') return `${formatted}%`;
  return `${formatted} ${unit}`;
};

const resolveYAxisUnit = (chart, scaleId) => {
  const meta = window.kpiActualTargetChartMeta || {};
  if (scaleId === 'yKg') return 'kg';
  if (scaleId === 'yPct') return '%';
  if (scaleId === 'yRp') return 'Rp';
  if (scaleId === 'yMp') return 'Orang';
  return meta.unit || '';
};

export const ensureHtmlChromeHosts = (chart) => {
  const canvas = chart?.canvas;
  const wrap = canvas?.closest('.kpi-chart-table-wrap');
  if (!wrap || !canvas) return null;

  let yHost = wrap.querySelector('.kpi-html-y-axis');
  if (!yHost) {
    yHost = document.createElement('div');
    yHost.className = 'kpi-html-y-axis';
    yHost.setAttribute('aria-hidden', 'true');
    wrap.insertBefore(yHost, canvas);
  }

  let titleHost = wrap.querySelector('.kpi-html-chart-title');
  if (!titleHost) {
    titleHost = document.createElement('div');
    titleHost.className = 'kpi-html-chart-title';
    titleHost.setAttribute('aria-hidden', 'true');
    wrap.insertBefore(titleHost, canvas);
  }

  let legHost = wrap.querySelector('.kpi-html-legend');
  if (!legHost) {
    legHost = document.createElement('div');
    legHost.className = 'kpi-html-legend';
    legHost.setAttribute('aria-hidden', 'true');
    wrap.appendChild(legHost);
  }

  return { wrap, yHost, titleHost, legHost, canvas };
};

export const renderHtmlChartTitle = (chart) => {
  const hosts = ensureHtmlChromeHosts(chart);
  if (!hosts?.titleHost) return;

  const meta = window.kpiActualTargetChartMeta || {};
  const templateTitle = String(meta.template_title || 'All Templates').trim();
  const monthLabel = String(meta.month_label || '').trim();
  const text = monthLabel ? `${templateTitle}, ${monthLabel}` : templateTitle;

  hosts.titleHost.innerHTML = text
    ? `<div class="kpi-html-chart-title__text">${escapeHtml(text)}</div>`
    : '';
};

export const renderHtmlYAxisLabels = (chart) => {
  const hosts = ensureHtmlChromeHosts(chart);
  if (!hosts) return;

  const { yHost, canvas } = hosts;
  const { chartArea } = chart;
  if (!chartArea?.height) {
    yHost.innerHTML = '';
    return;
  }

  const scaleId = Object.keys(chart.scales).find((id) => id !== 'x' && chart.scales[id]?.position === 'left')
    || Object.keys(chart.scales).find((k) => k !== 'x');
  const scale = scaleId ? chart.scales[scaleId] : null;
  if (!scale?.ticks?.length) {
    yHost.innerHTML = '';
    return;
  }

  const unit = resolveYAxisUnit(chart, scaleId);
  const h = canvas.offsetHeight || chart.height;
  yHost.style.width = `${KPI_HTML_Y_AXIS_WIDTH}px`;
  yHost.style.height = `${h}px`;
  yHost.style.top = `${canvas.offsetTop}px`;
  yHost.style.left = '0';

  const parts = [];
  scale.ticks.forEach((tick, i) => {
    const py = scale.getPixelForTick(i);
    if (!Number.isFinite(py)) return;
    const label = formatTickValue(tick.value, unit);
    if (!label) return;
    parts.push(
      `<span class="kpi-html-y-axis__tick" style="top:${Math.round(py)}px">${label}</span>`,
    );
  });

  yHost.innerHTML = parts.join('');
};

export const renderHtmlLegend = (chart) => {
  const hosts = ensureHtmlChromeHosts(chart);
  if (!hosts) return;

  const { legHost } = hosts;
  const datasets = chart.data?.datasets || [];
  if (!datasets.length) {
    legHost.innerHTML = '';
    return;
  }

  legHost.innerHTML = datasets.map((ds) => {
    const color = ds.borderColor || ds.backgroundColor || '#6b7280';
    const label = String(ds.label || '').trim();
    return (
      `<span class="kpi-html-legend__item">`
      + `<span class="kpi-html-legend__box" style="border-color:${color};background:${typeof ds.backgroundColor === 'string' && ds.backgroundColor.includes('rgba') ? ds.backgroundColor : 'transparent'}"></span>`
      + `<span class="kpi-html-legend__text">${label}</span>`
      + `</span>`
    );
  }).join('');
};

/** Hide Chart.js Y ticks + legend; draw HTML chrome instead. */
export const applyKpiHtmlChrome = (chart) => {
  if (!chart?.options) return;

  if (!chart.options.plugins) chart.options.plugins = {};
  if (!chart.options.plugins.legend) chart.options.plugins.legend = {};
  chart.options.plugins.legend.display = false;
  if (!chart.options.plugins.title) chart.options.plugins.title = {};
  chart.options.plugins.title.display = false;

  if (!chart.options.layout) chart.options.layout = {};
  if (!chart.options.layout.padding) chart.options.layout.padding = {};
  chart.options.layout.padding.top = Math.max(
    Number(chart.options.layout.padding.top) || 0,
    KPI_PLOT_PAD_TOP,
  );

  Object.keys(chart.options.scales || {}).forEach((axisId) => {
    if (axisId === 'x') return;
    const scale = chart.options.scales[axisId];
    if (!scale?.ticks) scale.ticks = {};
    if (scale.position === 'left' || axisId === 'y') {
      scale.ticks.display = false;
    }
  });

  renderHtmlChartTitle(chart);
  renderHtmlYAxisLabels(chart);
  renderHtmlLegend(chart);
};

export const kpiHtmlChromePlugin = {
  id: 'kpiHtmlChrome',
  afterDraw(chart) {
    if (chart.options?.plugins?.kpiHtmlChrome === false) return;
    if (chart.canvas?.id !== 'kpi-actual-target-chart') return;
    applyKpiHtmlChrome(chart);
  },
};
