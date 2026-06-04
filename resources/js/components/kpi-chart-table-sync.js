/**
 * KPI dashboard: fixed-width table + shared scroll with chart.
 * Does not repeatedly mutate chart options (avoids Chart.js proxy stack overflow).
 */
import {
  getFixedKpiContentWidth,
  getDayLabelsFromTable,
  recreateChartWithTableAxis,
  applyChartPaddingFromTable,
  resizeChartToTableWidth,
  applySensibleYScale,
  applyCncWasteLineStyle,
  applyYAxisTickLayout,
  applyKpiTooltipLayout,
  measureTablePlotMetrics,
  kpiHtmlChromePlugin,
  patchKpiChartUpdate,
  applyKpiHtmlChrome,
} from './kpi-chart-axis.js';
import {
  decorateKpiTableRowIcons,
  decorateKpiSectionTitle,
  formatKpiTableNumericCells,
  removeKpiChartTitleBanner,
} from './kpi-table-row-icons.js';

const KPI_SYNC_STYLE_ID = 'kpi-chart-table-sync-styles';

let domReady = false;
let layoutDone = false;
let chartAxisDone = false;
let guidesBound = false;
let stabilizeTimer = null;

const KPI_CHART_SLOT_MIN = 280;

const getChartSlotHeight = () => {
  const canvas = document.getElementById('kpi-actual-target-chart');
  const wrap = canvas?.closest('.kpi-chart-table-wrap');
  const fromStyle = wrap ? parseInt(wrap.style.height || wrap.style.minHeight || '', 10) : NaN;
  const attr = parseInt(canvas?.getAttribute('height') || '', 10);
  const h = Number.isFinite(fromStyle) && fromStyle > 0
    ? fromStyle
    : (Number.isFinite(attr) && attr > 0 ? attr : 300);
  return Math.max(KPI_CHART_SLOT_MIN, h);
};

const renderColumnAlignGuides = () => {
  const chartWrap = document.querySelector('#kpi-chart-table-sync .kpi-chart-table-wrap');
  const table = findTable();
  if (!chartWrap || !table) return;

  document.getElementById('kpi-chart-table-sync-inner')?.querySelector('.kpi-column-guides')?.remove();

  let layer = chartWrap.querySelector('.kpi-column-guides');
  if (!layer) {
    layer = document.createElement('div');
    layer.className = 'kpi-column-guides';
    layer.setAttribute('aria-hidden', 'true');
    chartWrap.appendChild(layer);
  }

  layer.innerHTML = '';
  const wrapRect = chartWrap.getBoundingClientRect();
  const addLine = (x, className) => {
    const line = document.createElement('div');
    line.className = `kpi-column-guide ${className}`;
    line.style.left = `${Math.round(x)}px`;
    layer.appendChild(line);
  };

  const fieldTh = table.querySelector('thead th.kpi-chart-table__field');
  if (fieldTh) {
    const r = fieldTh.getBoundingClientRect();
    addLine(r.right - wrapRect.left, 'kpi-column-guide--field');
  }

  const dayThs = table.querySelectorAll('thead th.kpi-chart-table__day');
  dayThs.forEach((th) => {
    const r = th.getBoundingClientRect();
    addLine(r.left - wrapRect.left, 'kpi-column-guide--edge');
  });

  const lastDay = dayThs[dayThs.length - 1];
  if (lastDay) {
    const r = lastDay.getBoundingClientRect();
    addLine(r.right - wrapRect.left, 'kpi-column-guide--edge');
  }
};

