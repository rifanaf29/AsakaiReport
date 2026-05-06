// Import Chart.js
import {
  Chart,
  BarController,
  BarElement,
  LineController,
  LineElement,
  Filler,
  PointElement,
  LinearScale,
  TimeScale,
  Tooltip,
  Legend,
  Title,
} from 'chart.js';
import 'chartjs-adapter-moment';

// Import utilities
import { getCssVariable, adjustColorOpacity } from '../utils';

Chart.register(BarController, BarElement, LineController, LineElement, Filler, PointElement, LinearScale, TimeScale, Tooltip, Legend, Title);

const formatKpiValue = (value, unit) => {
  if (value === null || value === undefined || value === '') return '-';

  const numericValue = Number(value);
  if (!Number.isFinite(numericValue)) return '-';
  const formatted = Intl.NumberFormat('en-US', {
    maximumFractionDigits: 2,
  }).format(numericValue);

  if (!unit) return formatted;
  if (unit === '%') return `${formatted}%`;
  return `${formatted} ${unit}`;
};

const kpiActualTargetChart = () => {
  const ctx = document.getElementById('kpi-actual-target-chart');
  if (!ctx) return;

  if (window.kpiActualTargetChartInstance) {
    try {
      window.kpiActualTargetChartInstance.destroy();
    } catch (e) {
      // no-op
    }
    window.kpiActualTargetChartInstance = null;
  }

  const chartPayload = window.kpiActualTargetChartData || { labels: [], actual: [], target: [] };
  const getMeta = () => window.kpiActualTargetChartMeta || { template_title: 'All Templates', unit: null, month_label: null };
  const labels = chartPayload.labels || [];
  const actual = chartPayload.actual || [];
  const target = chartPayload.target || [];
  const getUnit = () => getMeta().unit;
  const getMonthLabel = () => getMeta().month_label;
  const getTemplateCode = () => getMeta().template_code;

  const darkMode = localStorage.getItem('dark-mode') === 'true';

  const textColor = {
    light: '#9CA3AF',
    dark: '#6B7280',
  };

  const gridColor = {
    light: '#F3F4F6',
    dark: adjustColorOpacity('#374151', 0.6),
  };

  const tooltipBodyColor = {
    light: '#6B7280',
    dark: '#9CA3AF',
  };

  const tooltipTitleColor = {
    light: '#111827',
    dark: '#F3F4F6',
  };

  const tooltipBgColor = {
    light: '#ffffff',
    dark: '#374151',
  };

  const tooltipBorderColor = {
    light: '#E5E7EB',
    dark: '#4B5563',
  };

  const actualColor = 'rgb(0, 112, 192)';
  const targetColor = 'rgb(192, 0, 0)';

  const isDisplayOnly = getMeta().target_mode === 'display_only';
  const dashboardFields = getMeta().dashboard_fields || [];
  const seriesLabels = window.kpiSeriesLabels || {};

  const fieldPalette = [
    'rgb(0, 112, 192)',
    'rgb(192, 0, 0)',
    'rgb(0, 176, 80)',
    'rgb(255, 153, 0)',
    'rgb(112, 48, 160)',
    'rgb(0, 176, 240)',
    'rgb(255, 0, 0)',
    'rgb(146, 208, 80)',
  ];

  if (isDisplayOnly) {
    const fieldKeys = dashboardFields.length > 0
      ? dashboardFields
      : Object.keys(chartPayload).filter(k => k.startsWith('field:')).map(k => k.slice(6));

    const displayDatasets = fieldKeys.map((key, i) => {
      const seriesKey = `field:${key}`;
      const color = fieldPalette[i % fieldPalette.length];
      const label = seriesLabels[seriesKey] || key;
      return {
        label,
        data: chartPayload[seriesKey] || [],
        borderColor: color,
        backgroundColor: adjustColorOpacity(color, 0.12),
        fill: false,
        borderWidth: 2,
        pointRadius: 2,
        pointHoverRadius: 4,
        pointBackgroundColor: color,
        pointHoverBackgroundColor: color,
        tension: 0.2,
        clip: 20,
      };
    });

    window.kpiActualTargetChartInstance = new Chart(ctx, {
      type: 'line',
      data: { labels, datasets: displayDatasets },
      options: {
        layout: { padding: { top: 6, bottom: 10, left: 8, right: 8 } },
        scales: {
          y: {
            beginAtZero: true,
            border: { display: false },
            ticks: {
              maxTicksLimit: 6,
              callback: (value) => formatKpiValue(value, getUnit()),
              color: darkMode ? textColor.dark : textColor.light,
            },
            grid: { color: darkMode ? gridColor.dark : gridColor.light },
          },
          x: {
            type: 'time',
            time: { parser: 'MM-DD-YYYY', unit: 'day', displayFormats: { day: 'MMM D' } },
            border: { display: false },
            grid: { display: false },
            ticks: { color: darkMode ? textColor.dark : textColor.light, maxRotation: 0 },
          },
        },
        plugins: {
          title: {
            display: true,
            text: getMonthLabel() ? [getMeta().template_title || '', getMonthLabel()] : (getMeta().template_title || ''),
            color: '#000000',
            font: { size: 20, weight: '700' },
            padding: { top: 6, bottom: 12 },
          },
          legend: { display: true },
          tooltip: {
            callbacks: {
              label: (context) => {
                const val = context.parsed.y;
                return ` ${context.dataset.label}: ${formatKpiValue(val, getUnit())}`;
              },
            },
          },
        },
        responsive: true,
        maintainAspectRatio: false,
      },
    });
    return;
  }

  const isCncWaste = getTemplateCode() === 'TPL_PD_WASTE_CNC_BENDING';
  const cncWasteKg = chartPayload['field:total_waste_kg'] || [];

  const isPdMpOt = getTemplateCode() === 'TPL_PD_MP_OT';
  const otChargePd1 = chartPayload['field:ot_charge_pd1'] || [];
  const otChargePd2 = chartPayload['field:ot_charge_pd2'] || [];
  const otChargePd3 = chartPayload['field:ot_charge_pd3'] || [];
  const otChargePd4 = chartPayload['field:ot_charge_pd4'] || [];
  const otChargePd5 = chartPayload['field:ot_charge_pd5'] || [];
  const totalOtCharge = chartPayload['field:total_ot_charge'] || [];
  const jumlahMp = chartPayload['field:jumlah_mp'] || [];

  const datasets = isPdMpOt
    ? [
      {
        type: 'bar',
        label: 'OT Charge PD1',
        data: otChargePd1,
        borderColor: targetColor,
        backgroundColor: adjustColorOpacity(targetColor, 0.18),
        stack: 'otCharge',
        borderWidth: 1,
        yAxisID: 'yRp',
      },
      {
        type: 'bar',
        label: 'OT Charge PD2',
        data: otChargePd2,
        borderColor: targetColor,
        backgroundColor: adjustColorOpacity(targetColor, 0.26),
        stack: 'otCharge',
        borderWidth: 1,
        yAxisID: 'yRp',
      },
      {
        type: 'bar',
        label: 'OT Charge PD3',
        data: otChargePd3,
        borderColor: targetColor,
        backgroundColor: adjustColorOpacity(targetColor, 0.34),
        stack: 'otCharge',
        borderWidth: 1,
        yAxisID: 'yRp',
      },
      {
        type: 'bar',
        label: 'OT Charge PD4',
        data: otChargePd4,
        borderColor: targetColor,
        backgroundColor: adjustColorOpacity(targetColor, 0.42),
        stack: 'otCharge',
        borderWidth: 1,
        yAxisID: 'yRp',
      },
      {
        type: 'bar',
        label: 'OT Charge PD5',
        data: otChargePd5,
        borderColor: targetColor,
        backgroundColor: adjustColorOpacity(targetColor, 0.50),
        stack: 'otCharge',
        borderWidth: 1,
        yAxisID: 'yRp',
      },
      {
        type: 'line',
        label: 'Total OT Charge',
        data: totalOtCharge,
        borderColor: actualColor,
        backgroundColor: adjustColorOpacity(actualColor, 0.10),
        fill: false,
        borderWidth: 2,
        pointRadius: 2,
        pointHoverRadius: 4,
        pointBackgroundColor: actualColor,
        pointHoverBackgroundColor: actualColor,
        tension: 0.2,
        clip: 20,
        yAxisID: 'yRp',
      },
      {
        type: 'line',
        label: 'Jumlah MP',
        data: jumlahMp,
        borderColor: actualColor,
        backgroundColor: adjustColorOpacity(actualColor, 0.10),
        fill: false,
        borderWidth: 2,
        borderDash: [6, 3],
        pointRadius: 2,
        pointHoverRadius: 4,
        pointBackgroundColor: actualColor,
        pointHoverBackgroundColor: actualColor,
        tension: 0.2,
        clip: 20,
        yAxisID: 'yMp',
      },
    ]
    : isCncWaste
    ? [
      {
        label: 'Total Waste (KG)',
        data: cncWasteKg,
        borderColor: targetColor,
        backgroundColor: adjustColorOpacity(targetColor, 0.18),
        fill: false,
        showLine: false,
        borderWidth: 0,
        pointRadius: 3,
        pointHoverRadius: 5,
        pointBackgroundColor: targetColor,
        pointHoverBackgroundColor: targetColor,
        clip: 20,
        yAxisID: 'yKg',
      },
      {
        label: 'Total Waste (%)',
        data: actual,
        borderColor: actualColor,
        backgroundColor: adjustColorOpacity(actualColor, 0.12),
        fill: false,
        showLine: false,
        borderWidth: 0,
        pointRadius: 3,
        pointHoverRadius: 5,
        pointBackgroundColor: actualColor,
        pointHoverBackgroundColor: actualColor,
        clip: 20,
        yAxisID: 'yPct',
      },
    ]
    : [
      {
        label: 'Actual',
        data: actual,
        borderColor: actualColor,
        backgroundColor: adjustColorOpacity(actualColor, 0.12),
        fill: true,
        borderWidth: 2,
        pointRadius: 2,
        pointHoverRadius: 4,
        pointBackgroundColor: actualColor,
        pointHoverBackgroundColor: actualColor,
        tension: 0.2,
        clip: 20,
      },
      {
        label: 'Target',
        data: target,
        borderColor: targetColor,
        backgroundColor: adjustColorOpacity(targetColor, 0.08),
        fill: true,
        borderWidth: 2,
        pointRadius: 2,
        pointHoverRadius: 4,
        pointBackgroundColor: targetColor,
        pointHoverBackgroundColor: targetColor,
        tension: 0.2,
        clip: 20,
      },
    ];

  const chart = new Chart(ctx, {
    type: isPdMpOt ? 'bar' : 'line',
    data: {
      labels,
      datasets,
    },
    options: {
      layout: {
        padding: {
          top: 6,
          bottom: 10,
          left: 8,
          right: 8,
        },
      },
      scales: isPdMpOt
        ? {
          x: {
            type: 'time',
            stacked: true,
            time: {
              parser: 'MM-DD-YYYY',
              unit: 'day',
              displayFormats: {
                day: 'MMM D',
              },
            },
            border: {
              display: false,
            },
            grid: {
              display: false,
            },
            ticks: {
              color: darkMode ? textColor.dark : textColor.light,
              maxRotation: 0,
            },
          },
          yRp: {
            beginAtZero: true,
            position: 'left',
            stacked: true,
            border: { display: false },
            ticks: {
              maxTicksLimit: 6,
              callback: (value) => formatKpiValue(value, 'Rp'),
              color: darkMode ? textColor.dark : textColor.light,
            },
            grid: {
              color: darkMode ? gridColor.dark : gridColor.light,
            },
            title: {
              display: true,
              text: 'Unit: Rp',
              color: darkMode ? textColor.dark : textColor.light,
            },
          },
          yMp: {
            beginAtZero: true,
            position: 'right',
            stacked: false,
            border: { display: false },
            grid: {
              drawOnChartArea: false,
            },
            ticks: {
              maxTicksLimit: 6,
              callback: (value) => formatKpiValue(value, 'Orang'),
              color: darkMode ? textColor.dark : textColor.light,
            },
            title: {
              display: true,
              text: 'Unit: Orang',
              color: darkMode ? textColor.dark : textColor.light,
            },
          },
        }
        : isCncWaste
        ? {
          // Pareto-style: KG bars on left axis, % line on right axis (0-10%).
          yKg: {
            beginAtZero: true,
            position: 'left',
            border: { display: false },
            ticks: {
              maxTicksLimit: 6,
              callback: (value) => formatKpiValue(value, 'kg'),
              color: darkMode ? textColor.dark : textColor.light,
            },
            grid: {
              color: darkMode ? gridColor.dark : gridColor.light,
            },
            title: {
              display: true,
              text: 'Unit: kg',
              color: darkMode ? textColor.dark : textColor.light,
            },
          },
          yPct: {
            beginAtZero: true,
            position: 'right',
            min: 0,
            max: 10,
            border: { display: false },
            grid: {
              drawOnChartArea: false,
            },
            ticks: {
              maxTicksLimit: 6,
              callback: (value) => formatKpiValue(value, '%'),
              color: darkMode ? textColor.dark : textColor.light,
            },
            title: {
              display: true,
              text: 'Unit: %',
              color: darkMode ? textColor.dark : textColor.light,
            },
          },
          x: {
            type: 'time',
            time: {
              parser: 'MM-DD-YYYY',
              unit: 'day',
              displayFormats: {
                day: 'MMM D',
              },
            },
            border: {
              display: false,
            },
            grid: {
              display: false,
            },
            ticks: {
              color: darkMode ? textColor.dark : textColor.light,
              maxRotation: 0,
            },
          },
        }
        : {
          y: {
            beginAtZero: true,
            border: {
              display: false,
            },
            ticks: {
              maxTicksLimit: 6,
              callback: (value) => formatKpiValue(value, getUnit()),
              color: darkMode ? textColor.dark : textColor.light,
            },
            grid: {
              color: darkMode ? gridColor.dark : gridColor.light,
            },
            title: {
              display: Boolean(getUnit()),
              text: getUnit() ? `Unit: ${getUnit()}` : '',
              color: darkMode ? textColor.dark : textColor.light,
            },
          },
          x: {
            type: 'time',
            time: {
              parser: 'MM-DD-YYYY',
              unit: 'day',
              displayFormats: {
                day: 'MMM D',
              },
            },
            border: {
              display: false,
            },
            grid: {
              display: false,
            },
            ticks: {
              color: darkMode ? textColor.dark : textColor.light,
              maxRotation: 0,
            },
          },
        },
      plugins: {
        title: {
          display: true,
          text: getMonthLabel() ? [getMeta().template_title || 'All Templates', getMonthLabel()] : (getMeta().template_title || 'All Templates'),
          color: '#000000',
          font: {
            size: 20,
            weight: '700',
          },
          padding: {
            top: 6,
            bottom: 12,
          },
        },
        legend: {
          display: true,
          labels: {
            color: darkMode ? textColor.dark : textColor.light,
          },
        },
        tooltip: {
          callbacks: {
            title: (items) => (items && items[0] ? items[0].label : ''),
            label: (context) => {
              if (!isCncWaste && !isPdMpOt) {
                return `${context.dataset.label}: ${formatKpiValue(context.parsed?.y, getUnit())}`;
              }
              if (isCncWaste) {
                const axis = context.dataset.yAxisID;
                const unit = axis === 'yKg' ? 'kg' : '%';
                return `${context.dataset.label}: ${formatKpiValue(context.parsed?.y, unit)}`;
              }

              // PD MP & OT
              const axis = context.dataset.yAxisID;
              const unit = axis === 'yMp' ? 'Orang' : 'Rp';
              return `${context.dataset.label}: ${formatKpiValue(context.parsed?.y, unit)}`;
            },
          },
          titleColor: darkMode ? tooltipTitleColor.dark : tooltipTitleColor.light,
          bodyColor: darkMode ? tooltipBodyColor.dark : tooltipBodyColor.light,
          backgroundColor: darkMode ? tooltipBgColor.dark : tooltipBgColor.light,
          borderColor: darkMode ? tooltipBorderColor.dark : tooltipBorderColor.light,
          borderWidth: 1,
        },
      },
      interaction: {
        intersect: false,
        mode: 'nearest',
      },
      animation: {
        duration: 200,
      },
      maintainAspectRatio: false,
    },
  });

  document.addEventListener('darkMode', (e) => {
    const { mode } = e.detail;
    const isDark = mode === 'on';

    if (chart.options.scales?.x?.ticks) {
      chart.options.scales.x.ticks.color = isDark ? textColor.dark : textColor.light;
    }

    if (isPdMpOt) {
      if (chart.options.scales?.yRp?.ticks) {
        chart.options.scales.yRp.ticks.color = isDark ? textColor.dark : textColor.light;
      }
      if (chart.options.scales?.yRp?.grid) {
        chart.options.scales.yRp.grid.color = isDark ? gridColor.dark : gridColor.light;
      }
      if (chart.options.scales?.yRp?.title) {
        chart.options.scales.yRp.title.color = isDark ? textColor.dark : textColor.light;
      }

      if (chart.options.scales?.yMp?.ticks) {
        chart.options.scales.yMp.ticks.color = isDark ? textColor.dark : textColor.light;
      }
      if (chart.options.scales?.yMp?.title) {
        chart.options.scales.yMp.title.color = isDark ? textColor.dark : textColor.light;
      }
    } else if (isCncWaste) {
      if (chart.options.scales?.yKg?.ticks) {
        chart.options.scales.yKg.ticks.color = isDark ? textColor.dark : textColor.light;
      }
      if (chart.options.scales?.yKg?.grid) {
        chart.options.scales.yKg.grid.color = isDark ? gridColor.dark : gridColor.light;
      }
      if (chart.options.scales?.yKg?.title) {
        chart.options.scales.yKg.title.color = isDark ? textColor.dark : textColor.light;
      }

      if (chart.options.scales?.yPct?.ticks) {
        chart.options.scales.yPct.ticks.color = isDark ? textColor.dark : textColor.light;
      }
      if (chart.options.scales?.yPct?.title) {
        chart.options.scales.yPct.title.color = isDark ? textColor.dark : textColor.light;
      }
    } else {
      if (chart.options.scales?.y?.ticks) {
        chart.options.scales.y.ticks.color = isDark ? textColor.dark : textColor.light;
      }
      if (chart.options.scales?.y?.grid) {
        chart.options.scales.y.grid.color = isDark ? gridColor.dark : gridColor.light;
      }
      if (chart.options.scales?.y?.title) {
        chart.options.scales.y.title.color = isDark ? textColor.dark : textColor.light;
      }
    }

    chart.options.plugins.legend.labels.color = isDark ? textColor.dark : textColor.light;

    chart.options.plugins.title.color = '#000000';

    chart.options.plugins.tooltip.bodyColor = isDark ? tooltipBodyColor.dark : tooltipBodyColor.light;
    chart.options.plugins.tooltip.titleColor = isDark ? tooltipTitleColor.dark : tooltipTitleColor.light;
    chart.options.plugins.tooltip.backgroundColor = isDark ? tooltipBgColor.dark : tooltipBgColor.light;
    chart.options.plugins.tooltip.borderColor = isDark ? tooltipBorderColor.dark : tooltipBorderColor.light;

    chart.update('none');
  });

  window.kpiActualTargetChartInstance = chart;
};

// Expose re-render helper for dashboard fullscreen live updates.
window.renderKpiActualTargetChart = kpiActualTargetChart;

export default kpiActualTargetChart;
