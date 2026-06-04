@php
    $fieldsByKey = $tpl->fields->keyBy('field_key');
    $renderNumeric = function (string $key) use ($kpiId, $fieldsByKey, $kpi) {
        $field = $fieldsByKey->get($key);
        if (!$field) {
            return '';
        }
        $fieldKey = $field->field_key;
        $oldVal = old('entries.' . $kpiId . '.dynamic_fields.' . $fieldKey);
        $unitOverride = is_array($kpi->field_units ?? null) ? ($kpi->field_units[$fieldKey] ?? null) : null;
        $unit = $unitOverride ?: ($field->unit ?? '');
        $unitClass = $unit === 'Kg' ? 'text-base font-semibold text-emerald-700 dark:text-emerald-400' : 'text-sm font-medium text-gray-600 dark:text-gray-400';
        ob_start();
        ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ $field->field_name }}
                @if($unit)
                    <span class="text-xs font-normal text-gray-500 dark:text-gray-400">({{ $unit }})</span>
                @endif
            </label>
            <div class="flex items-center gap-2">
                <input type="number"
                       step="0.01"
                       name="entries[{{ $kpiId }}][dynamic_fields][{{ $fieldKey }}]"
                       value="{{ $oldVal }}"
                       @if($field->is_required || $fieldKey === 'hasil_produksi') required @endif
                       class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                       data-dynamic-field
                       data-field-key="{{ $fieldKey }}">
                <span class="{{ $unitClass }} shrink-0">{{ $unit }}</span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    };
@endphp

<div class="mb-4">
    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-3">Additional Fields</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {!! $renderNumeric('actual_ng') !!}
        {!! $renderNumeric('actual_produksi') !!}
        {!! $renderNumeric('hasil_produksi') !!}
    </div>
    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
        Nilai <strong>Hasil Produksi (Kg)</strong> tampil di dashboard <strong>Rejection in Proses</strong> dan disalin ke baris <strong>Hasil Produksi</strong> pada <strong>Waste NG</strong>, <strong>Waste Gram</strong>, dan <strong>Waste Puntungan</strong> (tanggal sama, Kg saja).
    </p>
</div>
