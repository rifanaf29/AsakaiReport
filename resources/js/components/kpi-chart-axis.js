/**
 * KPI chart ↔ table alignment: table header is the x-axis; chart plot aligns to day columns.
 */
import {
  applyKpiHtmlChrome,
  kpiHtmlChromePlugin,
  KPI_HTML_TITLE_TOP_PX,
  KPI_PLOT_PAD_TOP,
} from './kpi-chart-html-chrome.js';
import { formatKpiNumber } from './kpi-number-format.js';

export {
  applyKpiHtmlChrome,
  kpiHtmlChromePlugin,
  KPI_HTML_TITLE_TOP_PX,
  KPI_PLOT_PAD_TOP,
} from './kpi-chart-html-chrome.js';

export const KPI_FIELD_COL_PX = 140;
/** Y-axis labels + unit fit in this strip (left of the Date column border at 140px). */
export const KPI_Y_AXIS_STRIP_PX = KPI_FIELD_COL_PX;
export const KPI_DAY_COL_PX = 44;

export const getKpiDayColPx = () => {
  const n = Number(window.kpiTableDayColPx);
  return Number.isFinite(n) && n > 0 ? n : KPI_DAY_COL_PX;
};
export const KPI_EXTRA_COL_PX = 72;
export const KPI_CHART_PAD_LEFT = KPI_Y_AXIS_STRIP_PX;
/** Right layout padding = width of extra table columns only (keeps plot width = days × 44px). */
export const kpiChartPadRight = (extraCount = 0) => Math.max(Number(extraCount) || 0, 0) * KPI_EXTRA_COL_PX;

export const getDayLabelsFromTable = (table) => {
  if (!table) return [];

  const dayHeaders = table.querySelectorAll('thead tr th.kpi-chart-table__day');
  if (dayHeaders.length) {
    return Array.from(dayHeaders).map((th) => (th.textContent || '').replace(/\s+/g, '').trim());
  }

  const headerRow = table.querySelector('thead tr');
  if (!headerRow) return [];

  return Array.from(headerRow.querySelectorAll('th'))
    .slice(1)
    .filter((th) => {
      const text = (th.textContent || '').trim().toLowerCase();
      return text !== 'total' && !text.startsWith('average');
    })
    .map((th) => (th.textContent || '').replace(/\s+/g, '').trim());
};

export const labelsToDayNumbers = (labels) => (labels || []).map((label) => {
  const parts = String(label ?? '').split('-');
  if (parts.length === 3) {
    const day = Number.parseInt(parts[1], 10);
    if (Number.isFinite(day)) return String(day).padStart(2, '0');
  }
  const text = String(label ?? '').trim();
  return /^\d{1,2}$/.test(text) ? text.padStart(2, '0') : text;
});

export const formatKpiChartValue = (value, unit) => {
  if (value === null || value === undefined || value === '') return null;

  const formatted = formatKpiNumber(value, { unit });
  if (!formatted) return null;

  if (!unit) return formatted;
  if (unit === '%') return `${formatted}%`;
  return `${formatted} ${unit}`;
};

const resolveDatasetUnit = (dataset) => {
  const axis = dataset?.yAxisID;
  if (axis === 'yKg') return 'kg';
  if (axis === 'yPct') return '%';
  if (axis === 'yMp') return 'Orang';
  if (axis === 'yRp') return 'Rp';
  return window.kpiActualTargetChartMeta?.unit ?? null;
};

const readDataPointY = (point) => {
  if (point == null) return null;
  if (typeof point === 'object' && point.y != null) return Number(point.y);
  return Number(point);
};

const NICE_STEP_MULTIPLES = [1, 2, 2.5, 5, 10];
const TARGET_TICK_COUNT = 5;
const AXIS_HEADROOM = 1.05;

/** Whole-number step (never below 1) so every tick from 0 to max lands on an integer. */
const niceIntegerStep = (span) => {
  const rough = span / TARGET_TICK_COUNT;
  if (!Number.isFinite(rough) || rough <= 0) return 1;

  const exp = Math.pow(10, Math.floor(Math.log10(rough)));
  const multiple = NICE_STEP_MULTIPLES.find((m) => m * exp >= rough) ?? 10;
  return Math.max(Math.ceil(multiple * exp), 1);
};

/**
 * Y-axis max from highest plotted value (+ headroom), snapped to a whole multiple of an
 * integer step so Chart.js never labels the axis with decimals.
 */
