<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('KPI Entry Details') }}
            </h2>
            <div class="flex space-x-2">
                @if(!$entry->is_locked)
                    @can('edit kpi')
                        <a href="{{ route('kpi.entries.edit', $entry) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Edit
                        </a>
                    @endcan
                @endif
                <a href="{{ route('kpi.entries.index') }}" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">
                    Back to List
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Entry Status Banner -->
            <div class="mb-4 p-4 rounded-lg {{ $entry->status == 'OK' ? 'bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700' : 'bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold {{ $entry->status == 'OK' ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
                            Status: {{ $entry->status }}
                        </div>
                        <div class="text-sm {{ $entry->status == 'OK' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $entry->entry_date->format('F d, Y') }}
                        </div>
                    </div>
                    @if($entry->is_locked)
                    <div class="flex items-center space-x-2 text-red-600 dark:text-red-400">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-semibold">LOCKED</span>
                    </div>
                    @endif
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <!-- Template & Department Info -->
                    <div class="mb-8 pb-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold mb-4">KPI Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Template</div>
                                <div class="font-semibold">{{ $entry->template->name }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Department</div>
                                <div class="font-semibold">{{ $entry->department->name }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Entry Date</div>
                                <div class="font-semibold">{{ $entry->entry_date->format('Y-m-d') }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Created By</div>
                                <div class="font-semibold">{{ $entry->creator->name ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Metrics -->
                    <div class="mb-8 pb-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold mb-4">Performance Metrics</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="p-4 bg-blue-50 dark:bg-blue-900 rounded-lg">
                                <div class="text-sm text-blue-600 dark:text-blue-400 mb-1">Target</div>
                                <div class="text-3xl font-bold text-blue-800 dark:text-blue-200">{{ number_format($entry->target, 2) }}</div>
                            </div>
                            <div class="p-4 {{ $entry->status == 'OK' ? 'bg-green-50 dark:bg-green-900' : 'bg-red-50 dark:bg-red-900' }} rounded-lg">
                                <div class="text-sm {{ $entry->status == 'OK' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} mb-1">Actual</div>
                                <div class="text-3xl font-bold {{ $entry->status == 'OK' ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">{{ number_format($entry->actual, 2) }}</div>
                            </div>
                            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Achievement</div>
                                <div class="text-3xl font-bold text-gray-800 dark:text-gray-200">
                                    {{ $entry->target > 0 ? number_format(($entry->actual / $entry->target) * 100, 1) : '0' }}%
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Fields -->
                    @if($entry->template->fields->count() > 0 && $entry->dynamic_fields)
                    <div class="mb-8 pb-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold mb-4">Additional Fields</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($entry->template->fields as $field)
                                @if(isset($entry->dynamic_fields[$field->field_name]))
                                <div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ $field->label }}</div>
                                    <div class="font-semibold">{{ $entry->dynamic_fields[$field->field_name] }}</div>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Notes/Comments -->
                    @if($entry->notes)
                    <div class="mb-8 pb-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold mb-4">Notes/Comments</h3>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            {{ $entry->notes }}
                        </div>
                    </div>
                    @endif

                    <!-- Metadata -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Record Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                            <div>
                                <div class="text-gray-600 dark:text-gray-400 mb-1">Created At</div>
                                <div>{{ $entry->created_at->format('Y-m-d H:i:s') }}</div>
                            </div>
                            <div>
                                <div class="text-gray-600 dark:text-gray-400 mb-1">Last Updated</div>
                                <div>{{ $entry->updated_at->format('Y-m-d H:i:s') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