/** Keep chart slot height fixed (no chart.resize on scroll — that caused table overlap). */
const stabilizeKpiLayout = () => {
  clearTimeout(stabilizeTimer);
  stabilizeTimer = setTimeout(() => {
    const canvas = document.getElementById('kpi-actual-target-chart');
    const chartWrap = findChartWrap(canvas);
    if (!chartWrap) return;

    const height = getChartSlotHeight();
    chartWrap.style.flexShrink = '0';
    chartWrap.style.boxSizing = 'border-box';
    if (!chartWrap.style.height) chartWrap.style.height = `${height}px`;
    if (!chartWrap.style.minHeight) chartWrap.style.minHeight = `${height}px`;
    if (!chartWrap.style.maxHeight) chartWrap.style.maxHeight = `${height}px`;

    const tableHost = document.getElementById('presentation-kpi-table');
    if (tableHost) {
      tableHost.style.flexShrink = '0';
      tableHost.style.position = 'static';
    }

    if (canvas?.parentElement && canvas.parentElement !== chartWrap) {
      canvas.parentElement.style.height = `${height}px`;
      canvas.parentElement.style.minHeight = `${height}px`;
      canvas.parentElement.style.maxHeight = `${height}px`;
    }

    renderColumnAlignGuides();
  }, 60);
};

const scheduleAlignGuides = () => {
  requestAnimationFrame(() => {
    requestAnimationFrame(renderColumnAlignGuides);
  });
};

const bindGuideRefresh = () => {
  if (guidesBound) return;
  guidesBound = true;

  const scroll = document.getElementById('kpi-chart-table-sync');
  if (scroll) {
    scroll.addEventListener('scroll', () => {
      renderColumnAlignGuides();
      stabilizeKpiLayout();
    }, { passive: true });
  }
  window.addEventListener('resize', stabilizeKpiLayout, { passive: true });
};

