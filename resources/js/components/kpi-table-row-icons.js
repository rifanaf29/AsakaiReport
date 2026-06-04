/**
 * Icons only for Date header, Target row, and Actual row.
 */
import { applyYAxisTickLayout, applyKpiHtmlChrome } from './kpi-chart-axis.js';

const SVG = 'class="kpi-row-label__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"';

const ICONS = {
  date: `<svg ${SVG}><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>`,
  target: `<svg ${SVG}><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>`,
  actual: `<svg ${SVG}><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M4 19h16"/></svg>`,
};

const COLORS = {
  date: 'text-gray-500 dark:text-gray-400',
  target: 'text-red-700 dark:text-red-400',
  actual: 'text-blue-700 dark:text-blue-400',
};

/** Only Date / Target / Actual — not field rows (Accident, Kedatangan, etc.). */
export const resolveCoreRowIconKey = (text) => {
  const t = String(text ?? '').trim().toLowerCase();
  if (t === 'date' || t === 'field') return 'date';
  if (/^target\b/.test(t)) return 'target';
  if (/^actual\s*\(/.test(t)) return 'actual';
  return null;
};

const wrapLabelWithIcon = (labelEl, iconKey) => {
  if (!labelEl || !iconKey || labelEl.querySelector('.kpi-row-label')) return;

  const text = (labelEl.textContent || '').trim();
  let colorClass = COLORS[iconKey] || COLORS.date;
  const td = labelEl.closest('td');
  if (labelEl.classList.contains('text-white')
    || (td?.getAttribute('style') || '').includes('192, 0, 0')
    || (td?.getAttribute('style') || '').includes('0, 112, 192')) {
    colorClass = 'text-white';
  }

  const wrap = document.createElement('div');
  wrap.className = 'kpi-row-label flex items-center gap-1.5 min-w-0';
  wrap.innerHTML = `<span class="kpi-row-label__icon-wrap shrink-0 ${colorClass}">${ICONS[iconKey]}</span><span class="kpi-row-label__text truncate leading-tight">${text}</span>`;
  labelEl.textContent = '';
  labelEl.appendChild(wrap);
};

const SECTION_TITLE_ICON = `<svg class="kpi-section-title__icon w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>`;

/** Icon on card header "KPI Actual vs Target". */
export const decorateKpiSectionTitle = () => {
  document.querySelectorAll('h2').forEach((h2) => {
    const text = (h2.textContent || '').trim();
    if (!/^KPI Actual vs Target$/i.test(text)) return;
    if (h2.querySelector('.kpi-section-title__icon')) return;

    h2.classList.add('flex', 'items-center', 'gap-2');
    h2.innerHTML = `<span class="inline-flex shrink-0 text-blue-600 dark:text-blue-400">${SECTION_TITLE_ICON}</span><span>${text}</span>`;
  });
};

/**
 * Table cells are formatted server-side (KpiNumberFormat).
 * Do not re-parse here: JS treats "2.230" (id-ID thousands) as 2.23 and rounds PCS to "2".
 */
export const formatKpiTableNumericCells = () => {};

/** Remove duplicate HTML title banner; keep Chart.js title visible with safe spacing. */
export const applyKpiChartTitleLayout = (chart) => {
  const live = chart || window.kpiActualTargetChartInstance;
  if (!live?.options?.plugins) return;

  const meta = window.kpiActualTargetChartMeta || {};
  const templateTitle = meta.template_title || 'All Templates';
  const monthLabel = meta.month_label || '';
  const titleLines = monthLabel ? [templateTitle, monthLabel] : [templateTitle];

  if (!live.options.plugins.title) live.options.plugins.title = {};
  live.options.plugins.title.display = false;

  if (live.options.plugins.legend) {
    live.options.plugins.legend.display = false;
  }
  live.options.plugins.kpiHtmlChrome = true;

  if (!live.options.layout) live.options.layout = {};
  if (!live.options.layout.padding) live.options.layout.padding = {};
  live.options.layout.padding.top = Math.max(live.options.layout.padding.top ?? 0, 36);
  live.options.layout.padding.bottom = Math.max(live.options.layout.padding.bottom ?? 0, 18);

  applyYAxisTickLayout(live);
  applyKpiHtmlChrome(live);

  try {
    live.update('none');
  } catch (e) {
    // chart may not be fully initialized yet
  }
};

export const removeKpiChartTitleBanner = (chart) => {
  document.getElementById('kpi-chart-title-banner')?.remove();
  document.querySelectorAll('.kpi-chart-title-banner').forEach((el) => el.remove());
  applyKpiChartTitleLayout(chart);
};

export const decorateKpiTableRowIcons = (table) => {
  if (!table) return;

  const dateHeader = table.querySelector('thead tr th.kpi-chart-table__field div')
    || table.querySelector('thead tr th:first-child div');
  if (dateHeader) wrapLabelWithIcon(dateHeader, 'date');

  table.querySelectorAll('tbody tr td.kpi-chart-table__field div, tbody tr td:first-child div').forEach((div) => {
    const key = resolveCoreRowIconKey(div.textContent);
    if (key === 'target' || key === 'actual') wrapLabelWithIcon(div, key);
  });
};