export const computeAxisMax = (values) => {
  const nums = values.filter((v) => Number.isFinite(v) && v >= 0);
  if (!nums.length) return undefined;

  const rawMax = Math.max(...nums);
  if (rawMax <= 0) return 10;

  const span = rawMax * AXIS_HEADROOM;
  const step = niceIntegerStep(span);
  return Math.max(step * Math.ceil(span / step), step);
};

export const applySensibleYScale = (chart) => {
  if (!chart?.options?.scales || !chart.data?.datasets) return;

  Object.keys(chart.options.scales).forEach((axisId) => {
    if (axisId === 'x') return;

    const scale = chart.options.scales[axisId];
    if (axisId === 'yPct' && scale.max != null) return;

    const axisKey = axisId === 'y' ? 'y' : axisId;
    const values = [];

    chart.data.datasets.forEach((ds) => {
      if ((ds.yAxisID || 'y') !== axisKey) return;
      (ds.data || []).forEach((point) => {
        const y = readDataPointY(point);
        if (Number.isFinite(y)) values.push(y);
      });
    });

    const max = computeAxisMax(values);
    if (max != null) {
      scale.max = max;
      scale.beginAtZero = true;
    }
  });
};

const readPointY = (element) => {
  if (element?.parsed?.y != null && Number.isFinite(element.parsed.y)) {
    return element.parsed.y;
  }
  const raw = element?.$context?.raw;
  if (raw != null && typeof raw === 'object' && raw.y != null) return Number(raw.y);
  if (raw != null && typeof raw !== 'object') return Number(raw);
  return null;
};

/** Target / standard series — no value labels on chart (table still shows them). */
const isStandardSeries = (dataset) => {
  const label = String(dataset?.label || '').trim().toLowerCase();
  return label === 'target'
    || label.startsWith('target ')
    || label.startsWith('target(')
    || label === 'standar'
    || label.startsWith('standar ');
};

const LABEL_OFFSET_PX = 16;
const LABEL_NEAR_LINE_PX = 22;
const LABEL_FONT = '600 9px Inter, system-ui, sans-serif';

const getTargetPixelYAtIndex = (chart, index) => {
  let targetY = null;
  chart.data.datasets.forEach((dataset, di) => {
    if (!isStandardSeries(dataset)) return;
    const meta = chart.getDatasetMeta(di);
    const el = meta?.data?.[index];
    if (el && Number.isFinite(el.y)) targetY = el.y;
  });
  return targetY;
};

const pickLabelY = (pointY, targetPixelY, chartArea) => {
  const top = chartArea.top + 10;
  const bottom = chartArea.bottom - 10;
  let labelY = pointY - LABEL_OFFSET_PX;
  let below = false;

  if (labelY < top) {
    below = true;
    labelY = pointY + LABEL_OFFSET_PX;
  }

  if (targetPixelY != null && Math.abs(pointY - targetPixelY) < LABEL_NEAR_LINE_PX) {
    if (pointY <= targetPixelY) {
      labelY = Math.min(pointY, targetPixelY) - LABEL_OFFSET_PX - 2;
      if (labelY < top) {
        below = true;
        labelY = Math.max(pointY, targetPixelY) + LABEL_OFFSET_PX + 2;
      }
    } else {
      below = true;
      labelY = Math.max(pointY, targetPixelY) + LABEL_OFFSET_PX + 2;
      if (labelY > bottom) {
        below = false;
        labelY = Math.min(pointY, targetPixelY) - LABEL_OFFSET_PX - 2;
      }
    }
  }

  labelY = Math.min(Math.max(labelY, top), bottom);
  return { labelY, below };
};

const drawLabelPill = (ctx, text, x, y, color) => {
  ctx.font = LABEL_FONT;
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  const w = ctx.measureText(text).width;
  const padX = 4;
  const padY = 3;
  const boxW = w + padX * 2;
  const boxH = 14;
  const left = x - boxW / 2;
  const top = y - boxH / 2;

  ctx.fillStyle = 'rgba(255, 255, 255, 0.94)';
  ctx.strokeStyle = 'rgba(148, 163, 184, 0.55)';
  ctx.lineWidth = 0.75;
  if (typeof ctx.roundRect === 'function') {
    ctx.beginPath();
    ctx.roundRect(left, top, boxW, boxH, 3);
    ctx.fill();
    ctx.stroke();
  } else {
    ctx.fillRect(left, top, boxW, boxH);
    ctx.strokeRect(left, top, boxW, boxH);
  }

  ctx.fillStyle = color;
  ctx.fillText(text, x, y);
};

