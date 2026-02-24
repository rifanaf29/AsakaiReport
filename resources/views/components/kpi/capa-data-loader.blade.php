@props(['capaAreas'])

<script>
// Load existing CAPA data on page load
document.addEventListener('DOMContentLoaded', function() {
    @if($capaAreas->count() > 0)
        @foreach($capaAreas as $areaIdx => $area)
            {
                const currentAreaIdx = capaAreaCounter;
                addCapaArea();
                
                // Set area date
                const dateInput = document.querySelector(`input[name="capa_areas[${currentAreaIdx}][capa_date]"]`);
                if (dateInput) dateInput.value = {!! json_encode($area->capa_date->format('Y-m-d')) !!};
                
                // Set area name
                const areaSelect = document.getElementById(`capa_area_select_${currentAreaIdx}`);
                const areaInput = document.getElementById(`capa_area_name_${currentAreaIdx}`);
                const areaName = {!! json_encode($area->area_name) !!};
                
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
</script>
