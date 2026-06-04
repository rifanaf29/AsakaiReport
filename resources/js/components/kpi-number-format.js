/**
 * KPI number display: ppm = integer, % = 1 decimal, Pcs = full integer with thousands (no shortening).
 */

const normalizeUnit = (unit) => String(unit ?? '').trim().toLowerCase();

const PCS_FIELD_KEYS = new Set([
  'actual_produksi',
  'actual_ng',
  'order_pcs',
  'shortage_pcs',
]);

export const resolveKpiFormatKind = ({ unit = null, rowLabel = null, fieldKey = null } = {}) => {
  const u = normalizeUnit(unit);
  const label = String(rowLabel ?? '').toLowerCase();
  const fk = String(fieldKey ?? '').toLowerCase();

  if (u === 'ppm' || label.includes('(ppm)') || /\bppm\b/.test(label)) return 'ppm';
  if (u === '%' || label.includes('(%)')) return 'percent';
  if (u === 'kg' || label.includes('(kg)') || label.includes('gram')) return 'default';
  if (u === 'pcs' || u === 'pc' || label.includes('(pcs)') || label.includes('(pc)')) return 'pcs';
  if (fk && (fk.endsWith('_pcs') || fk.endsWith('_ng') || PCS_FIELD_KEYS.has(fk))) return 'pcs';

  return 'default';
};

const formatPcsInteger = (n) => new Intl.NumberFormat('id-ID', {
  useGrouping: true,
  maximumFractionDigits: 0,
  minimumFractionDigits: 0,
}).format(Math.round(n));

export const formatKpiNumber = (value, options = {}) => {
  if (value === null || value === undefined || value === '') return '';

  const n = Number(value);
  if (!Number.isFinite(n)) return String(value);

  const opts = typeof options === 'string' ? { unit: options } : options;
  const kind = resolveKpiFormatKind(opts);

  switch (kind) {
    case 'ppm':
      return formatPcsInteger(n);
    case 'pcs':
      return formatPcsInteger(n);
    case 'percent':
      return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 1,
      }).format(n);
    default: {
      let formatted = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(n);
      formatted = formatted.replace(/,00$/, '').replace(/,(\d)0$/, ',$1');
      return formatted;
    }
  }
};

export const getKpiChartUnit = () => window.kpiActualTargetChartMeta?.unit ?? null;

export const getKpiFieldUnit = (fieldKey) => {
  if (!fieldKey) return null;
  const units = window.kpiActualTargetChartMeta?.field_units;
  if (!units || typeof units !== 'object') return null;
  return units[fieldKey] ?? null;
};
