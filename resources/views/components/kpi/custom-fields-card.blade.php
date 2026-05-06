@props(['entry'])

@if($entry->template->fields->count() > 0)
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="px-6 py-4 bg-blue-50 dark:bg-blue-900/20 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Custom Fields ({{ $entry->template->fields->count() }})
            </h3>
            <span class="text-xs text-blue-700 dark:text-blue-300">Additional Parameters</span>
        </div>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-2 gap-4">
            @foreach($entry->template->fields as $field)
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    @php
                        $unitOverride = null;
                        if ($entry->relationLoaded('kpiDefinition') && $entry->kpiDefinition && is_array($entry->kpiDefinition->field_units ?? null)) {
                            $unitOverride = $entry->kpiDefinition->field_units[$field->field_key] ?? null;
                        }
                        $fieldUnit = $unitOverride ?: ($field->unit ?? null);
                    @endphp
                    {{ $field->label }}
                    @if($fieldUnit)
                        <span class="text-xs font-normal text-gray-500 dark:text-gray-400">({{ $fieldUnit }})</span>
                    @endif
                    @if($field->field_type === 'calculated')
                    <span class="text-xs font-normal text-indigo-500 dark:text-indigo-400 ml-1">auto</span>
                @elseif($field->is_required)
                    <span class="text-red-500">*</span>
                @endif
                </label>
                @php
                    $dynamicFields = is_array($entry->dynamic_fields) ? $entry->dynamic_fields : [];
                    $oldValue = old("dynamic_fields.{$field->field_key}");
                    $storedValue = $dynamicFields[$field->field_key] ?? null;
                    $fieldValue = $oldValue ?? $storedValue ?? $field->default_value ?? '';
                    $isCalc = ($field->field_type ?? null) === 'calculated';
                    $isEditable = $isCalc ? false : (bool) ($field->is_editable ?? true);
                @endphp
                @if($isCalc)
                    <input type="number" step="0.01"
                           name="dynamic_fields[{{ $field->field_key }}]"
                           data-calc-edit-field
                           data-field-key="{{ $field->field_key }}"
                           data-calc-formula="{{ $field->calculation_formula ?? '' }}"
                           value="{{ $fieldValue }}"
                           readonly
                           class="w-full px-4 py-3 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 cursor-not-allowed">
                @elseif($field->field_type === 'textarea')
                    <textarea name="dynamic_fields[{{ $field->field_key }}]"
                              data-edit-dynamic-field data-field-key="{{ $field->field_key }}"
                              rows="3"
                              @if($field->is_required) required @endif
                              @if(!$isEditable) readonly @endif
                              class="w-full px-4 py-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-200 @if(!$isEditable) opacity-75 cursor-not-allowed @endif">{{ $fieldValue }}</textarea>
                @else
                    <input type="{{ in_array($field->field_type, ['number','decimal','accounting'], true) ? 'number' : ($field->field_type === 'calculated' ? 'number' : $field->field_type) }}"
                           name="dynamic_fields[{{ $field->field_key }}]"
                           data-edit-dynamic-field data-field-key="{{ $field->field_key }}"
                           value="{{ $fieldValue }}"
                           @if(in_array($field->field_type, ['number','decimal','accounting'], true)) step="0.01" @endif
                           @if($field->is_required) required @endif
                           @if(!$isEditable) readonly @endif
                           class="w-full px-4 py-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-200 @if(!$isEditable) opacity-75 cursor-not-allowed @endif">
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif
