<x-app-layout>
    <div class="px-6 pt-6 mb-6">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create CAPA (Comprehensive)') }}
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Fill all CAPA details, problems, causes, and action plans in one go
        </p>
    </div>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('capa.store-comprehensive') }}" id="capaForm">
                @csrf

                <!-- CAPA Area Section -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">CAPA Area Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="department_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Department <span class="text-red-500">*</span>
                                </label>
                                <select id="department_id" name="department_id" required
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    <option value="">Select department...</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="capa_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    CAPA Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" id="capa_date" name="capa_date" required
                                       value="{{ old('capa_date', date('Y-m-d')) }}"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                @error('capa_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label for="area_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Area Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="area_name" name="area_name" required
                                       value="{{ old('area_name') }}"
                                       placeholder="e.g., Production Line 1, Warehouse Zone A"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                @error('area_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label for="area_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Area Description
                                </label>
                                <textarea id="area_description" name="area_description" rows="2"
                                          placeholder="Brief description of this area..."
                                          class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ old('area_description') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Problems Section -->
                <div id="problems-container"></div>

                <div class="flex items-center justify-between mb-6">
                    <button type="button" onclick="addProblem()" 
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Problem
                    </button>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4">
                    <a href="{{ route('master.capa-areas.index') }}" 
                       class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 font-semibold">
                        Create Complete CAPA
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let problemCounter = 0;

        function addProblem() {
            const container = document.getElementById('problems-container');
            const problemIndex = problemCounter++;
            
            const problemDiv = document.createElement('div');
            problemDiv.className = 'bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6 problem-section';
            problemDiv.id = `problem-${problemIndex}`;
            problemDiv.innerHTML = `
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Problem #${problemIndex + 1}</h3>
                        <button type="button" onclick="removeProblem(${problemIndex})" 
                                class="text-red-600 hover:text-red-800 dark:text-red-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Problem Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="problems[${problemIndex}][problem_number]" required
                                   placeholder="e.g., PROB-2026-001"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Problem Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="problems[${problemIndex}][problem_date]" required
                                   value="${new Date().toISOString().split('T')[0]}"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Priority <span class="text-red-500">*</span>
                            </label>
                            <select name="problems[${problemIndex}][priority]" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Critical">Critical</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Reported By <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="problems[${problemIndex}][reported_by]" required
                                   value="{{ auth()->user()->name }}"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Problem Description <span class="text-red-500">*</span>
                            </label>
                            <textarea name="problems[${problemIndex}][problem_description]" required rows="3"
                                      placeholder="Describe the problem..."
                                      class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"></textarea>
                        </div>
                    </div>

                    <!-- Causes Section -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-md font-semibold text-gray-700 dark:text-gray-200">Causes</h4>
                            <button type="button" onclick="addCause(${problemIndex})" 
                                    class="text-sm px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                                Add Cause
                            </button>
                        </div>
                        <div id="causes-container-${problemIndex}"></div>
                    </div>
                </div>
            `;
            
            container.appendChild(problemDiv);
            addCause(problemIndex); // Add first cause automatically
        }

        function removeProblem(problemIndex) {
            const problem = document.getElementById(`problem-${problemIndex}`);
            if (problem) {
                problem.remove();
            }
        }

        function addCause(problemIndex) {
            const container = document.getElementById(`causes-container-${problemIndex}`);
            const causeCount = container.children.length;
            
            const causeDiv = document.createElement('div');
            causeDiv.className = 'bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4 cause-section';
            causeDiv.id = `cause-${problemIndex}-${causeCount}`;
            causeDiv.innerHTML = `
                <div class="flex items-center justify-between mb-3">
                    <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Cause #${causeCount + 1}</h5>
                    <button type="button" onclick="removeCause(${problemIndex}, ${causeCount})" 
                            class="text-red-600 hover:text-red-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Cause Description <span class="text-red-500">*</span>
                        </label>
                        <textarea name="problems[${problemIndex}][causes][${causeCount}][cause_description]" required rows="2"
                                  placeholder="What caused this problem?"
                                  class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Root Cause Analysis
                        </label>
                        <textarea name="problems[${problemIndex}][causes][${causeCount}][root_cause_analysis]" rows="2"
                                  placeholder="5 Why's, Fishbone, etc..."
                                  class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"></textarea>
                    </div>
                </div>

                <!-- Action Plans Section -->
                <div class="border-t border-gray-300 dark:border-gray-600 pt-3">
                    <div class="flex items-center justify-between mb-3">
                        <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Action Plans</h6>
                        <button type="button" onclick="addActionPlan(${problemIndex}, ${causeCount})" 
                                class="text-xs px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700">
                            Add Action
                        </button>
                    </div>
                    <div id="actions-container-${problemIndex}-${causeCount}"></div>
                </div>
            `;
            
            container.appendChild(causeDiv);
            addActionPlan(problemIndex, causeCount); // Add first action automatically
        }

        function removeCause(problemIndex, causeIndex) {
            const cause = document.getElementById(`cause-${problemIndex}-${causeIndex}`);
            if (cause) {
                cause.remove();
            }
        }

        function addActionPlan(problemIndex, causeIndex) {
            const container = document.getElementById(`actions-container-${problemIndex}-${causeIndex}`);
            const actionCount = container.children.length;
            
            const actionDiv = document.createElement('div');
            actionDiv.className = 'bg-white dark:bg-gray-800 rounded p-3 mb-3 action-section';
            actionDiv.id = `action-${problemIndex}-${causeIndex}-${actionCount}`;
            actionDiv.innerHTML = `
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Action #${actionCount + 1}</span>
                    <button type="button" onclick="removeAction(${problemIndex}, ${causeIndex}, ${actionCount})" 
                            class="text-red-600 hover:text-red-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Action Description <span class="text-red-500">*</span>
                        </label>
                        <textarea name="problems[${problemIndex}][causes][${causeIndex}][action_plans][${actionCount}][description]" 
                                  required rows="2"
                                  placeholder="What action will be taken?"
                                  class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            PIC (Person In Charge) <span class="text-red-500">*</span>
                        </label>
                        <select name="problems[${problemIndex}][causes][${causeIndex}][action_plans][${actionCount}][pic_user_id]" 
                                required
                                class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <option value="">Select PIC...</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Due Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               name="problems[${problemIndex}][causes][${causeIndex}][action_plans][${actionCount}][due_date]" 
                               required
                               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Notes / Remarks
                        </label>
                        <input type="text" 
                               name="problems[${problemIndex}][causes][${causeIndex}][action_plans][${actionCount}][keterangan]"
                               placeholder="Additional notes..."
                               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    </div>
                </div>
            `;
            
            container.appendChild(actionDiv);
        }

        function removeAction(problemIndex, causeIndex, actionIndex) {
            const action = document.getElementById(`action-${problemIndex}-${causeIndex}-${actionIndex}`);
            if (action) {
                action.remove();
            }
        }

        // Add first problem on page load
        document.addEventListener('DOMContentLoaded', function() {
            addProblem();
        });
    </script>
</x-app-layout>
