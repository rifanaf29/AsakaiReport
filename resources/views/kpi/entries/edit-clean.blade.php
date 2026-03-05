<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Edit KPI Entry') }}
            </h2>
            <div class="flex items-center space-x-3">
                <span class="px-3 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-full text-sm font-medium">
                    @php
                        $entryTemplateDisplay = $entry->template->departments
                            ->firstWhere('id', $entry->department_id)?->pivot?->display_name
                            ?: $entry->template->code;
                    @endphp
                    {{ $entryTemplateDisplay }}
                </span>
                <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full text-sm">
                    {{ $entry->department->name }}
                </span>
            </div>
        </div>
    </x-slot>

    @push('styles')
    <link rel="stylesheet" href="{{ asset('css/additional-styles/kpi-entries.css') }}">
    @endpush

    <div class="py-6">
        <div class="max-w mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('kpi.entries.update', $entry) }}" class="space-y-6">
                @csrf
                @method('PUT')
                
                @php
                    $capaAreas_data = $entry->capaAreas;
                    $totalProblems = $capaAreas_data->sum(function($area) { return $area->problems->count(); });
                    $totalCauses = $capaAreas_data->sum(function($area) { return $area->problems->sum(function($p) { return $p->causes->count(); }); });
                    $totalActions = $capaAreas_data->sum(function($area) { return $area->problems->sum(function($p) { return $p->causes->sum(function($c) { return $c->actionPlans->count(); }); }); });
                @endphp

                <!-- Breadcrumb -->
                <div class="flex items-center text-sm mb-6">
                    <a href="{{ route('kpi.entries.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        KPI Entries
                    </a>
                    <svg class="w-4 h-4 mx-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-gray-700 dark:text-gray-300 font-medium">Edit Entry</span>
                </div>
                
                <!-- Equal 2-Column Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left Column: KPI Data -->
                    <div class="space-y-6">
                        <x-kpi.entry-form-card :entry="$entry" />
                        <x-kpi.custom-fields-card :entry="$entry" />
                        <x-kpi.notes-card :entry="$entry" />
                    </div>

                    <!-- Right Column: CAPA Section -->
                    <div class="space-y-6">
                        <x-kpi.capa-management-card 
                            :capaAreas="$capaAreas_data"
                            :totalProblems="$totalProblems"
                            :totalCauses="$totalCauses"
                            :totalActions="$totalActions" />
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 px-6 py-4">
                    <a href="{{ route('kpi.entries.index') }}" 
                       class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Cancel
                    </a>
                    <div class="flex items-center space-x-3">
                        <button type="submit" 
                                class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white text-sm font-semibold rounded-lg hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all shadow-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Update KPI Entry
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script src="{{ asset('js/kpi-capa-management.js') }}"></script>
    <script>
        // Initialize CAPA management with areas list
        initCapaManagement({!! json_encode($capaAreas->toArray()) !!});
    </script>
    <x-kpi.capa-data-loader :capaAreas="$capaAreas_data" />
    @endpush
</x-app-layout>
