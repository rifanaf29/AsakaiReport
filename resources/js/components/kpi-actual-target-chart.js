// Import Chart.js
import {
  Chart,
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

Chart.register(LineController, LineElement, Filler, PointElement, LinearScale, TimeScale, Tooltip, Legend, Title);

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

  const chart = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [
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
      ],
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
      scales: {
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
            size: 14,
            weight: '600',
          },
          padding: {
            top: 6,
            bottom: 10,
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
            label: (context) => `${context.dataset.label}: ${formatKpiValue(context.parsed?.y, getUnit())}`,
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

    chart.options.scales.x.ticks.color = isDark ? textColor.dark : textColor.light;
    chart.options.scales.y.ticks.color = isDark ? textColor.dark : textColor.light;
    chart.options.scales.y.grid.color = isDark ? gridColor.dark : gridColor.light;
    chart.options.scales.y.title.color = isDark ? textColor.dark : textColor.light;

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

export default kpiActualTargetChart;