const injectStyles = () => {
  if (document.getElementById(KPI_SYNC_STYLE_ID)) return;
  const style = document.createElement('style');
  style.id = KPI_SYNC_STYLE_ID;
  style.textContent = `
    #kpi-chart-table-sync {
      -webkit-overflow-scrolling: touch;
      overflow-x: auto;
      overflow-y: visible;
    }
    #kpi-chart-table-sync-inner {
      width: var(--kpi-content-width);
      min-width: var(--kpi-content-width);
      display: flex;
      flex-direction: column;
      align-items: flex-start;
    }
    #kpi-chart-table-sync .kpi-chart-table-wrap {
      width: var(--kpi-content-width);
      max-width: var(--kpi-content-width);
      flex: 0 0 auto;
      min-height: ${KPI_CHART_SLOT_MIN}px;
      position: relative;
      overflow: hidden;
      box-sizing: border-box;
      margin-bottom: 8px;
    }
    #kpi-chart-table-sync .kpi-chart-table-wrap canvas#kpi-actual-target-chart {
      display: block !important;
      min-height: ${KPI_CHART_SLOT_MIN}px !important;
    }
    #kpi-chart-table-sync .kpi-chart-table-wrap {
      position: relative;
    }
    #kpi-chart-table-sync .kpi-html-y-axis {
      position: absolute;
      left: 0;
      top: 0;
      pointer-events: none;
      z-index: 6;
    }
    #kpi-chart-table-sync .kpi-html-y-axis__tick {
      position: absolute;
      right: 6px;
      transform: translateY(-50%);
      font-size: 10px;
      line-height: 1;
      color: #6b7280;
      font-variant-numeric: tabular-nums;
      white-space: nowrap;
    }
    #kpi-chart-table-sync .kpi-html-legend {
      position: absolute;
      left: 8px;
      bottom: 6px;
      display: flex;
      flex-wrap: wrap;
      gap: 10px 14px;
      align-items: center;
      font-size: 11px;
      color: #374151;
      pointer-events: none;
      z-index: 6;
    }
    #kpi-chart-table-sync .kpi-html-legend__item {
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    #kpi-chart-table-sync .kpi-html-legend__box {
      width: 14px;
      height: 14px;
      border: 2px solid #6b7280;
      border-radius: 2px;
      flex-shrink: 0;
    }
    #kpi-chart-table-sync .kpi-html-chart-title {
      position: absolute;
      top: 2px;
      left: 140px;
      right: 8px;
      text-align: center;
      pointer-events: none;
      z-index: 7;
    }
    #kpi-chart-table-sync .kpi-html-chart-title__text {
      font-size: 15px;
      font-weight: 700;
      line-height: 1.2;
      color: #000;
    }
    #kpi-chart-table-sync #presentation-kpi-table {
      flex: 0 0 auto;
      position: static;
      width: var(--kpi-content-width);
      overflow: visible;
    }
    #kpi-chart-table-sync #kpi-actual-target-chart {
      display: block;
    }
    #kpi-chart-table-sync .kpi-chart-table {
      table-layout: fixed;
      width: var(--kpi-content-width);
    }
    #kpi-chart-table-sync .kpi-chart-table__field {
      width: 140px; min-width: 140px; max-width: 140px;
    }
    #kpi-chart-table-sync .kpi-chart-table__day {
      width: var(--kpi-day-col-width, 44px);
      min-width: var(--kpi-day-col-width, 44px);
    }
    #kpi-chart-table-sync .kpi-chart-table tbody td.kpi-chart-table__value-cell {
      min-width: var(--kpi-day-col-width, 44px);
      overflow: visible;
    }
    #kpi-chart-table-sync .kpi-chart-table__extra {
      width: 72px; min-width: 72px;
    }
    #kpi-chart-table-sync .kpi-chart-table__field.sticky {
      z-index: 2;
      box-shadow: 2px 0 4px -2px rgb(0 0 0 / 0.08);
    }
    #kpi-chart-table-sync .kpi-row-label {
      display: flex;
      align-items: center;
      gap: 6px;
      min-width: 0;
    }
    #kpi-chart-table-sync .kpi-row-label__icon-wrap {
      display: inline-flex;
      width: 15px;
      height: 15px;
      flex-shrink: 0;
    }
    #kpi-chart-table-sync .kpi-row-label__icon {
      width: 15px;
      height: 15px;
    }
    #kpi-chart-table-sync .kpi-row-label__text {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      min-width: 0;
    }
    #kpi-chart-table-sync .kpi-chart-table-wrap {
      margin-bottom: 0;
      padding-bottom: 0;
    }
    #kpi-chart-table-sync #presentation-kpi-table {
      margin-top: 0 !important;
    }
    #kpi-chart-table-sync .kpi-chart-table thead tr.kpi-chart-x-axis th.kpi-chart-table__day {
      font-weight: 600;
      font-size: 11px;
    }
    #kpi-chart-table-sync .kpi-chart-table thead tr.kpi-chart-x-axis th.kpi-chart-table__field {
      font-size: 11px;
      text-transform: uppercase;
      color: rgb(156 163 175);
    }
    #kpi-chart-table-sync .kpi-chart-table__day,
    #kpi-chart-table-sync .kpi-chart-table tbody td:not(.kpi-chart-table__field),
    #kpi-chart-table-sync .kpi-chart-table tfoot td:not(.kpi-chart-table__field) {
      border-left: 1px solid rgba(148, 163, 184, 0.35);
    }
    #kpi-chart-table-sync tbody td.kpi-chart-table__value-cell {
      padding-left: 6px;
      padding-right: 6px;
    }
    #kpi-chart-table-sync tbody td.kpi-chart-table__value-cell > div {
      white-space: nowrap;
      font-variant-numeric: tabular-nums;
      font-size: 11px;
      line-height: 1.25;
      overflow: visible;
    }
    #kpi-chart-table-sync-inner {
      overflow: visible;
    }
    #kpi-chart-table-sync .kpi-chart-table-wrap .kpi-column-guides {
      position: absolute;
      inset: 0;
      pointer-events: none;
      z-index: 1;
      overflow: hidden;
    }
    #kpi-chart-table-sync .kpi-column-guide {
      position: absolute;
      top: 0;
      bottom: 0;
      width: 0;
    }
    #kpi-chart-table-sync .kpi-column-guide--edge {
      border-left: 1px solid rgba(148, 163, 184, 0.55);
    }
    #kpi-chart-table-sync .kpi-column-guide--field {
      border-left: 2px solid rgba(100, 116, 139, 0.75);
    }
    #kpi-chart-table-sync .kpi-chart-table thead th.kpi-header-sat div,
    #kpi-chart-table-sync .kpi-chart-table thead th[style*="255, 255, 0"] div,
    #kpi-chart-table-sync .kpi-chart-table thead th[style*="255,255,0"] div {
      color: #000 !important;
    }
    #kpi-chart-table-sync,
    #kpi-chart-table-sync #presentation-kpi-table {
      display: block !important;
      visibility: visible !important;
    }
    .kpi-duplicate-table-hidden {
      display: none !important;
    }
    #kpi-chart-table-sync .kpi-chart-table-wrap canvas {
      max-width: none !important;
      flex-shrink: 0;
    }
  `;
  document.head.appendChild(style);
};