/** Draw value labels on Actual points; smart above/below when near Target line or chart edge. */
export const kpiValueLabelsPlugin = {
  id: 'kpiValueLabels',
  afterDatasetsDraw(chart) {
    if (chart.options?.plugins?.kpiValueLabels === false) return;

    const { ctx, chartArea } = chart;
    if (!chartArea) return;

    chart.data.datasets.forEach((dataset, di) => {
      if (isStandardSeries(dataset)) return;

      const meta = chart.getDatasetMeta(di);
      if (!meta?.data?.length || meta.hidden) return;

      const unit = resolveDatasetUnit(dataset);
      const color = dataset.borderColor || dataset.backgroundColor || '#374151';

      meta.data.forEach((element, index) => {
        if (!element || element.skip) return;

        const y = readPointY(element);
        if (!Number.isFinite(y) || y === 0) return;

        const text = formatKpiChartValue(y, unit);
        if (!text) return;

        const x = element.x;
        if (!Number.isFinite(x)) return;

        const targetPixelY = getTargetPixelYAtIndex(chart, index);
        const { labelY } = pickLabelY(element.y, targetPixelY, chartArea);

        ctx.save();
        drawLabelPill(ctx, text, x, labelY, color);
        ctx.restore();
      });
    });
  },
};

export const formatTooltipDateTitle = (rawLabel, monthLabel) => {
  const parts = String(rawLabel ?? '').split('-');
  if (parts.length === 3) {
    const m = Number.parseInt(parts[0], 10);
    const d = Number.parseInt(parts[1], 10);
    const y = parts[2];
    if (Number.isFinite(m) && Number.isFinite(d)) {
      const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      return `${months[m - 1] || `M${m}`} ${d}, ${y}`;
    }
  }
  return monthLabel ? `${rawLabel} (${monthLabel})` : String(rawLabel ?? '');
};

export const getFixedKpiContentWidth = (dayCount, extraCount = 0, dayColPx) => {
  const days = Math.max(Number(dayCount) || 0, 0);
  const extras = Math.max(Number(extraCount) || 0, 0);
  const colPx = Number(dayColPx) > 0 ? Number(dayColPx) : getKpiDayColPx();
  return KPI_FIELD_COL_PX + days * colPx + extras * KPI_EXTRA_COL_PX;
};

/** X scale: one tick per day index, centered on each 44px column (offset must stay false). */
export const kpiTableXScale = (dayCount) => ({
  type: 'linear',
  min: -0.5,
  max: Math.max(dayCount - 0.5, 0.5),
  offset: false,
  border: { display: false },
  ticks: { display: false, stepSize: 1 },
  grid: {
    display: false,
  },
});

/** Read day-column geometry from the table DOM (relative to chart canvas). */
export const measureTablePlotMetrics = (chart) => {
  const canvas = chart?.canvas;
  const table = document.querySelector('#kpi-chart-table-sync .kpi-chart-table');
  const dayThs = table?.querySelectorAll('thead th.kpi-chart-table__day');
  if (!canvas || !dayThs?.length) return null;

  const canvasRect = canvas.getBoundingClientRect();
  const firstRect = dayThs[0].getBoundingClientRect();
  const lastRect = dayThs[dayThs.length - 1].getBoundingClientRect();
  const dayWidth = firstRect.width;
  if (!dayWidth) return null;

  return {
    plotLeft: firstRect.left - canvasRect.left,
    plotWidth: lastRect.right - firstRect.left,
    dayWidth,
    dayCount: dayThs.length,
  };
};

/** Vertical guide lines aligned to table columns (canvas coordinates). */
export const kpiTableGridPlugin = {
  id: 'kpiTableColumnGrid',
  beforeDatasetsDraw(chart) {
    const m = chart.__kpiTableMetrics || measureTablePlotMetrics(chart);
    if (!m) return;
    chart.__kpiTableMetrics = m;

    const { chartArea } = chart;
    if (!chartArea?.width) return;

    const { ctx } = chart;
    const top = chartArea.top;
    const bottom = chartArea.bottom;
    const { plotLeft, dayWidth, dayCount } = m;

    ctx.save();
    ctx.strokeStyle = 'rgba(148, 163, 184, 0.45)';
    ctx.lineWidth = 1;

    for (let i = 0; i <= dayCount; i += 1) {
      const x = Math.round(plotLeft + i * dayWidth) + 0.5;
      ctx.beginPath();
      ctx.moveTo(x, top);
      ctx.lineTo(x, bottom);
      ctx.stroke();
    }

    ctx.restore();
  },
};

