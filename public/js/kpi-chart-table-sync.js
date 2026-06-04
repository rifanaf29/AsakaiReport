(() => {
  // resources/js/components/kpi-number-format.js
  var normalizeUnit = (unit) => String(unit ?? "").trim().toLowerCase();
  var PCS_FIELD_KEYS = /* @__PURE__ */ new Set([
    "actual_produksi",
    "actual_ng",
    "order_pcs",
    "shortage_pcs"
  ]);
  var resolveKpiFormatKind = ({ unit = null, rowLabel = null, fieldKey = null } = {}) => {
    const u = normalizeUnit(unit);
    const label = String(rowLabel ?? "").toLowerCase();
    const fk = String(fieldKey ?? "").toLowerCase();
    if (u === "ppm" || label.includes("(ppm)") || /\bppm\b/.test(label)) return "ppm";
    if (u === "%" || label.includes("(%)")) return "percent";
    if (u === "kg" || label.includes("(kg)") || label.includes("gram")) return "default";
    if (u === "pcs" || u === "pc" || label.includes("(pcs)") || label.includes("(pc)")) return "pcs";
    if (fk && (fk.endsWith("_pcs") || fk.endsWith("_ng") || PCS_FIELD_KEYS.has(fk))) return "pcs";
    return "default";
  };
  var formatPcsInteger = (n) => new Intl.NumberFormat("id-ID", {
    useGrouping: true,
    maximumFractionDigits: 0,
    minimumFractionDigits: 0
  }).format(Math.round(n));
  var formatKpiNumber = (value, options = {}) => {
    if (value === null || value === void 0 || value === "") return "";
    const n = Number(value);
    if (!Number.isFinite(n)) return String(value);
    const opts = typeof options === "string" ? { unit: options } : options;
    const kind = resolveKpiFormatKind(opts);
    switch (kind) {
      case "ppm":
        return formatPcsInteger(n);
      case "pcs":
        return formatPcsInteger(n);
      case "percent":
        return new Intl.NumberFormat("id-ID", {
          minimumFractionDigits: 0,
          maximumFractionDigits: 1
        }).format(n);
      default: {
        let formatted = new Intl.NumberFormat("id-ID", { maximumFractionDigits: 2 }).format(n);
        formatted = formatted.replace(/,00$/, "").replace(/,(\d)0$/, ",$1");
        return formatted;
      }
    }
  };

  // resources/js/components/kpi-chart-html-chrome.js
  var KPI_HTML_Y_AXIS_WIDTH = 132;
  var KPI_HTML_TITLE_TOP_PX = 32;
  var KPI_PLOT_PAD_TOP = KPI_HTML_TITLE_TOP_PX + 4;
  var escapeHtml = (value) => String(value ?? "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
  var formatTickValue = (value, unit) => {
    const formatted = formatKpiNumber(value, { unit });
    if (!formatted) return "";
    if (!unit) return formatted;
    if (unit === "%") return `${formatted}%`;
    return `${formatted} ${unit}`;
  };
  var resolveYAxisUnit = (chart, scaleId) => {
    const meta = window.kpiActualTargetChartMeta || {};
    if (scaleId === "yKg") return "kg";
    if (scaleId === "yPct") return "%";
    if (scaleId === "yRp") return "Rp";
    if (scaleId === "yMp") return "Orang";
    return meta.unit || "";
  };
  var ensureHtmlChromeHosts = (chart) => {
    const canvas = chart?.canvas;
    const wrap = canvas?.closest(".kpi-chart-table-wrap");
    if (!wrap || !canvas) return null;
    let yHost = wrap.querySelector(".kpi-html-y-axis");
    if (!yHost) {
      yHost = document.createElement("div");
      yHost.className = "kpi-html-y-axis";
      yHost.setAttribute("aria-hidden", "true");
      wrap.insertBefore(yHost, canvas);
    }
    let titleHost = wrap.querySelector(".kpi-html-chart-title");
    if (!titleHost) {
      titleHost = document.createElement("div");
      titleHost.className = "kpi-html-chart-title";
      titleHost.setAttribute("aria-hidden", "true");
      wrap.insertBefore(titleHost, canvas);
    }
    let legHost = wrap.querySelector(".kpi-html-legend");
    if (!legHost) {
      legHost = document.createElement("div");
      legHost.className = "kpi-html-legend";
      legHost.setAttribute("aria-hidden", "true");
      wrap.appendChild(legHost);
    }
    return { wrap, yHost, titleHost, legHost, canvas };
  };
  var renderHtmlChartTitle = (chart) => {
    const hosts = ensureHtmlChromeHosts(chart);
    if (!hosts?.titleHost) return;
    const meta = window.kpiActualTargetChartMeta || {};
    const templateTitle = String(meta.template_title || "All Templates").trim();
    const monthLabel = String(meta.month_label || "").trim();
    const text = monthLabel ? `${templateTitle}, ${monthLabel}` : templateTitle;
    hosts.titleHost.innerHTML = text ? `<div class="kpi-html-chart-title__text">${escapeHtml(text)}</div>` : "";
  };
  var renderHtmlYAxisLabels = (chart) => {
    const hosts = ensureHtmlChromeHosts(chart);
    if (!hosts) return;
    const { yHost, canvas } = hosts;
    const { chartArea } = chart;
    if (!chartArea?.height) {
      yHost.innerHTML = "";
      return;
    }
    const scaleId = Object.keys(chart.scales).find((id) => id !== "x" && chart.scales[id]?.position === "left") || Object.keys(chart.scales).find((k) => k !== "x");
    const scale = scaleId ? chart.scales[scaleId] : null;
    if (!scale?.ticks?.length) {
      yHost.innerHTML = "";
      return;
    }
    const unit = resolveYAxisUnit(chart, scaleId);
    const h = canvas.offsetHeight || chart.height;
    yHost.style.width = `${KPI_HTML_Y_AXIS_WIDTH}px`;
    yHost.style.height = `${h}px`;
    yHost.style.top = `${canvas.offsetTop}px`;
    yHost.style.left = "0";
    const parts = [];
    scale.ticks.forEach((tick, i) => {
      const py = scale.getPixelForTick(i);
      if (!Number.isFinite(py)) return;
      const label = formatTickValue(tick.value, unit);
      if (!label) return;
      parts.push(
        `<span class="kpi-html-y-axis__tick" style="top:${Math.round(py)}px">${label}</span>`
      );
    });
    yHost.innerHTML = parts.join("");
  };
  var renderHtmlLegend = (chart) => {
    const hosts = ensureHtmlChromeHosts(chart);
    if (!hosts) return;
    const { legHost } = hosts;
    const datasets = chart.data?.datasets || [];
    if (!datasets.length) {
      legHost.innerHTML = "";
      return;
    }
    legHost.innerHTML = datasets.map((ds) => {
      const color = ds.borderColor || ds.backgroundColor || "#6b7280";
      const label = String(ds.label || "").trim();
      return `<span class="kpi-html-legend__item"><span class="kpi-html-legend__box" style="border-color:${color};background:${typeof ds.backgroundColor === "string" && ds.backgroundColor.includes("rgba") ? ds.backgroundColor : "transparent"}"></span><span class="kpi-html-legend__text">${label}</span></span>`;
    }).join("");
  };
  var applyKpiHtmlChrome = (chart) => {
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
      KPI_PLOT_PAD_TOP
    );
    Object.keys(chart.options.scales || {}).forEach((axisId) => {
      if (axisId === "x") return;
      const scale = chart.options.scales[axisId];
      if (!scale?.ticks) scale.ticks = {};
      if (scale.position === "left" || axisId === "y") {
        scale.ticks.display = false;
      }
    });
    renderHtmlChartTitle(chart);
    renderHtmlYAxisLabels(chart);
    renderHtmlLegend(chart);
  };
  var kpiHtmlChromePlugin = {
    id: "kpiHtmlChrome",
    afterDraw(chart) {
      if (chart.options?.plugins?.kpiHtmlChrome === false) return;
      if (chart.canvas?.id !== "kpi-actual-target-chart") return;
      applyKpiHtmlChrome(chart);
    }
  };

  // resources/js/components/kpi-chart-axis.js
  var KPI_FIELD_COL_PX = 140;
  var KPI_Y_AXIS_STRIP_PX = KPI_FIELD_COL_PX;
  var KPI_DAY_COL_PX = 44;
  var getKpiDayColPx = () => {
    const n = Number(window.kpiTableDayColPx);
    return Number.isFinite(n) && n > 0 ? n : KPI_DAY_COL_PX;
  };
  var KPI_EXTRA_COL_PX = 72;
  var KPI_CHART_PAD_LEFT = KPI_Y_AXIS_STRIP_PX;
  var kpiChartPadRight = (extraCount = 0) => Math.max(Number(extraCount) || 0, 0) * KPI_EXTRA_COL_PX;
  var getDayLabelsFromTable = (table) => {
    if (!table) return [];
    const dayHeaders = table.querySelectorAll("thead tr th.kpi-chart-table__day");
    if (dayHeaders.length) {
      return Array.from(dayHeaders).map((th) => (th.textContent || "").replace(/\s+/g, "").trim());
    }
    const headerRow = table.querySelector("thead tr");
    if (!headerRow) return [];
    return Array.from(headerRow.querySelectorAll("th")).slice(1).filter((th) => {
      const text = (th.textContent || "").trim().toLowerCase();
      return text !== "total" && !text.startsWith("average");
    }).map((th) => (th.textContent || "").replace(/\s+/g, "").trim());
  };
  var formatKpiChartValue = (value, unit) => {
    if (value === null || value === void 0 || value === "") return null;
    const formatted = formatKpiNumber(value, { unit });
    if (!formatted) return null;
    if (!unit) return formatted;
    if (unit === "%") return `${formatted}%`;
    return `${formatted} ${unit}`;
  };
  var resolveDatasetUnit = (dataset) => {
    const axis = dataset?.yAxisID;
    if (axis === "yKg") return "kg";
    if (axis === "yPct") return "%";
    if (axis === "yMp") return "Orang";
    if (axis === "yRp") return "Rp";
    return window.kpiActualTargetChartMeta?.unit ?? null;
  };
  var readDataPointY = (point) => {
    if (point == null) return null;
    if (typeof point === "object" && point.y != null) return Number(point.y);
    return Number(point);
  };
  var niceCeil = (value) => {
    if (!Number.isFinite(value) || value <= 0) return 10;
    const exp = Math.pow(10, Math.floor(Math.log10(value)));
    const n = value / exp;
    const nice = n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10;
    return nice * exp * 1.1;
  };
  var computeAxisMax = (values) => {
    const nums = values.filter((v) => Number.isFinite(v) && v >= 0);
    if (!nums.length) return void 0;
    const rawMax = Math.max(...nums);
    if (rawMax <= 0) return niceCeil(10);
    return niceCeil(rawMax);
  };
  var applySensibleYScale = (chart) => {
    if (!chart?.options?.scales || !chart.data?.datasets) return;
    Object.keys(chart.options.scales).forEach((axisId) => {
      if (axisId === "x") return;
      const scale = chart.options.scales[axisId];
      if (axisId === "yPct" && scale.max != null) return;
      const axisKey = axisId === "y" ? "y" : axisId;
      const values = [];
      chart.data.datasets.forEach((ds) => {
        if ((ds.yAxisID || "y") !== axisKey) return;
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
  var readPointY = (element) => {
    if (element?.parsed?.y != null && Number.isFinite(element.parsed.y)) {
      return element.parsed.y;
    }
    const raw = element?.$context?.raw;
    if (raw != null && typeof raw === "object" && raw.y != null) return Number(raw.y);
    if (raw != null && typeof raw !== "object") return Number(raw);
    return null;
  };
  var isStandardSeries = (dataset) => {
    const label = String(dataset?.label || "").trim().toLowerCase();
    return label === "target" || label.startsWith("target ") || label.startsWith("target(") || label === "standar" || label.startsWith("standar ");
  };
  var LABEL_OFFSET_PX = 16;
  var LABEL_NEAR_LINE_PX = 22;
  var LABEL_FONT = "600 9px Inter, system-ui, sans-serif";
  var getTargetPixelYAtIndex = (chart, index) => {
    let targetY = null;
    chart.data.datasets.forEach((dataset, di) => {
      if (!isStandardSeries(dataset)) return;
      const meta = chart.getDatasetMeta(di);
      const el = meta?.data?.[index];
      if (el && Number.isFinite(el.y)) targetY = el.y;
    });
    return targetY;
  };
  var pickLabelY = (pointY, targetPixelY, chartArea) => {
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
  var drawLabelPill = (ctx, text, x, y, color) => {
    ctx.font = LABEL_FONT;
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    const w = ctx.measureText(text).width;
    const padX = 4;
    const padY = 3;
    const boxW = w + padX * 2;
    const boxH = 14;
    const left = x - boxW / 2;
    const top = y - boxH / 2;
    ctx.fillStyle = "rgba(255, 255, 255, 0.94)";
    ctx.strokeStyle = "rgba(148, 163, 184, 0.55)";
    ctx.lineWidth = 0.75;
    if (typeof ctx.roundRect === "function") {
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
  var kpiValueLabelsPlugin = {
    id: "kpiValueLabels",
    afterDatasetsDraw(chart) {
      if (chart.options?.plugins?.kpiValueLabels === false) return;
      const { ctx, chartArea } = chart;
      if (!chartArea) return;
      chart.data.datasets.forEach((dataset, di) => {
        if (isStandardSeries(dataset)) return;
        const meta = chart.getDatasetMeta(di);
        if (!meta?.data?.length || meta.hidden) return;
        const unit = resolveDatasetUnit(dataset);
        const color = dataset.borderColor || dataset.backgroundColor || "#374151";
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
    }
  };
  var formatTooltipDateTitle = (rawLabel, monthLabel) => {
    const parts = String(rawLabel ?? "").split("-");
    if (parts.length === 3) {
      const m = Number.parseInt(parts[0], 10);
      const d = Number.parseInt(parts[1], 10);
      const y = parts[2];
      if (Number.isFinite(m) && Number.isFinite(d)) {
        const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        return `${months[m - 1] || `M${m}`} ${d}, ${y}`;
      }
    }
    return monthLabel ? `${rawLabel} (${monthLabel})` : String(rawLabel ?? "");
  };
  var getFixedKpiContentWidth = (dayCount, extraCount = 0, dayColPx) => {
    const days = Math.max(Number(dayCount) || 0, 0);
    const extras = Math.max(Number(extraCount) || 0, 0);
    const colPx = Number(dayColPx) > 0 ? Number(dayColPx) : getKpiDayColPx();
    return KPI_FIELD_COL_PX + days * colPx + extras * KPI_EXTRA_COL_PX;
  };
  var kpiTableXScale = (dayCount) => ({
    type: "linear",
    min: -0.5,
    max: Math.max(dayCount - 0.5, 0.5),
    offset: false,
    border: { display: false },
    ticks: { display: false, stepSize: 1 },
    grid: {
      display: false
    }
  });
  var measureTablePlotMetrics = (chart) => {
    const canvas = chart?.canvas;
    const table = document.querySelector("#kpi-chart-table-sync .kpi-chart-table");
    const dayThs = table?.querySelectorAll("thead th.kpi-chart-table__day");
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
      dayCount: dayThs.length
    };
  };
  var kpiTableGridPlugin = {
    id: "kpiTableColumnGrid",
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
      ctx.strokeStyle = "rgba(148, 163, 184, 0.45)";
      ctx.lineWidth = 1;
      for (let i = 0; i <= dayCount; i += 1) {
        const x = Math.round(plotLeft + i * dayWidth) + 0.5;
        ctx.beginPath();
        ctx.moveTo(x, top);
        ctx.lineTo(x, bottom);
        ctx.stroke();
      }
      ctx.restore();
    }
  };
  var kpiSnapPointsPlugin = {
    id: "kpiSnapPointsToTable",
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
    }
  };
  var resizeChartToTableWidth = (chart, dayCount, extraCount = 0) => {
    if (!chart?.canvas) return false;
    const totalWidth = getFixedKpiContentWidth(dayCount, extraCount);
    const canvas = chart.canvas;
    const wrap = canvas.closest(".kpi-chart-table-wrap");
    const fromWrapStyle = wrap ? parseInt(wrap.style.minHeight || wrap.style.height || "", 10) : NaN;
    const heightAttr = parseInt(canvas.getAttribute("height") || "", 10);
    let height = Number.isFinite(fromWrapStyle) && fromWrapStyle > 0 ? fromWrapStyle : Number.isFinite(heightAttr) && heightAttr > 0 ? heightAttr : 300;
    height = Math.max(280, height);
    const chain = [
      canvas.parentElement,
      canvas.closest(".kpi-chart-table-wrap"),
      document.getElementById("kpi-chart-table-sync-inner")
    ].filter(Boolean);
    chain.forEach((el) => {
      if (el.id === "kpi-chart-table-sync-inner") {
        el.style.width = `${totalWidth}px`;
        el.style.minWidth = `${totalWidth}px`;
        return;
      }
      el.style.width = `${totalWidth}px`;
      el.style.minWidth = `${totalWidth}px`;
      el.style.maxWidth = `${totalWidth}px`;
    });
    const chartWrap = canvas.closest(".kpi-chart-table-wrap");
    if (chartWrap) {
      if (!chartWrap.style.minHeight) chartWrap.style.minHeight = `${height}px`;
      if (!chartWrap.style.height) chartWrap.style.height = `${height}px`;
      if (!chartWrap.style.maxHeight) chartWrap.style.maxHeight = `${height}px`;
      chartWrap.style.overflow = "hidden";
      chartWrap.style.boxSizing = "border-box";
    }
    canvas.style.width = `${totalWidth}px`;
    canvas.style.height = `${height}px`;
    canvas.style.minHeight = `${height}px`;
    canvas.style.maxWidth = "none";
    canvas.style.display = "block";
    chart.__kpiDayCount = dayCount;
    chart.resize(totalWidth, height);
    if (chart.chartArea && chart.chartArea.bottom - chart.chartArea.top < 90) {
      const nextH = Math.min(320, height + 24);
      chartWrap.style.height = `${nextH}px`;
      chartWrap.style.minHeight = `${nextH}px`;
      chartWrap.style.maxHeight = `${nextH}px`;
      canvas.style.height = `${nextH}px`;
      canvas.setAttribute("height", String(nextH));
      chart.resize(totalWidth, nextH);
    }
    return true;
  };
  var seriesToIndexedPoints = (data) => {
    if (!Array.isArray(data)) return data;
    return data.map((point, index) => {
      let y = point;
      if (point != null && typeof point === "object" && !Array.isArray(point)) {
        if (Object.prototype.hasOwnProperty.call(point, "y")) {
          y = point.y;
        } else if (Object.prototype.hasOwnProperty.call(point, "value")) {
          y = point.value;
        } else {
          return { x: index, y: null };
        }
      }
      if (y === null || y === void 0 || y === "") return { x: index, y: null };
      const num = Number(y);
      return { x: index, y: Number.isFinite(num) ? num : null };
    });
  };
  var preserveRawLabels = (chart) => {
    if (chart.__kpiRawDateLabels?.length) return;
    const raw = window.kpiActualTargetChartData?.labels || [];
    chart.__kpiRawDateLabels = Array.isArray(raw) ? [...raw] : [];
  };
  var cloneDataset = (ds) => ({
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
    showLine: ds.type === "bar" ? ds.showLine : ds.showLine !== false,
    spanGaps: ds.spanGaps ?? (ds.type === "line" || ds.showLine !== false)
  });
  var applyCncWasteLineStyle = (datasets) => {
    const isCnc = window.kpiActualTargetChartMeta?.template_code === "TPL_PD_WASTE_CNC_BENDING";
    if (!isCnc || !Array.isArray(datasets)) return datasets;
    return datasets.map((ds) => {
      const label = String(ds.label || "");
      if (!/total waste/i.test(label)) return ds;
      return {
        ...ds,
        type: ds.type || "line",
        showLine: true,
        spanGaps: true,
        borderWidth: Number(ds.borderWidth) > 0 ? ds.borderWidth : 2,
        tension: ds.tension ?? 0.2,
        pointRadius: ds.pointRadius ?? 3
      };
    });
  };
  var kpiYAxisTickAlign = (position = "left") => position === "right" ? "inner" : "outer";
  var patchKpiChartUpdate = (chart) => {
    if (!chart?.update || chart.__kpiUpdatePatched) return;
    const origUpdate = chart.update.bind(chart);
    chart.update = function patchedKpiUpdate(mode, ...args) {
      if (this.canvas?.id === "kpi-actual-target-chart") {
        applyYAxisTickLayout(this);
      }
      return origUpdate(mode, ...args);
    };
    chart.__kpiUpdatePatched = true;
  };
  var applyYAxisTickLayout = (chart) => {
    if (!chart?.options?.scales) return;
    if (!chart.options.layout) chart.options.layout = {};
    if (!chart.options.layout.padding) chart.options.layout.padding = {};
    chart.options.layout.padding.left = KPI_CHART_PAD_LEFT;
    Object.keys(chart.options.scales).forEach((axisId) => {
      if (axisId === "x") return;
      const scale = chart.options.scales[axisId];
      if (!scale) return;
      delete scale.afterFit;
      if (!scale.ticks) scale.ticks = {};
      scale.position = scale.position || "left";
      scale.ticks.align = kpiYAxisTickAlign(scale.position);
      scale.ticks.crossAlign = scale.position === "right" ? "near" : "far";
      scale.ticks.padding = 2;
      scale.ticks.maxRotation = 0;
      scale.ticks.autoSkip = true;
      if (!scale.ticks.font || typeof scale.ticks.font !== "object") scale.ticks.font = {};
      scale.ticks.font.size = 10;
      if (!scale.grid) scale.grid = {};
      scale.grid.drawBorder = false;
      if (scale.title) scale.title.display = false;
    });
    applyKpiHtmlChrome(chart);
  };
  var applyKpiTooltipLayout = (chart) => {
    if (!chart?.options) return;
    if (!chart.options.interaction) chart.options.interaction = {};
    chart.options.interaction.mode = "index";
    chart.options.interaction.intersect = false;
    chart.options.interaction.axis = "x";
    const tip = chart.options.plugins?.tooltip;
    if (!tip) return;
    tip.mode = "index";
    tip.intersect = false;
    tip.position = "nearest";
    if (typeof tip.axis !== "string") delete tip.axis;
    if (!tip.animation || typeof tip.animation !== "object") tip.animation = {};
    tip.animation.duration = 0;
    tip.caretPadding = 8;
    tip.padding = 10;
    if (typeof tip.xAlign === "function") tip.xAlign = "center";
    if (typeof tip.xAlign !== "string") tip.xAlign = "center";
    if (typeof tip.yAlign !== "string") tip.yAlign = "bottom";
  };
  var buildYScaleConfig = (scale) => {
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
        drawOnChartArea: scale.grid?.drawOnChartArea
      },
      ticks: {
        maxTicksLimit: scale.ticks?.maxTicksLimit,
        color: scale.ticks?.color,
        callback: scale.ticks?.callback,
        align: kpiYAxisTickAlign(scale.position),
        crossAlign: scale.position === "right" ? "near" : "far",
        padding: 2,
        maxRotation: 0,
        autoSkip: scale.ticks?.autoSkip ?? true
      }
    };
    if (scale.title) {
      cfg.title = {
        display: false,
        text: scale.title.text,
        color: scale.title.color
      };
    }
    return cfg;
  };
  var pickColor = (value) => typeof value === "string" ? value : void 0;
  var buildPluginsConfig = (oldPlugins, rawDates, monthLabel, tooltipCallbacks = {}) => {
    if (!oldPlugins) {
      return {
        tooltip: {
          enabled: true,
          mode: "index",
          intersect: false,
          position: "nearest",
          animation: { duration: 0 },
          xAlign: "center",
          yAlign: "bottom",
          callbacks: {
            title: (items) => {
              const idx = items?.[0]?.dataIndex;
              return formatTooltipDateTitle(rawDates?.[idx], monthLabel);
            }
          }
        }
      };
    }
    const oldTip = oldPlugins.tooltip || {};
    const callbacks = {
      title: (items) => {
        const idx = items?.[0]?.dataIndex;
        return formatTooltipDateTitle(rawDates?.[idx], monthLabel);
      }
    };
    if (typeof tooltipCallbacks.label === "function") {
      callbacks.label = tooltipCallbacks.label;
    }
    const out = {
      tooltip: {
        enabled: true,
        mode: "index",
        intersect: false,
        position: "nearest",
        animation: { duration: 0 },
        caretPadding: 8,
        padding: 10,
        displayColors: true,
        xAlign: "center",
        yAlign: "bottom",
        titleColor: pickColor(oldTip.titleColor),
        bodyColor: pickColor(oldTip.bodyColor),
        backgroundColor: pickColor(oldTip.backgroundColor),
        borderColor: pickColor(oldTip.borderColor),
        borderWidth: oldTip.borderWidth ?? 1,
        callbacks
      }
    };
    if (oldPlugins.title) {
      const meta = window.kpiActualTargetChartMeta || {};
      const templateTitle = meta.template_title || "All Templates";
      const monthLabel2 = meta.month_label || "";
      const titleText = monthLabel2 ? [templateTitle, monthLabel2] : [templateTitle];
      out.title = {
        display: false,
        text: titleText
      };
    }
    if (oldPlugins.legend) {
      out.legend = {
        display: oldPlugins.legend.display !== false,
        position: "bottom",
        align: "start",
        labels: {
          color: pickColor(oldPlugins.legend.labels?.color),
          boxWidth: 14,
          padding: 10
        }
      };
    }
    return out;
  };
  var recreateChartWithTableAxis = (chart, table, extraCount = 0) => {
    if (!chart?.canvas) return false;
    const xType = chart.options?.scales?.x?.type;
    if (chart.__kpiTableAxisDone && xType !== "time") return false;
    const dayLabels = getDayLabelsFromTable(table);
    if (!dayLabels.length) return false;
    const ChartCtor = chart.constructor;
    if (!ChartCtor) return false;
    try {
      preserveRawLabels(chart);
      const rawDates = chart.__kpiRawDateLabels || [];
      const monthLabel = window.kpiActualTargetChartMeta?.month_label || "";
      const oldConfig = chart.config;
      const oldPlugins = oldConfig.options?.plugins;
      const oldTooltipCallbacks = oldPlugins?.tooltip?.callbacks || {};
      const oldScales = oldConfig.options?.scales || {};
      const scales = {};
      Object.keys(oldScales).forEach((id) => {
        if (id === "x") return;
        scales[id] = buildYScaleConfig(oldScales[id]);
      });
      scales.x = kpiTableXScale(dayLabels.length);
      const padding = oldConfig.options?.layout?.padding || {};
      const datasets = applyCncWasteLineStyle(chart.data.datasets.map((ds) => ({
        ...cloneDataset(ds),
        data: seriesToIndexedPoints(ds.data)
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
              right: kpiChartPadRight(extraCount)
            }
          },
          scales,
          interaction: {
            mode: "index",
            intersect: false,
            axis: "x"
          },
          plugins: {
            ...buildPluginsConfig(oldPlugins, rawDates, monthLabel, oldTooltipCallbacks),
            kpiTableColumnGrid: true,
            kpiSnapPointsToTable: true,
            kpiValueLabels: true,
            kpiHtmlChrome: true
          },
          responsive: false,
          maintainAspectRatio: false
        }
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
      console.warn("[kpi-chart-sync] chart recreate failed:", err);
      return false;
    }
  };
  var applyChartPaddingFromTable = (chart, extraCount = 0) => {
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
      const dayCount = chart.data?.labels?.length || window.kpiActualTargetChartData?.labels?.length || 0;
      resizeChartToTableWidth(chart, dayCount, extraCount);
      applySensibleYScale(chart);
      applyYAxisTickLayout(chart);
      applyKpiTooltipLayout(chart);
      chart.__kpiTableMetrics = measureTablePlotMetrics(chart);
      return true;
    } catch (err) {
      console.warn("[kpi-chart-sync] padding apply skipped:", err);
      return false;
    }
  };

  // resources/js/components/kpi-table-row-icons.js
  var SVG = 'class="kpi-row-label__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"';
  var ICONS = {
    date: `<svg ${SVG}><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>`,
    target: `<svg ${SVG}><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>`,
    actual: `<svg ${SVG}><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M4 19h16"/></svg>`
  };
  var COLORS = {
    date: "text-gray-500 dark:text-gray-400",
    target: "text-red-700 dark:text-red-400",
    actual: "text-blue-700 dark:text-blue-400"
  };
  var resolveCoreRowIconKey = (text) => {
    const t = String(text ?? "").trim().toLowerCase();
    if (t === "date" || t === "field") return "date";
    if (/^target\b/.test(t)) return "target";
    if (/^actual\s*\(/.test(t)) return "actual";
    return null;
  };
  var wrapLabelWithIcon = (labelEl, iconKey) => {
    if (!labelEl || !iconKey || labelEl.querySelector(".kpi-row-label")) return;
    const text = (labelEl.textContent || "").trim();
    let colorClass = COLORS[iconKey] || COLORS.date;
    const td = labelEl.closest("td");
    if (labelEl.classList.contains("text-white") || (td?.getAttribute("style") || "").includes("192, 0, 0") || (td?.getAttribute("style") || "").includes("0, 112, 192")) {
      colorClass = "text-white";
    }
    const wrap = document.createElement("div");
    wrap.className = "kpi-row-label flex items-center gap-1.5 min-w-0";
    wrap.innerHTML = `<span class="kpi-row-label__icon-wrap shrink-0 ${colorClass}">${ICONS[iconKey]}</span><span class="kpi-row-label__text truncate leading-tight">${text}</span>`;
    labelEl.textContent = "";
    labelEl.appendChild(wrap);
  };
  var SECTION_TITLE_ICON = `<svg class="kpi-section-title__icon w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>`;
  var decorateKpiSectionTitle = () => {
    document.querySelectorAll("h2").forEach((h2) => {
      const text = (h2.textContent || "").trim();
      if (!/^KPI Actual vs Target$/i.test(text)) return;
      if (h2.querySelector(".kpi-section-title__icon")) return;
      h2.classList.add("flex", "items-center", "gap-2");
      h2.innerHTML = `<span class="inline-flex shrink-0 text-blue-600 dark:text-blue-400">${SECTION_TITLE_ICON}</span><span>${text}</span>`;
    });
  };
  var formatKpiTableNumericCells = () => {
  };
  var applyKpiChartTitleLayout = (chart) => {
    const live = chart || window.kpiActualTargetChartInstance;
    if (!live?.options?.plugins) return;
    const meta = window.kpiActualTargetChartMeta || {};
    const templateTitle = meta.template_title || "All Templates";
    const monthLabel = meta.month_label || "";
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
      live.update("none");
    } catch (e) {
    }
  };
  var removeKpiChartTitleBanner = (chart) => {
    document.getElementById("kpi-chart-title-banner")?.remove();
    document.querySelectorAll(".kpi-chart-title-banner").forEach((el) => el.remove());
    applyKpiChartTitleLayout(chart);
  };
  var decorateKpiTableRowIcons = (table) => {
    if (!table) return;
    const dateHeader = table.querySelector("thead tr th.kpi-chart-table__field div") || table.querySelector("thead tr th:first-child div");
    if (dateHeader) wrapLabelWithIcon(dateHeader, "date");
    table.querySelectorAll("tbody tr td.kpi-chart-table__field div, tbody tr td:first-child div").forEach((div) => {
      const key = resolveCoreRowIconKey(div.textContent);
      if (key === "target" || key === "actual") wrapLabelWithIcon(div, key);
    });
  };

  // resources/js/components/kpi-chart-table-sync.js
  var KPI_SYNC_STYLE_ID = "kpi-chart-table-sync-styles";
  var domReady = false;
  var layoutDone = false;
  var chartAxisDone = false;
  var guidesBound = false;
  var stabilizeTimer = null;
  var KPI_CHART_SLOT_MIN = 280;
  var getChartSlotHeight = () => {
    const canvas = document.getElementById("kpi-actual-target-chart");
    const wrap = canvas?.closest(".kpi-chart-table-wrap");
    const fromStyle = wrap ? parseInt(wrap.style.height || wrap.style.minHeight || "", 10) : NaN;
    const attr = parseInt(canvas?.getAttribute("height") || "", 10);
    const h = Number.isFinite(fromStyle) && fromStyle > 0 ? fromStyle : Number.isFinite(attr) && attr > 0 ? attr : 300;
    return Math.max(KPI_CHART_SLOT_MIN, h);
  };
  var renderColumnAlignGuides = () => {
    const chartWrap = document.querySelector("#kpi-chart-table-sync .kpi-chart-table-wrap");
    const table = findTable();
    if (!chartWrap || !table) return;
    document.getElementById("kpi-chart-table-sync-inner")?.querySelector(".kpi-column-guides")?.remove();
    let layer = chartWrap.querySelector(".kpi-column-guides");
    if (!layer) {
      layer = document.createElement("div");
      layer.className = "kpi-column-guides";
      layer.setAttribute("aria-hidden", "true");
      chartWrap.appendChild(layer);
    }
    layer.innerHTML = "";
    const wrapRect = chartWrap.getBoundingClientRect();
    const addLine = (x, className) => {
      const line = document.createElement("div");
      line.className = `kpi-column-guide ${className}`;
      line.style.left = `${Math.round(x)}px`;
      layer.appendChild(line);
    };
    const fieldTh = table.querySelector("thead th.kpi-chart-table__field");
    if (fieldTh) {
      const r = fieldTh.getBoundingClientRect();
      addLine(r.right - wrapRect.left, "kpi-column-guide--field");
    }
    const dayThs = table.querySelectorAll("thead th.kpi-chart-table__day");
    dayThs.forEach((th) => {
      const r = th.getBoundingClientRect();
      addLine(r.left - wrapRect.left, "kpi-column-guide--edge");
    });
    const lastDay = dayThs[dayThs.length - 1];
    if (lastDay) {
      const r = lastDay.getBoundingClientRect();
      addLine(r.right - wrapRect.left, "kpi-column-guide--edge");
    }
  };
  var stabilizeKpiLayout = () => {
    clearTimeout(stabilizeTimer);
    stabilizeTimer = setTimeout(() => {
      const canvas = document.getElementById("kpi-actual-target-chart");
      const chartWrap = findChartWrap(canvas);
      if (!chartWrap) return;
      const height = getChartSlotHeight();
      chartWrap.style.flexShrink = "0";
      chartWrap.style.boxSizing = "border-box";
      if (!chartWrap.style.height) chartWrap.style.height = `${height}px`;
      if (!chartWrap.style.minHeight) chartWrap.style.minHeight = `${height}px`;
      if (!chartWrap.style.maxHeight) chartWrap.style.maxHeight = `${height}px`;
      const tableHost = document.getElementById("presentation-kpi-table");
      if (tableHost) {
        tableHost.style.flexShrink = "0";
        tableHost.style.position = "static";
      }
      if (canvas?.parentElement && canvas.parentElement !== chartWrap) {
        canvas.parentElement.style.height = `${height}px`;
        canvas.parentElement.style.minHeight = `${height}px`;
        canvas.parentElement.style.maxHeight = `${height}px`;
      }
      renderColumnAlignGuides();
    }, 60);
  };
  var scheduleAlignGuides = () => {
    requestAnimationFrame(() => {
      requestAnimationFrame(renderColumnAlignGuides);
    });
  };
  var bindGuideRefresh = () => {
    if (guidesBound) return;
    guidesBound = true;
    const scroll = document.getElementById("kpi-chart-table-sync");
    if (scroll) {
      scroll.addEventListener("scroll", () => {
        renderColumnAlignGuides();
        stabilizeKpiLayout();
      }, { passive: true });
    }
    window.addEventListener("resize", stabilizeKpiLayout, { passive: true });
  };
  var injectStyles = () => {
    if (document.getElementById(KPI_SYNC_STYLE_ID)) return;
    const style = document.createElement("style");
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
  var isExtraHeader = (th) => {
    const t = (th.textContent || "").trim().toLowerCase();
    return t === "total" || t.startsWith("average");
  };
  var KPI_DAY_COL_MIN = 44;
  var KPI_DAY_COL_MAX = 220;
  var textMeasureCanvas;
  var measureTextPx = (text, font) => {
    if (!textMeasureCanvas) textMeasureCanvas = document.createElement("canvas");
    const ctx = textMeasureCanvas.getContext("2d");
    ctx.font = font;
    return ctx.measureText(text).width;
  };
  var getTableMeasureFont = (table) => {
    const sample = table.querySelector("tbody td:not(:first-child) div") || table.querySelector("thead th.kpi-chart-table__day div");
    if (!sample) return "500 11px Inter, system-ui, sans-serif";
    const cs = getComputedStyle(sample);
    return `${cs.fontWeight} ${cs.fontSize} ${cs.fontFamily}`;
  };
  var measureDayColWidth = (table) => {
    if (!table) return KPI_DAY_COL_MIN;
    const font = getTableMeasureFont(table);
    const cellPad = 16;
    let maxW = KPI_DAY_COL_MIN;
    const consider = (text) => {
      const t = String(text ?? "").trim();
      if (!t || t === "OK" || t === "NG") return;
      maxW = Math.max(maxW, measureTextPx(t, font) + cellPad);
    };
    table.querySelectorAll("thead th.kpi-chart-table__day div").forEach((el) => {
      consider(el.textContent);
    });
    table.querySelectorAll("tbody tr").forEach((tr) => {
      tr.querySelectorAll("td:not(:first-child)").forEach((td) => {
        consider(td.querySelector("div")?.textContent || td.textContent);
      });
    });
    return Math.min(KPI_DAY_COL_MAX, Math.max(KPI_DAY_COL_MIN, Math.ceil(maxW)));
  };
  var tagTableColumns = (table) => {
    const row = table.querySelector("thead tr");
    if (!row) return { dayCount: 0, extraCount: 0 };
    row.classList.add("kpi-chart-x-axis");
    let dayCount = 0;
    let extraCount = 0;
    Array.from(row.querySelectorAll("th")).forEach((th, i) => {
      th.classList.remove("kpi-chart-table__field", "kpi-chart-table__day", "kpi-chart-table__extra");
      if (i === 0) {
        th.classList.add("kpi-chart-table__field", "bg-gray-50", "dark:bg-gray-700/50");
      } else if (isExtraHeader(th)) {
        th.classList.add("kpi-chart-table__extra");
        extraCount += 1;
      } else {
        th.classList.add("kpi-chart-table__day");
        dayCount += 1;
      }
    });
    table.querySelectorAll("tbody tr, tfoot tr").forEach((tr) => {
      const td = tr.querySelector("td");
      if (!td) return;
      const footer = tr.parentElement?.tagName === "TFOOT";
      td.classList.add("kpi-chart-table__field");
      if (footer) td.classList.add("bg-gray-50", "dark:bg-gray-700/50");
      else td.classList.add("bg-white", "dark:bg-gray-800");
    });
    table.querySelectorAll("tbody td:not(:first-child)").forEach((td) => {
      td.classList.add("kpi-chart-table__value-cell");
      const div = td.querySelector("div");
      if (div) div.removeAttribute("title");
    });
    fixSaturdayHeaderTextColor(table);
    renameFieldHeaderToDate(table);
    decorateKpiTableRowIcons(table);
    formatKpiTableNumericCells(table);
    const dayColPx = measureDayColWidth(table);
    window.kpiTableDayColPx = dayColPx;
    return { dayCount, extraCount, dayColPx };
  };
  var renameFieldHeaderToDate = (table) => {
    const label = table?.querySelector("thead tr th.kpi-chart-table__field div") || table?.querySelector("thead tr th:first-child div");
    if (label) label.textContent = "Date";
  };
  var fixSaturdayHeaderTextColor = (table) => {
    if (!table) return;
    table.querySelectorAll("thead th").forEach((th) => {
      const style = (th.getAttribute("style") || "").replace(/\s/g, "");
      const isSaturday = style.includes("255,255,0") || style.includes("rgb(255,255,0)");
      if (isSaturday) {
        th.classList.add("kpi-header-sat");
        const label = th.querySelector("div");
        if (label) label.style.color = "#000";
      }
    });
  };
  var ensureColgroup = (table, dayCount, extraCount, dayColPx = 44) => {
    let cg = table.querySelector("colgroup.kpi-sync-cols");
    if (!cg) {
      cg = document.createElement("colgroup");
      cg.className = "kpi-sync-cols";
      table.insertBefore(cg, table.firstChild);
    }
    cg.innerHTML = "";
    const f = document.createElement("col");
    f.style.width = "140px";
    cg.appendChild(f);
    for (let i = 0; i < dayCount; i += 1) {
      const c = document.createElement("col");
      c.style.width = `${dayColPx}px`;
      cg.appendChild(c);
    }
    for (let i = 0; i < extraCount; i += 1) {
      const c = document.createElement("col");
      c.style.width = "72px";
      cg.appendChild(c);
    }
  };
  var findChartWrap = (canvas) => canvas?.closest(".kpi-chart-table-wrap") || canvas?.closest('[class*="h-["]') || canvas?.parentElement;
  var findTable = () => {
    const inSync = document.querySelector("#kpi-chart-table-sync .kpi-chart-table");
    if (inSync) return inSync;
    const canvas = document.getElementById("kpi-actual-target-chart");
    const wrap = canvas?.closest(".p-5");
    return wrap?.querySelector("table") || null;
  };
  var hideDuplicateKpiTablesOutsideSync = () => {
    const canvas = document.getElementById("kpi-actual-target-chart");
    const panel = canvas?.closest(".col-span-full");
    const sync = document.getElementById("kpi-chart-table-sync");
    if (!panel || !sync) return;
    panel.querySelectorAll("table").forEach((table) => {
      if (sync.contains(table)) return;
      const host = table.closest(".overflow-x-auto") || table.closest(".mt-4") || table.parentElement;
      if (!host || sync.contains(host)) return;
      host.classList.add("kpi-duplicate-table-hidden");
    });
    sync.querySelectorAll(".kpi-duplicate-table-hidden").forEach((el) => {
      el.classList.remove("kpi-duplicate-table-hidden");
    });
  };
  var isPrebuiltSyncDom = (canvas, chartWrap, scroll, inner, tableHost) => scroll && inner && chartWrap && tableHost && inner.contains(chartWrap) && inner.contains(tableHost) && scroll.contains(inner);
  var setupDom = () => {
    const canvas = document.getElementById("kpi-actual-target-chart");
    if (!canvas) return false;
    injectStyles();
    const chartWrap = findChartWrap(canvas);
    if (!chartWrap) return false;
    let table = findTable();
    if (!table) return false;
    const scroll = document.getElementById("kpi-chart-table-sync");
    const inner = document.getElementById("kpi-chart-table-sync-inner");
    let tableHost = document.getElementById("presentation-kpi-table");
    if (isPrebuiltSyncDom(canvas, chartWrap, scroll, inner, tableHost)) {
      chartWrap.classList.add("kpi-chart-table-wrap");
      inner.style.display = "flex";
      inner.style.flexDirection = "column";
      inner.style.alignItems = "flex-start";
      tableHost.classList.remove("mt-4", "overflow-x-auto");
      table = tableHost.querySelector("table") || table;
      table.classList.remove("table-auto", "w-full");
      table.classList.add("kpi-chart-table", "table-fixed");
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
      scrollEl = document.createElement("div");
      scrollEl.id = "kpi-chart-table-sync";
      scrollEl.className = "overflow-x-auto";
      contentWrap.insertBefore(scrollEl, contentWrap.firstChild);
    }
    if (!innerEl) {
      innerEl = document.createElement("div");
      innerEl.id = "kpi-chart-table-sync-inner";
      scrollEl.appendChild(innerEl);
    }
    innerEl.style.display = "flex";
    innerEl.style.flexDirection = "column";
    innerEl.style.alignItems = "flex-start";
    chartWrap.classList.add("kpi-chart-table-wrap");
    if (!innerEl.contains(chartWrap)) {
      innerEl.insertBefore(chartWrap, innerEl.firstChild);
    }
    if (!tableHost) {
      tableHost = document.createElement("div");
      tableHost.id = "presentation-kpi-table";
      innerEl.appendChild(tableHost);
    } else if (!innerEl.contains(tableHost)) {
      tableHost.classList.remove("mt-4", "overflow-x-auto");
      innerEl.appendChild(tableHost);
    }
    if (!tableHost.contains(table)) {
      tableHost.innerHTML = "";
      tableHost.appendChild(table);
    }
    table = tableHost.querySelector("table") || table;
    table.classList.remove("table-auto", "w-full");
    table.classList.add("kpi-chart-table", "table-fixed");
    tagTableColumns(table);
    hideDuplicateKpiTablesOutsideSync();
    domReady = true;
    return true;
  };
  var observePresentationTableUpdates = () => {
    const host = document.getElementById("presentation-kpi-table");
    if (!host || host.__kpiTableObserved) return;
    const observer = new MutationObserver(() => {
      layoutDone = false;
      chartAxisDone = false;
      setTimeout(runSync, 50);
    });
    observer.observe(host, { childList: true, subtree: false });
    host.__kpiTableObserved = true;
  };
  var applyLayout = () => {
    if (layoutDone) return;
    const table = findTable();
    if (!table) return;
    const { dayCount, extraCount, dayColPx } = tagTableColumns(table);
    if (!dayCount) return;
    const w = getFixedKpiContentWidth(dayCount, extraCount, dayColPx);
    const scroll = document.getElementById("kpi-chart-table-sync");
    const inner = document.getElementById("kpi-chart-table-sync-inner");
    const chartWrap = findChartWrap(document.getElementById("kpi-actual-target-chart"));
    if (scroll) {
      scroll.style.setProperty("--kpi-content-width", `${w}px`);
      scroll.style.setProperty("--kpi-day-col-width", `${dayColPx || 44}px`);
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
      chartWrap.style.flexShrink = "0";
      chartWrap.style.height = `${slotHeight}px`;
      chartWrap.style.minHeight = `${slotHeight}px`;
      chartWrap.style.maxHeight = `${slotHeight}px`;
      chartWrap.style.overflow = "hidden";
      chartWrap.style.marginBottom = "8px";
      chartWrap.style.boxSizing = "border-box";
    }
    const tableHost = document.getElementById("presentation-kpi-table");
    if (tableHost) {
      tableHost.style.marginTop = "0";
      tableHost.style.flexShrink = "0";
      tableHost.style.width = `${w}px`;
    }
    const canvas = document.getElementById("kpi-actual-target-chart");
    if (canvas) {
      canvas.style.width = `${w}px`;
      canvas.style.maxWidth = "none";
      if (canvas.parentElement) {
        canvas.parentElement.style.width = `${w}px`;
        canvas.parentElement.style.height = `${slotHeight}px`;
        canvas.parentElement.style.minHeight = `${slotHeight}px`;
      }
    }
    layoutDone = true;
  };
  var applyChartSize = (dayCount, extraCount) => {
    const chart = window.kpiActualTargetChartInstance;
    if (!chart?.canvas || !dayCount) return;
    resizeChartToTableWidth(chart, dayCount, extraCount);
    applySensibleYScale(chart);
    applyYAxisTickLayout(chart);
    applyKpiTooltipLayout(chart);
    removeKpiChartTitleBanner(chart);
    chart.__kpiTableMetrics = measureTablePlotMetrics(chart);
  };
  var unifyChartWithTable = () => {
    const chart = window.kpiActualTargetChartInstance;
    const table = findTable();
    if (!chart?.canvas || !table) return;
    const { dayCount, extraCount } = tagTableColumns(table);
    const xType = chart.options?.scales?.x?.type;
    const mustRecreate = xType === "time" || !chart.__kpiTableAxisDone;
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
  var patchCncWasteLinesOnLiveChart = () => {
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
  var runSync = () => {
    if (!document.getElementById("kpi-actual-target-chart")) return;
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
        live.update("none");
      } catch (e) {
      }
    }
    scheduleAlignGuides();
    bindGuideRefresh();
  };
  var resetForNewChart = () => {
    domReady = false;
    layoutDone = false;
    chartAxisDone = false;
  };
  var wrapChartRender = () => {
    const orig = window.renderKpiActualTargetChart;
    if (typeof orig !== "function" || orig.__kpiWrapped) return;
    window.renderKpiActualTargetChart = function kpiRenderWrapped() {
      resetForNewChart();
      const existing = window.kpiActualTargetChartInstance;
      if (existing?.destroy) {
        try {
          existing.destroy();
        } catch (err) {
          console.warn("[kpi-chart-sync] destroy before render:", err);
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
  var ensureHtmlChromePlugin = (chart) => {
    const Ctor = chart?.constructor;
    if (Ctor?.register && !Ctor.registry?.plugins?.get(kpiHtmlChromePlugin.id)) {
      Ctor.register(kpiHtmlChromePlugin);
    }
  };
  var init = () => {
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
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", afterAppChart, { once: true });
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
            c.update("none");
          } catch (e) {
          }
        }
      }, 900);
    };
    if (document.readyState === "complete") {
      scheduleInitialSync();
    } else {
      window.addEventListener("load", scheduleInitialSync, { once: true });
    }
  };
  window.syncKpiChartTableLayout = runSync;
  window.resetKpiChartTableLayout = resetForNewChart;
  window.getDayLabelsFromTable = getDayLabelsFromTable;
  window.renderKpiColumnAlignGuides = renderColumnAlignGuides;
  window.stabilizeKpiChartTableLayout = stabilizeKpiLayout;
  init();
})();