const isExtraHeader = (th) => {
  const t = (th.textContent || '').trim().toLowerCase();
  return t === 'total' || t.startsWith('average');
};

const KPI_DAY_COL_MIN = 44;
/** Wide enough for millions in id-ID format e.g. 10.972.350 */
const KPI_DAY_COL_MAX = 220;

let textMeasureCanvas;

const measureTextPx = (text, font) => {
  if (!textMeasureCanvas) textMeasureCanvas = document.createElement('canvas');
  const ctx = textMeasureCanvas.getContext('2d');
  ctx.font = font;
  return ctx.measureText(text).width;
};

const getTableMeasureFont = (table) => {
  const sample = table.querySelector('tbody td:not(:first-child) div')
    || table.querySelector('thead th.kpi-chart-table__day div');
  if (!sample) return '500 11px Inter, system-ui, sans-serif';
  const cs = getComputedStyle(sample);
  return `${cs.fontWeight} ${cs.fontSize} ${cs.fontFamily}`;
};

/** Fit widest cell value — full numbers visible, no ellipsis. */
const measureDayColWidth = (table) => {
  if (!table) return KPI_DAY_COL_MIN;

  const font = getTableMeasureFont(table);
  const cellPad = 16;
  let maxW = KPI_DAY_COL_MIN;

  const consider = (text) => {
    const t = String(text ?? '').trim();
    if (!t || t === 'OK' || t === 'NG') return;
    maxW = Math.max(maxW, measureTextPx(t, font) + cellPad);
  };

  table.querySelectorAll('thead th.kpi-chart-table__day div').forEach((el) => {
    consider(el.textContent);
  });

  table.querySelectorAll('tbody tr').forEach((tr) => {
    tr.querySelectorAll('td:not(:first-child)').forEach((td) => {
      consider(td.querySelector('div')?.textContent || td.textContent);
    });
  });

  return Math.min(KPI_DAY_COL_MAX, Math.max(KPI_DAY_COL_MIN, Math.ceil(maxW)));
};

const tagTableColumns = (table) => {
  const row = table.querySelector('thead tr');
  if (!row) return { dayCount: 0, extraCount: 0 };

  row.classList.add('kpi-chart-x-axis');

  let dayCount = 0;
  let extraCount = 0;

  Array.from(row.querySelectorAll('th')).forEach((th, i) => {
    th.classList.remove('kpi-chart-table__field', 'kpi-chart-table__day', 'kpi-chart-table__extra');
    if (i === 0) {
      th.classList.add('kpi-chart-table__field', 'bg-gray-50', 'dark:bg-gray-700/50');
    } else if (isExtraHeader(th)) {
      th.classList.add('kpi-chart-table__extra');
      extraCount += 1;
    } else {
      th.classList.add('kpi-chart-table__day');
      dayCount += 1;
    }
  });

  table.querySelectorAll('tbody tr, tfoot tr').forEach((tr) => {
    const td = tr.querySelector('td');
    if (!td) return;
    const footer = tr.parentElement?.tagName === 'TFOOT';
    td.classList.add('kpi-chart-table__field');
    if (footer) td.classList.add('bg-gray-50', 'dark:bg-gray-700/50');
    else td.classList.add('bg-white', 'dark:bg-gray-800');
  });

  table.querySelectorAll('tbody td:not(:first-child)').forEach((td) => {
    td.classList.add('kpi-chart-table__value-cell');
    const div = td.querySelector('div');
    if (div) div.removeAttribute('title');
  });

  fixSaturdayHeaderTextColor(table);
  renameFieldHeaderToDate(table);
  decorateKpiTableRowIcons(table);
  formatKpiTableNumericCells(table);

  const dayColPx = measureDayColWidth(table);
  window.kpiTableDayColPx = dayColPx;

  return { dayCount, extraCount, dayColPx };
};

const renameFieldHeaderToDate = (table) => {
  const label = table?.querySelector('thead tr th.kpi-chart-table__field div')
    || table?.querySelector('thead tr th:first-child div');
  if (label) label.textContent = 'Date';
};

const fixSaturdayHeaderTextColor = (table) => {
  if (!table) return;
  table.querySelectorAll('thead th').forEach((th) => {
    const style = (th.getAttribute('style') || '').replace(/\s/g, '');
    const isSaturday = style.includes('255,255,0') || style.includes('rgb(255,255,0)');
    if (isSaturday) {
      th.classList.add('kpi-header-sat');
      const label = th.querySelector('div');
      if (label) label.style.color = '#000';
    }
  });
};

const ensureColgroup = (table, dayCount, extraCount, dayColPx = 44) => {
  let cg = table.querySelector('colgroup.kpi-sync-cols');
  if (!cg) {
    cg = document.createElement('colgroup');
    cg.className = 'kpi-sync-cols';
    table.insertBefore(cg, table.firstChild);
  }
  cg.innerHTML = '';
  const f = document.createElement('col');
  f.style.width = '140px';
  cg.appendChild(f);
  for (let i = 0; i < dayCount; i += 1) {
    const c = document.createElement('col');
    c.style.width = `${dayColPx}px`;
    cg.appendChild(c);
  }
  for (let i = 0; i < extraCount; i += 1) {
    const c = document.createElement('col');
    c.style.width = '72px';
    cg.appendChild(c);
  }
};

const findChartWrap = (canvas) => canvas?.closest('.kpi-chart-table-wrap')
  || canvas?.closest('[class*="h-["]')
  || canvas?.parentElement;

const findTable = () => {
  const inSync = document.querySelector('#kpi-chart-table-sync .kpi-chart-table');
  if (inSync) return inSync;
  const canvas = document.getElementById('kpi-actual-target-chart');
  const wrap = canvas?.closest('.p-5');
  return wrap?.querySelector('table') || null;
};

/** Hide extra KPI tables outside the chart sync area (never remove from DOM). */
const hideDuplicateKpiTablesOutsideSync = () => {
  const canvas = document.getElementById('kpi-actual-target-chart');
  const panel = canvas?.closest('.col-span-full');
  const sync = document.getElementById('kpi-chart-table-sync');
  if (!panel || !sync) return;

  panel.querySelectorAll('table').forEach((table) => {
    if (sync.contains(table)) return;

    const host = table.closest('.overflow-x-auto')
      || table.closest('.mt-4')
      || table.parentElement;
    if (!host || sync.contains(host)) return;

    host.classList.add('kpi-duplicate-table-hidden');
  });

  sync.querySelectorAll('.kpi-duplicate-table-hidden').forEach((el) => {
    el.classList.remove('kpi-duplicate-table-hidden');
  });
};

const isPrebuiltSyncDom = (canvas, chartWrap, scroll, inner, tableHost) => (
  scroll
  && inner
  && chartWrap
  && tableHost
  && inner.contains(chartWrap)
  && inner.contains(tableHost)
  && scroll.contains(inner)
);

