<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit KPI Entry') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('kpi.entries.update', $entry) }}">
                        @csrf
                        @method('PUT')

                        <!-- Template Info (Read-only) -->
                        <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-md">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">KPI Template</div>
                            <div class="text-lg font-semibold">{{ $entry->template->name }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ $entry->department->name }}</div>
                        </div>

                        <!-- Entry Date -->
                        <div class="mb-6">
                            <label for="entry_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Entry Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" id="entry_date" name="entry_date" required
                                   value="{{ old('entry_date', $entry->entry_date->format('Y-m-d')) }}"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('entry_date')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Target & Actual -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="target" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Target <span class="text-red-500">*</span>
                                </label>
                                <input type="number" step="0.01" id="target" name="target" required
                                       value="{{ old('target', $entry->target) }}"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @error('target')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="actual" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Actual <span class="text-red-500">*</span>
                                </label>
                                <input type="number" step="0.01" id="actual" name="actual" required
                                       value="{{ old('actual', $entry->actual) }}"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @error('actual')
                                    <p class="mt-1 text-sm text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Dynamic Fields -->
                        @if($entry->template->fields->count() > 0)
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Additional Fields</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach($entry->template->fields as $field)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ $field->label }} @if($field->is_required)<span class="text-red-500">*</span>@endif
                                    </label>
                                    @php
                                        $fieldValue = old("dynamic_fields.{$field->field_name}", $entry->dynamic_fields[$field->field_name] ?? $field->default_value);
                                    @endphp
                                    @if($field->field_type === 'textarea')
                                        <textarea name="dynamic_fields[{{ $field->field_name }}]" 
                                                  rows="3"
                                                  @if($field->is_required) required @endif
                                                  class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">{{ $fieldValue }}</textarea>
                                    @else
                                        <input type="{{ $field->field_type }}" 
                                               name="dynamic_fields[{{ $field->field_name }}]"
                                               value="{{ $fieldValue }}"
                                               @if($field->field_type === 'number') step="0.01" @endif
                                               @if($field->is_required) required @endif
                                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- Notes/Comments -->
                        <div class="mb-6">
                            <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Notes/Comments
                            </label>
                            <textarea id="notes" name="notes" rows="3"
                                      placeholder="Add any additional notes or comments about this KPI entry..."
                                      class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $entry->notes) }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ route('kpi.entries.index') }}" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Update Entry
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
