/**
 * Rename "Field" → "Date" and re-run chart/table sync after KPI table AJAX updates.
 */
(function () {
  const renameFieldHeader = (root) => {
    if (!root) return;
    root.querySelectorAll('thead tr th:first-child div').forEach((el) => {
      if (el.textContent.trim().toLowerCase() === 'field') {
        el.textContent = 'Date';
      }
    });
  };

  const triggerSync = () => {
    if (typeof window.syncKpiChartTableLayout === 'function') {
      window.syncKpiChartTableLayout();
    }
  };

  const onReady = () => {
    renameFieldHeader(document.getElementById('kpi-chart-table-sync'));
    renameFieldHeader(document.getElementById('presentation-kpi-table'));

    const host = document.getElementById('presentation-kpi-table');
    if (host && !host.__kpiRenameObserved) {
      const observer = new MutationObserver(() => {
        renameFieldHeader(host);
        triggerSync();
      });
      observer.observe(host, { childList: true, subtree: false });
      host.__kpiRenameObserved = true;
    }

    triggerSync();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', onReady);
  } else {
    onReady();
  }
})();