/** Snap line/bar points to table column centers (delta from scale layout, keeps valid y). */
export const kpiSnapPointsPlugin = {
  id: 'kpiSnapPointsToTable',
  afterLayout(chart) {
    const m = chart.__kpiTableMetrics || measureTablePlotMetrics(chart);
    const xScale = chart.scales?.x;
    const { chartArea } = chart;
    if (!m || !xScale || !chartArea?.width) return;

    chart.__kpiTableMetrics = m;
    const dx = m.plotLeft - chartArea.left;

    chart.data.datasets.forEach((_, di) => {
      const meta = chart.getDatasetMeta(di);
      if (!meta?.data?.length || meta.hidden) return;

      meta.data.forEach((point, i) => {
        if (!point || point.skip) return;
        const baseX = xScale.getPixelForValue(i);
        if (!Number.isFinite(baseX)) return;
        point.x = baseX + dx;
      });
    });
  },
};

export const resizeChartToTableWidth = (chart, dayCount, extraCount = 0) => {
  if (!chart?.canvas) return false;

  const totalWidth = getFixedKpiContentWidth(dayCount, extraCount);
  const canvas = chart.canvas;
  const wrap = canvas.closest('.kpi-chart-table-wrap');
  const fromWrapStyle = wrap ? parseInt(wrap.style.minHeight || wrap.style.height || '', 10) : NaN;
  const heightAttr = parseInt(canvas.getAttribute('height') || '', 10);
  let height = Number.isFinite(fromWrapStyle) && fromWrapStyle > 0
    ? fromWrapStyle
    : (Number.isFinite(heightAttr) && heightAttr > 0 ? heightAttr : 300);
  height = Math.max(280, height);

  const chain = [
    canvas.parentElement,
    canvas.closest('.kpi-chart-table-wrap'),
    document.getElementById('kpi-chart-table-sync-inner'),
  ].filter(Boolean);

  chain.forEach((el) => {
    if (el.id === 'kpi-chart-table-sync-inner') {
      el.style.width = `${totalWidth}px`;
      el.style.minWidth = `${totalWidth}px`;
      return;
    }
    el.style.width = `${totalWidth}px`;
    el.style.minWidth = `${totalWidth}px`;
    el.style.maxWidth = `${totalWidth}px`;
  });

  const chartWrap = canvas.closest('.kpi-chart-table-wrap');
  if (chartWrap) {
    if (!chartWrap.style.minHeight) chartWrap.style.minHeight = `${height}px`;
    if (!chartWrap.style.height) chartWrap.style.height = `${height}px`;
    if (!chartWrap.style.maxHeight) chartWrap.style.maxHeight = `${height}px`;
    chartWrap.style.overflow = 'hidden';
    chartWrap.style.boxSizing = 'border-box';
  }

  canvas.style.width = `${totalWidth}px`;
  canvas.style.height = `${height}px`;
  canvas.style.minHeight = `${height}px`;
  canvas.style.maxWidth = 'none';
  canvas.style.display = 'block';

  chart.__kpiDayCount = dayCount;
  chart.resize(totalWidth, height);

  if (chart.chartArea && chart.chartArea.bottom - chart.chartArea.top < 90) {
    const nextH = Math.min(320, height + 24);
    chartWrap.style.height = `${nextH}px`;
    chartWrap.style.minHeight = `${nextH}px`;
    chartWrap.style.maxHeight = `${nextH}px`;
    canvas.style.height = `${nextH}px`;
    canvas.setAttribute('height', String(nextH));
    chart.resize(totalWidth, nextH);
  }
  return true;
};

/** Map series values to { x: dayIndex, y } for linear x-axis (handles time-scale {x,y} points). */
export const seriesToIndexedPoints = (data) => {
  if (!Array.isArray(data)) return data;

  return data.map((point, index) => {
    let y = point;

    if (point != null && typeof point === 'object' && !Array.isArray(point)) {
      if (Object.prototype.hasOwnProperty.call(point, 'y')) {
        y = point.y;
      } else if (Object.prototype.hasOwnProperty.call(point, 'value')) {
        y = point.value;
      } else {
        return { x: index, y: null };
      }
    }

    if (y === null || y === undefined || y === '') return { x: index, y: null };

    const num = Number(y);
    return { x: index, y: Number.isFinite(num) ? num : null };
  });
};

