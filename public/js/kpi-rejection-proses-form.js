/**
 * Rejection in Proses: 3-column layout (NG | Produksi Pcs | Hasil Produksi Kg).
 */
(function () {
  const TEMPLATE_CODE = 'TPL_PD_REJECTION_PROSES';

  const findRejectionCards = () => {
    const cards = [];
    document.querySelectorAll('[data-kpi-id]').forEach((card) => {
      const hint = card.textContent || '';
      if (hint.includes(TEMPLATE_CODE)) {
        cards.push(card);
      }
    });
    return cards;
  };

  const getFieldWrap = (card, key) => {
    const input = card.querySelector(`[data-field-key="${key}"], [name*="[dynamic_fields][${key}]"]`);
    return input?.closest('div.mb-0, div')?.parentElement?.closest('div') || input?.parentElement;
  };

  const ensureKgField = (card, kpiId) => {
    if (card.querySelector('[data-field-key="hasil_produksi"]')) {
      return;
    }

    const grid = card.querySelector('.grid');
    if (!grid) return;

    const wrap = document.createElement('div');
    wrap.innerHTML = `
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Hasil Produksi <span class="text-xs text-gray-500">(Kg)</span>
      </label>
      <div class="flex items-center gap-2">
        <input type="number" step="0.01"
               name="entries[${kpiId}][dynamic_fields][hasil_produksi]"
               class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
               required
               data-dynamic-field data-field-key="hasil_produksi">
        <span class="text-base font-semibold text-emerald-700 dark:text-emerald-400 shrink-0">Kg</span>
      </div>`;
    grid.appendChild(wrap);
  };

  const layoutCard = (card) => {
    const kpiId = card.getAttribute('data-kpi-id');
    if (!kpiId) return;

    ensureKgField(card, kpiId);

    const section = card.querySelector('h3')?.closest('.mb-4');
    const grid = section?.querySelector('.grid');
    if (!grid) return;

    grid.classList.remove('md:grid-cols-2');
    grid.classList.add('md:grid-cols-3');

    const order = ['actual_ng', 'actual_produksi', 'hasil_produksi'];
    order.forEach((key) => {
      const input = card.querySelector(`[data-field-key="${key}"]`);
      const cell = input?.closest('div')?.parentElement?.parentElement
        || input?.parentElement?.parentElement;
      if (cell && cell.parentElement === grid) {
        grid.appendChild(cell);
      }
    });

    ['actual_ng', 'actual_produksi', 'hasil_produksi'].forEach((key) => {
      const input = card.querySelector(`[data-field-key="${key}"]`);
      const unitSpan = input?.parentElement?.querySelector('span');
      if (key === 'hasil_produksi' && input) {
        input.setAttribute('required', 'required');
        input.setAttribute('min', '0.01');
        let kg = input.parentElement?.querySelector('.kpi-kg-suffix');
        if (!kg) {
          kg = document.createElement('span');
          kg.className = 'kpi-kg-suffix text-base font-semibold text-emerald-700 dark:text-emerald-400 shrink-0';
          kg.textContent = 'Kg';
          input.parentElement?.appendChild(kg);
        }
      }
    });

    let hint = section.querySelector('.kpi-rejection-hint');
    if (!hint) {
      hint = document.createElement('p');
      hint.className = 'kpi-rejection-hint mt-2 text-xs text-gray-500 dark:text-gray-400';
      hint.innerHTML = 'Nilai <strong>Hasil Produksi (Kg)</strong> tampil di <strong>Rejection in Proses</strong> dan disalin ke baris Hasil Produksi di <strong>Waste NG</strong>, <strong>Waste Gram</strong>, dan <strong>Waste Puntungan</strong>. Isi juga <strong>Actual NG</strong> dan <strong>Actual Produksi</strong> agar data tersimpan.';
      section.appendChild(hint);
    }
  };

  const run = () => {
    findRejectionCards().forEach(layoutCard);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }

  const observer = new MutationObserver(() => run());
  observer.observe(document.body, { childList: true, subtree: true });
})();