const setupDom = () => {
  const canvas = document.getElementById('kpi-actual-target-chart');
  if (!canvas) return false;

  injectStyles();

  const chartWrap = findChartWrap(canvas);
  if (!chartWrap) return false;

  let table = findTable();
  if (!table) return false;

  const scroll = document.getElementById('kpi-chart-table-sync');
  const inner = document.getElementById('kpi-chart-table-sync-inner');
  let tableHost = document.getElementById('presentation-kpi-table');

  if (isPrebuiltSyncDom(canvas, chartWrap, scroll, inner, tableHost)) {
    chartWrap.classList.add('kpi-chart-table-wrap');
    inner.style.display = 'flex';
    inner.style.flexDirection = 'column';
    inner.style.alignItems = 'flex-start';
    tableHost.classList.remove('mt-4', 'overflow-x-auto');
    table = tableHost.querySelector('table') || table;
    table.classList.remove('table-auto', 'w-full');
    table.classList.add('kpi-chart-table', 'table-fixed');
    tagTableColumns(table);
    hideDuplicateKpiTablesOutsideSync();
    domReady = true;
    return true;
  }

  const contentWrap = chartWrap.parentElement;
  if (!contentWrap) return false;

  let scrollEl = scroll;
  let innerEl = inner;

  if (!scrollEl) {
    scrollEl = document.createElement('div');
    scrollEl.id = 'kpi-chart-table-sync';
    scrollEl.className = 'overflow-x-auto';
    contentWrap.insertBefore(scrollEl, contentWrap.firstChild);
  }

  if (!innerEl) {
    innerEl = document.createElement('div');
    innerEl.id = 'kpi-chart-table-sync-inner';
    scrollEl.appendChild(innerEl);
  }
  innerEl.style.display = 'flex';
  innerEl.style.flexDirection = 'column';
  innerEl.style.alignItems = 'flex-start';

  chartWrap.classList.add('kpi-chart-table-wrap');
  if (!innerEl.contains(chartWrap)) {
    innerEl.insertBefore(chartWrap, innerEl.firstChild);
  }

  if (!tableHost) {
    tableHost = document.createElement('div');
    tableHost.id = 'presentation-kpi-table';
    innerEl.appendChild(tableHost);
  } else if (!innerEl.contains(tableHost)) {
    tableHost.classList.remove('mt-4', 'overflow-x-auto');
    innerEl.appendChild(tableHost);
  }

  if (!tableHost.contains(table)) {
    tableHost.innerHTML = '';
    tableHost.appendChild(table);
  }

  table = tableHost.querySelector('table') || table;
  table.classList.remove('table-auto', 'w-full');
  table.classList.add('kpi-chart-table', 'table-fixed');
  tagTableColumns(table);
  hideDuplicateKpiTablesOutsideSync();

  domReady = true;
  return true;
};

const observePresentationTableUpdates = () => {
  const host = document.getElementById('presentation-kpi-table');
  if (!host || host.__kpiTableObserved) return;

  const observer = new MutationObserver(() => {
    layoutDone = false;
    chartAxisDone = false;
    setTimeout(runSync, 50);
  });

  observer.observe(host, { childList: true, subtree: false });
  host.__kpiTableObserved = true;
};