const preserveRawLabels = (chart) => {
  if (chart.__kpiRawDateLabels?.length) return;
  const raw = window.kpiActualTargetChartData?.labels || [];
  chart.__kpiRawDateLabels = Array.isArray(raw) ? [...raw] : [];
};

const cloneDataset = (ds) => ({
  label: ds.label,
  data: Array.isArray(ds.data) ? [...ds.data] : ds.data,
  type: ds.type,
  borderColor: ds.borderColor,
  backgroundColor: ds.backgroundColor,
  fill: ds.fill,
  borderWidth: ds.borderWidth,
  pointRadius: ds.pointRadius,
  pointHoverRadius: ds.pointHoverRadius,
  pointBackgroundColor: ds.pointBackgroundColor,
  pointHoverBackgroundColor: ds.pointHoverBackgroundColor,
  tension: ds.tension,
  clip: ds.clip,
  yAxisID: ds.yAxisID,
  stack: ds.stack,
  borderDash: ds.borderDash,
  showLine: ds.type === 'bar' ? ds.showLine : (ds.showLine !== false),
  spanGaps: ds.spanGaps ?? (ds.type === 'line' || ds.showLine !== false),
});

/** Waste CNC Bending: connect points with lines (legacy app bundle used showLine: false). */
export const applyCncWasteLineStyle = (datasets) => {
  const isCnc = window.kpiActualTargetChartMeta?.template_code === 'TPL_PD_WASTE_CNC_BENDING';
  if (!isCnc || !Array.isArray(datasets)) return datasets;

  return datasets.map((ds) => {
    const label = String(ds.label || '');
    if (!/total waste/i.test(label)) return ds;

    return {
      ...ds,
      type: ds.type || 'line',
      showLine: true,
      spanGaps: true,
      borderWidth: Number(ds.borderWidth) > 0 ? ds.borderWidth : 2,
      tension: ds.tension ?? 0.2,
      pointRadius: ds.pointRadius ?? 3,
    };
  });
};

/** Y tick labels sit in the left strip (outside plot, left of day columns). */
export const kpiYAxisTickAlign = (position = 'left') => (
  position === 'right' ? 'inner' : 'outer'
);

/** Keep Y-axis HTML chrome + padding on every chart.update(). */
export const patchKpiChartUpdate = (chart) => {
  if (!chart?.update || chart.__kpiUpdatePatched) return;

  const origUpdate = chart.update.bind(chart);
  chart.update = function patchedKpiUpdate(mode, ...args) {
    if (this.canvas?.id === 'kpi-actual-target-chart') {
      applyYAxisTickLayout(this);
    }
    return origUpdate(mode, ...args);
  };
  chart.__kpiUpdatePatched = true;
};

export const applyYAxisTickLayout = (chart) => {
  if (!chart?.options?.scales) return;

  if (!chart.options.layout) chart.options.layout = {};
  if (!chart.options.layout.padding) chart.options.layout.padding = {};
  chart.options.layout.padding.left = KPI_CHART_PAD_LEFT;

  Object.keys(chart.options.scales).forEach((axisId) => {
    if (axisId === 'x') return;
    const scale = chart.options.scales[axisId];
    if (!scale) return;

    delete scale.afterFit;

    if (!scale.ticks) scale.ticks = {};
    scale.position = scale.position || 'left';
    scale.ticks.align = kpiYAxisTickAlign(scale.position);
    scale.ticks.crossAlign = scale.position === 'right' ? 'near' : 'far';
    scale.ticks.padding = 2;
    scale.ticks.maxRotation = 0;
    scale.ticks.autoSkip = true;
    scale.ticks.precision = 0;
    if (!scale.ticks.font || typeof scale.ticks.font !== 'object') scale.ticks.font = {};
    scale.ticks.font.size = 10;

    if (!scale.grid) scale.grid = {};
    scale.grid.drawBorder = false;

    if (scale.title) scale.title.display = false;
  });

  applyKpiHtmlChrome(chart);
};

