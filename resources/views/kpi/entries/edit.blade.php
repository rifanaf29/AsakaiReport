<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Edit KPI Entry') }}
            </h2>
            <div class="flex items-center space-x-3">
                @php
                    $entryTemplateDisplay = $entry->template->departments
                        ->firstWhere('id', $entry->department_id)?->pivot?->display_name
                        ?: $entry->template->code;
                @endphp
                <span class="px-3 py-1 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 rounded-full text-sm font-medium">
                    {{ $entryTemplateDisplay }}
                </span>
                <span class="px-3 py-1 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-full text-sm">
                    {{ $entry->department->name }}
                </span>
            </div>
        </div>
    </x-slot>

    <style>
        /* KPI Entries - Edit Form Styles */

        /* Custom scrollbar for CAPA section */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 10px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Dark mode scrollbar */
        .dark .custom-scrollbar::-webkit-scrollbar-track {
            background: #2d3748;
        }
        
        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #4a5568;
        }
        
        .dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #718096;
        }

        /* Card transitions */
        .problem-card, .cause-card, .action-card {
            transition: all 0.2s ease-in-out;
        }
        
        .problem-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        /* Highlight animation for newly added items */
        @keyframes slideInAndHighlight {
            0% {
                opacity: 0;
                transform: translateY(-20px);
            }
            50% {
                opacity: 1;
                transform: translateY(0);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .newly-added {
            animation: slideInAndHighlight 0.5s ease-out;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.28);
        }
        
        .newly-added-fade {
            transition: box-shadow 0.5s ease-out;
            box-shadow: none;
        }

        /* Toast notification */
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            padding: 16px 24px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideInRight 0.3s ease-out;
        }
        
        .dark .toast-notification {
            background: #1f2937;
            color: white;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
        
        .toast-notification.hiding {
            animation: slideOutRight 0.3s ease-in;
        }

        /* Collapse/Expand Animation */
        .capa-area-content {
            max-height: 2000px;
            overflow: hidden;
            transition: max-height 0.4s ease-out, opacity 0.3s ease-out;
            opacity: 1;
        }
        
        .capa-area-content.collapsed {
            max-height: 0;
            opacity: 0;
            transition: max-height 0.3s ease-in, opacity 0.2s ease-in;
        }
        
        .collapse-icon {
            transition: transform 0.3s ease;
        }
        
        .collapse-icon.collapsed {
            transform: rotate(-180deg);
        }
    </style>

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
                                class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-semibold rounded-lg hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all shadow-lg">
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

    <script>
        @include('components.kpi.capa-management-script')
        
        // Initialize CAPA management with areas list
        document.addEventListener('DOMContentLoaded', function() {
            initCapaManagement({!! json_encode($capaAreas->toArray()) !!});
            
            // Load existing CAPA data
            @if($capaAreas_data->count() > 0)
                @foreach($capaAreas_data as $areaIdx => $area)
                    {
                        const currentAreaIdx = capaAreaCounter;
                        const areaName = {!! json_encode($area->area_name) !!};
                        addCapaArea(areaName);
                        
                        // Set area date
                        const dateInput = document.querySelector(`input[name="capa_areas[${currentAreaIdx}][capa_date]"]`);
                        if (dateInput) dateInput.value = {!! json_encode($area->capa_date->format('Y-m-d')) !!};
                        
                        // Set area name
                        const areaSelect = document.getElementById(`capa_area_select_${currentAreaIdx}`);
                        const areaInput = document.getElementById(`capa_area_name_${currentAreaIdx}`);
                        
                        if (areaSelect && areaInput) {
                            const optionExists = Array.from(areaSelect.options).some(opt => opt.value === areaName);
                            if (optionExists) {
                                areaSelect.value = areaName;
                                areaInput.value = areaName;
                            } else {
                                areaSelect.value = '__custom__';
                                areaInput.classList.remove('hidden');
                                areaInput.value = areaName;
                            }
                        }
                        
                        // Set area description
                        const descTextarea = document.querySelector(`textarea[name="capa_areas[${currentAreaIdx}][area_description]"]`);
                        if (descTextarea) descTextarea.value = {!! json_encode($area->area_description ?? '') !!};
                        
                        // Load problems for this area
                        @foreach($area->problems as $problemIdx => $problem)
                            {
                                const problemsContainer = document.getElementById(`problems-container-${currentAreaIdx}`);
                                const currentProblemIdx = problemsContainer ? problemsContainer.children.length : 0;
                                
                                addProblem(currentAreaIdx);
                                
                                // Set problem data
                                const severitySelect = document.querySelector(`#problem-${currentAreaIdx}-${currentProblemIdx} select[name*="severity"]`);
                                if (severitySelect) severitySelect.value = {!! json_encode($problem->severity ?? 'medium') !!};
                                
                                const problemTextarea = document.querySelector(`#problem-${currentAreaIdx}-${currentProblemIdx} textarea[name*="problem_description"]`);
                                if (problemTextarea) problemTextarea.value = {!! json_encode($problem->problem_description ?? '') !!};
                                
                                // Clear default cause
                                const defaultCausesContainer = document.getElementById(`causes-container-${currentAreaIdx}-${currentProblemIdx}`);
                                if (defaultCausesContainer) defaultCausesContainer.innerHTML = '';
                                
                                // Load causes
                                @foreach($problem->causes as $causeIdx => $cause)
                                    {
                                        addCause(currentAreaIdx, currentProblemIdx);
                                        
                                        const causeDescTextarea = document.querySelector(`#cause-${currentAreaIdx}-${currentProblemIdx}-{{ $causeIdx }} textarea[name*="cause_description"]`);
                                        if (causeDescTextarea) causeDescTextarea.value = {!! json_encode($cause->cause_description ?? '') !!};
                                        
                                        // Clear default action
                                        const defaultActionsContainer = document.getElementById(`actions-container-${currentAreaIdx}-${currentProblemIdx}-{{ $causeIdx }}`);
                                        if (defaultActionsContainer) defaultActionsContainer.innerHTML = '';
                                        
                                        // Load action plans
                                        @foreach($cause->actionPlans as $actionIdx => $action)
                                            {
                                                addActionPlan(currentAreaIdx, currentProblemIdx, {{ $causeIdx }});
                                                
                                                setTimeout(() => {
                                                    const actionContainer = document.getElementById(`action-${currentAreaIdx}-${currentProblemIdx}-{{ $causeIdx }}-{{ $actionIdx }}`);
                                                    if (actionContainer) {
                                                        const descInput = actionContainer.querySelector('textarea[name*="[description]"]');
                                                        if (descInput) descInput.value = {!! json_encode($action->description ?? '') !!};
                                                        
                                                        const picInput = actionContainer.querySelector('input[name*="[person_in_charge]"]');
                                                        if (picInput) picInput.value = {!! json_encode($action->person_in_charge ?? '') !!};
                                                        
                                                        const dueDateInput = actionContainer.querySelector('input[name*="[due_date]"]');
                                                        if (dueDateInput) dueDateInput.value = {!! json_encode($action->due_date ? $action->due_date->format('Y-m-d') : '') !!};
                                                        
                                                        const statusSelect = actionContainer.querySelector('select[name*="[status]"]');
                                                        if (statusSelect) statusSelect.value = {!! json_encode($action->status ?? 'open') !!};
                                                        
                                                        const keteranganInput = actionContainer.querySelector('input[name*="[keterangan]"]');
                                                        if (keteranganInput) keteranganInput.value = {!! json_encode($action->keterangan ?? '') !!};
                                                    }
                                                }, 100);
                                            }
                                        @endforeach
                                    }
                                @endforeach
                            }
                        @endforeach
                    }
                @endforeach
            @endif
        });

        document.addEventListener('DOMContentLoaded', function() {
            const actualConfig = {
                mode: {!! json_encode($entry->template->actual_mode ?? 'manual') !!},
                aggregation: {!! json_encode($entry->template->actual_aggregation ?? 'sum') !!},
                fieldKeys: {!! json_encode($entry->template->actual_field_keys ?? []) !!},
            };

            const actualInput = document.getElementById('actual');
            if (!actualInput) return;

            function computeAggregatedActual() {
                if (actualConfig.mode !== 'aggregated') return;

                const values = actualConfig.fieldKeys
                    .map((key) => {
                        const input = document.querySelector(`[name="dynamic_fields[${key}]"]`);
                        return input ? parseFloat(input.value) : NaN;
                    })
                    .filter((value) => Number.isFinite(value));

                if (values.length === 0) {
                    actualInput.value = '';
                    return;
                }

                let result = 0;
                switch (actualConfig.aggregation) {
                    case 'avg':
                        result = values.reduce((sum, value) => sum + value, 0) / values.length;
                        break;
                    case 'min':
                        result = Math.min(...values);
                        break;
                    case 'max':
                        result = Math.max(...values);
                        break;
                    default:
                        result = values.reduce((sum, value) => sum + value, 0);
                        break;
                }

                actualInput.value = Number.isFinite(result) ? result.toFixed(2) : '';
            }

            if (actualConfig.mode === 'aggregated') {
                actualInput.readOnly = true;
                actualInput.required = false;
                actualInput.classList.add('bg-gray-100', 'dark:bg-gray-600', 'cursor-not-allowed');

                document.querySelectorAll('[data-field-key]').forEach((input) => {
                    input.addEventListener('input', computeAggregatedActual);
                });

                computeAggregatedActual();
            }
        });
    </script>
</x-app-layout>