const applyLayout = () => {
  if (layoutDone) return;

  const table = findTable();
  if (!table) return;

  const { dayCount, extraCount, dayColPx } = tagTableColumns(table);
  if (!dayCount) return;

  const w = getFixedKpiContentWidth(dayCount, extraCount, dayColPx);
  const scroll = document.getElementById('kpi-chart-table-sync');
  const inner = document.getElementById('kpi-chart-table-sync-inner');
  const chartWrap = findChartWrap(document.getElementById('kpi-actual-target-chart'));

  if (scroll) {
    scroll.style.setProperty('--kpi-content-width', `${w}px`);
    scroll.style.setProperty('--kpi-day-col-width', `${dayColPx || 44}px`);
  }
  if (inner) {
    inner.style.width = `${w}px`;
    inner.style.minWidth = `${w}px`;
  }
  if (table) {
    table.style.width = `${w}px`;
    ensureColgroup(table, dayCount, extraCount, dayColPx || 44);
  }
  const slotHeight = getChartSlotHeight();

  if (chartWrap) {
    chartWrap.style.width = `${w}px`;
    chartWrap.style.maxWidth = `${w}px`;
    chartWrap.style.flexShrink = '0';
    chartWrap.style.height = `${slotHeight}px`;
    chartWrap.style.minHeight = `${slotHeight}px`;
    chartWrap.style.maxHeight = `${slotHeight}px`;
    chartWrap.style.overflow = 'hidden';
    chartWrap.style.marginBottom = '8px';
    chartWrap.style.boxSizing = 'border-box';
  }

  const tableHost = document.getElementById('presentation-kpi-table');
  if (tableHost) {
    tableHost.style.marginTop = '0';
    tableHost.style.flexShrink = '0';
    tableHost.style.width = `${w}px`;
  }

  const canvas = document.getElementById('kpi-actual-target-chart');
  if (canvas) {
    canvas.style.width = `${w}px`;
    canvas.style.maxWidth = 'none';
    if (canvas.parentElement) {
      canvas.parentElement.style.width = `${w}px`;
      canvas.parentElement.style.height = `${slotHeight}px`;
      canvas.parentElement.style.minHeight = `${slotHeight}px`;
    }
  }

  layoutDone = true;
};

const applyChartSize = (dayCount, extraCount) => {
  const chart = window.kpiActualTargetChartInstance;
  if (!chart?.canvas || !dayCount) return;
  resizeChartToTableWidth(chart, dayCount, extraCount);
  applySensibleYScale(chart);
  applyYAxisTickLayout(chart);
  applyKpiTooltipLayout(chart);
  removeKpiChartTitleBanner(chart);
  chart.__kpiTableMetrics = measureTablePlotMetrics(chart);
};

const unifyChartWithTable = () => {
  const chart = window.kpiActualTargetChartInstance;
  const table = findTable();
  if (!chart?.canvas || !table) return;

  const { dayCount, extraCount } = tagTableColumns(table);
  const xType = chart.options?.scales?.x?.type;
  const mustRecreate = xType === 'time' || !chart.__kpiTableAxisDone;

  if (mustRecreate) {
    if (recreateChartWithTableAxis(chart, table, extraCount)) {
      chartAxisDone = true;
      const live = window.kpiActualTargetChartInstance;
      if (live) {
        patchKpiChartUpdate(live);
        resizeChartToTableWidth(live, dayCount, extraCount);
        applySensibleYScale(live);
        applyYAxisTickLayout(live);
        applyKpiTooltipLayout(live);
        live.__kpiTableMetrics = measureTablePlotMetrics(live);
      }
    } else {
      const live = window.kpiActualTargetChartInstance;
      if (live) {
        applyChartPaddingFromTable(live, extraCount);
        patchKpiChartUpdate(live);
      }
      applyChartSize(dayCount, extraCount);
    }
  } else {
    patchKpiChartUpdate(chart);
    applyChartSize(dayCount, extraCount);
  }
  patchCncWasteLinesOnLiveChart();
  scheduleAlignGuides();
  bindGuideRefresh();
};

const patchCncWasteLinesOnLiveChart = () => {
  const chart = window.kpiActualTargetChartInstance;
  if (!chart?.data?.datasets?.length) return;

  const styled = applyCncWasteLineStyle(chart.data.datasets);
  let changed = false;
  styled.forEach((ds, i) => {
    const live = chart.data.datasets[i];
    if (!live || live.showLine === ds.showLine) return;
    live.showLine = ds.showLine;
    live.spanGaps = ds.spanGaps;
    live.borderWidth = ds.borderWidth;
    live.tension = ds.tension;
    changed = true;
  });

  if (changed) {
    chart.__kpiTableMetrics = null;
  }
};