/** In-place only — never replace plugins/tooltip (avoids Chart.js proxy recursion). */
export const applyKpiTooltipLayout = (chart) => {
  if (!chart?.options) return;

  if (!chart.options.interaction) chart.options.interaction = {};
  chart.options.interaction.mode = 'index';
  chart.options.interaction.intersect = false;
  chart.options.interaction.axis = 'x';

  const tip = chart.options.plugins?.tooltip;
  if (!tip) return;

  tip.mode = 'index';
  tip.intersect = false;
  tip.position = 'nearest';
  if (typeof tip.axis !== 'string') delete tip.axis;
  if (!tip.animation || typeof tip.animation !== 'object') tip.animation = {};
  tip.animation.duration = 0;
  tip.caretPadding = 8;
  tip.padding = 10;
  if (typeof tip.xAlign === 'function') tip.xAlign = 'center';
  if (typeof tip.xAlign !== 'string') tip.xAlign = 'center';
  if (typeof tip.yAlign !== 'string') tip.yAlign = 'bottom';
};

const buildYScaleConfig = (scale) => {
  if (!scale) return null;
  const cfg = {
    type: scale.type,
    position: scale.position,
    beginAtZero: scale.beginAtZero,
    stacked: scale.stacked,
    min: scale.min,
    // max omitted — set by applySensibleYScale from real data
    border: { display: scale.border?.display ?? false },
    grid: {
      color: scale.grid?.color,
      display: scale.grid?.display,
      drawOnChartArea: scale.grid?.drawOnChartArea,
    },
    ticks: {
      maxTicksLimit: scale.ticks?.maxTicksLimit,
      color: scale.ticks?.color,
      callback: scale.ticks?.callback,
      align: kpiYAxisTickAlign(scale.position),
      crossAlign: scale.position === 'right' ? 'near' : 'far',
      padding: 2,
      maxRotation: 0,
      autoSkip: scale.ticks?.autoSkip ?? true,
      precision: 0,
    },
  };
  if (scale.title) {
    cfg.title = {
      display: false,
      text: scale.title.text,
      color: scale.title.color,
    };
  }
  return cfg;
};

const pickColor = (value) => (typeof value === 'string' ? value : undefined);

const buildPluginsConfig = (oldPlugins, rawDates, monthLabel, tooltipCallbacks = {}) => {
  if (!oldPlugins) {
    return {
      tooltip: {
        enabled: true,
        mode: 'index',
        intersect: false,
        position: 'nearest',
        animation: { duration: 0 },
        xAlign: 'center',
        yAlign: 'bottom',
        callbacks: {
          title: (items) => {
            const idx = items?.[0]?.dataIndex;
            return formatTooltipDateTitle(rawDates?.[idx], monthLabel);
          },
        },
      },
    };
  }

  const oldTip = oldPlugins.tooltip || {};
  const callbacks = {
    title: (items) => {
      const idx = items?.[0]?.dataIndex;
      return formatTooltipDateTitle(rawDates?.[idx], monthLabel);
    },
  };
  if (typeof tooltipCallbacks.label === 'function') {
    callbacks.label = tooltipCallbacks.label;
  }

  const out = {
    tooltip: {
      enabled: true,
      mode: 'index',
      intersect: false,
      position: 'nearest',
      animation: { duration: 0 },
      caretPadding: 8,
      padding: 10,
      displayColors: true,
      xAlign: 'center',
      yAlign: 'bottom',
      titleColor: pickColor(oldTip.titleColor),
      bodyColor: pickColor(oldTip.bodyColor),
      backgroundColor: pickColor(oldTip.backgroundColor),
      borderColor: pickColor(oldTip.borderColor),
      borderWidth: oldTip.borderWidth ?? 1,
      callbacks,
    },
  };

  if (oldPlugins.title) {
    const meta = window.kpiActualTargetChartMeta || {};
    const templateTitle = meta.template_title || 'All Templates';
    const monthLabel = meta.month_label || '';
    const titleText = monthLabel ? [templateTitle, monthLabel] : [templateTitle];

    out.title = {
      display: false,
      text: titleText,
    };
  }

  if (oldPlugins.legend) {
    out.legend = {
      display: oldPlugins.legend.display !== false,
      position: 'bottom',
      align: 'start',
      labels: {
        color: pickColor(oldPlugins.legend.labels?.color),
        boxWidth: 14,
        padding: 10,
      },
    };
  }

  return out;
};

/**
 * Recreate chart with category x-axis (labels from table) so points line up with day columns.
 */
export const recreateChartWithTableAxis = (chart, table, extraCount = 0) => {
  if (!chart?.canvas) return false;

  const xType = chart.options?.scales?.x?.type;
  if (chart.__kpiTableAxisDone && xType !== 'time') return false;

  const dayLabels = getDayLabelsFromTable(table);
  if (!dayLabels.length) return false;

    const ChartCtor = chart.constructor;
    if (!ChartCtor) return false;

    try {
      preserveRawLabels(chart);
      const rawDates = chart.__kpiRawDateLabels || [];
      const monthLabel = window.kpiActualTargetChartMeta?.month_label || '';

    const oldConfig = chart.config;
    const oldPlugins = oldConfig.options?.plugins;
    const oldTooltipCallbacks = oldPlugins?.tooltip?.callbacks || {};
    const oldScales = oldConfig.options?.scales || {};
    const scales = {};

    Object.keys(oldScales).forEach((id) => {
      if (id === 'x') return;
      scales[id] = buildYScaleConfig(oldScales[id]);
    });

    scales.x = kpiTableXScale(dayLabels.length);

    const padding = oldConfig.options?.layout?.padding || {};
    const datasets = applyCncWasteLineStyle(chart.data.datasets.map((ds) => ({
      ...cloneDataset(ds),
      data: seriesToIndexedPoints(ds.data),
    })));
    const ctx = chart.canvas;
    const dayCount = dayLabels.length;

    chart.destroy();

    [kpiTableGridPlugin, kpiSnapPointsPlugin, kpiValueLabelsPlugin, kpiHtmlChromePlugin].forEach((plugin) => {
      if (!ChartCtor.registry?.plugins?.get(plugin.id)) {
        ChartCtor.register(plugin);
      }
    });

    const newChart = new ChartCtor(ctx, {
      type: oldConfig.type,
      data: { labels: dayLabels, datasets },
      options: {
        layout: {
          padding: {
            top: Math.max(padding.top ?? 6, KPI_PLOT_PAD_TOP),
            bottom: padding.bottom ?? 2,
            left: KPI_CHART_PAD_LEFT,
            right: kpiChartPadRight(extraCount),
          },
        },
        scales,
        interaction: {
          mode: 'index',
          intersect: false,
          axis: 'x',
        },
        plugins: {
          ...buildPluginsConfig(oldPlugins, rawDates, monthLabel, oldTooltipCallbacks),
          kpiTableColumnGrid: true,
          kpiSnapPointsToTable: true,
          kpiValueLabels: true,
          kpiHtmlChrome: true,
        },
        responsive: false,
        maintainAspectRatio: false,
      },
    });

    newChart.__kpiTableAxisDone = true;
    newChart.__kpiRawDateLabels = rawDates;
    newChart.__kpiDayCount = dayCount;
    window.kpiActualTargetChartInstance = newChart;
    resizeChartToTableWidth(newChart, dayCount, extraCount);
    applySensibleYScale(newChart);
    applyYAxisTickLayout(newChart);
    applyKpiTooltipLayout(newChart);
    newChart.__kpiTableMetrics = measureTablePlotMetrics(newChart);
    return true;
  } catch (err) {
    console.warn('[kpi-chart-sync] chart recreate failed:', err);
    return false;
  }
};

/**
 * Fallback when category recreate fails: hide chart x labels and align padding to table.
 */
export const applyChartPaddingFromTable = (chart, extraCount = 0) => {
  if (!chart?.options) return false;

  try {
    const x = chart.options.scales?.x;
    if (x?.ticks) {
      x.ticks.display = false;
      x.ticks.autoSkip = false;
    }
    if (!chart.options.layout) chart.options.layout = {};
    if (!chart.options.layout.padding) chart.options.layout.padding = {};
    chart.options.layout.padding.left = KPI_CHART_PAD_LEFT;
    chart.options.layout.padding.right = kpiChartPadRight(extraCount);

    const dayCount = chart.data?.labels?.length
      || window.kpiActualTargetChartData?.labels?.length
      || 0;
    resizeChartToTableWidth(chart, dayCount, extraCount);
    applySensibleYScale(chart);
    applyYAxisTickLayout(chart);
    applyKpiTooltipLayout(chart);
    chart.__kpiTableMetrics = measureTablePlotMetrics(chart);
    return true;
  } catch (err) {
    console.warn('[kpi-chart-sync] padding apply skipped:', err);
    return false;
  }
};