const runSync = () => {
  if (!document.getElementById('kpi-actual-target-chart')) return;

  decorateKpiSectionTitle();
  removeKpiChartTitleBanner();

  if (!setupDom()) return;

  const table = findTable();
  if (!table) return;

  const { dayCount, extraCount } = tagTableColumns(table);

  if (!layoutDone) {
    applyLayout();
  }
  unifyChartWithTable();
  removeKpiChartTitleBanner(window.kpiActualTargetChartInstance);
  hideDuplicateKpiTablesOutsideSync();
  stabilizeKpiLayout();

  const live = window.kpiActualTargetChartInstance;
  if (live) {
    ensureHtmlChromePlugin(live);
    patchKpiChartUpdate(live);
    if (!live.options.plugins) live.options.plugins = {};
    live.options.plugins.kpiHtmlChrome = true;
    applyYAxisTickLayout(live);
    applyKpiHtmlChrome(live);
    try {
      live.update('none');
    } catch (e) {
      // no-op
    }
  }

  scheduleAlignGuides();
  bindGuideRefresh();
};

const resetForNewChart = () => {
  domReady = false;
  layoutDone = false;
  chartAxisDone = false;
};

const wrapChartRender = () => {
  const orig = window.renderKpiActualTargetChart;
  if (typeof orig !== 'function' || orig.__kpiWrapped) return;

  window.renderKpiActualTargetChart = function kpiRenderWrapped() {
    resetForNewChart();
    const existing = window.kpiActualTargetChartInstance;
    if (existing?.destroy) {
      try {
        existing.destroy();
      } catch (err) {
        console.warn('[kpi-chart-sync] destroy before render:', err);
      }
      window.kpiActualTargetChartInstance = null;
    }
    orig();
    const live = window.kpiActualTargetChartInstance;
    if (live) {
      ensureHtmlChromePlugin(live);
      patchKpiChartUpdate(live);
    }
    setTimeout(runSync, 50);
    setTimeout(runSync, 400);
  };
  window.renderKpiActualTargetChart.__kpiWrapped = true;
};

const ensureHtmlChromePlugin = (chart) => {
  const Ctor = chart?.constructor;
  if (Ctor?.register && !Ctor.registry?.plugins?.get(kpiHtmlChromePlugin.id)) {
    Ctor.register(kpiHtmlChromePlugin);
  }
};

const init = () => {
  decorateKpiSectionTitle();
  removeKpiChartTitleBanner();
  wrapChartRender();
  observePresentationTableUpdates();

  const pinLiveChart = () => {
    const existing = window.kpiActualTargetChartInstance;
    if (!existing) return;
    ensureHtmlChromePlugin(existing);
    patchKpiChartUpdate(existing);
    if (!existing.options.plugins) existing.options.plugins = {};
    existing.options.plugins.kpiHtmlChrome = true;
    applyYAxisTickLayout(existing);
    applyKpiHtmlChrome(existing);
  };

  const afterAppChart = () => {
    setTimeout(pinLiveChart, 0);
    setTimeout(pinLiveChart, 250);
    setTimeout(pinLiveChart, 800);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', afterAppChart, { once: true });
  } else {
    afterAppChart();
  }

  const scheduleInitialSync = () => {
    setTimeout(runSync, 50);
    setTimeout(runSync, 400);
    setTimeout(() => {
      const c = window.kpiActualTargetChartInstance;
      if (c) {
        patchKpiChartUpdate(c);
        applyYAxisTickLayout(c);
        applyKpiHtmlChrome(c);
        try {
          c.update('none');
        } catch (e) {
          // no-op
        }
      }
    }, 900);
  };

  if (document.readyState === 'complete') {
    scheduleInitialSync();
  } else {
    window.addEventListener('load', scheduleInitialSync, { once: true });
  }
};

window.syncKpiChartTableLayout = runSync;
window.resetKpiChartTableLayout = resetForNewChart;
window.getDayLabelsFromTable = getDayLabelsFromTable;
window.renderKpiColumnAlignGuides = renderColumnAlignGuides;
window.stabilizeKpiChartTableLayout = stabilizeKpiLayout;

init();
