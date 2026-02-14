<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Action Plan') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('capa.action-plans.update', $actionPlan) }}">
                        @csrf
                        @method('PUT')

                        <!-- Problem Info (Read-only) -->
                        <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-md">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CAPA Problem</div>
                            <div class="font-mono font-semibold">{{ $actionPlan->problem->problem_number }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ Str::limit($actionPlan->problem->problem_description, 100) }}</div>
                        </div>

                        <!-- Action Type & Status -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="action_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Action Type <span class="text-red-500">*</span>
                                </label>
                                <select id="action_type" name="action_type" required
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="Corrective" {{ old('action_type', $actionPlan->action_type) == 'Corrective' ? 'selected' : '' }}>Corrective Action</option>
                                    <option value="Preventive" {{ old('action_type', $actionPlan->action_type) == 'Preventive' ? 'selected' : '' }}>Preventive Action</option>
                                </select>
                                @error('action_type')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select id="status" name="status" required
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="Pending" {{ old('status', $actionPlan->status) == 'Pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="In Progress" {{ old('status', $actionPlan->status) == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="Completed" {{ old('status', $actionPlan->status) == 'Completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="Cancelled" {{ old('status', $actionPlan->status) == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                                @error('status')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Action Description -->
                        <div class="mb-6">
                            <label for="action_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Action Description <span class="text-red-500">*</span>
                            </label>
                            <textarea id="action_description" name="action_description" rows="4" required
                                      class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="Describe the action plan in detail...">{{ old('action_description', $actionPlan->action_description) }}</textarea>
                            @error('action_description')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Maximum 1000 characters</p>
                        </div>

                        <!-- Responsible Person & Due Date -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="responsible_person" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Responsible Person <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="responsible_person" name="responsible_person" required
                                       value="{{ old('responsible_person', $actionPlan->responsible_person) }}"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @error('responsible_person')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="due_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Due Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" id="due_date" name="due_date" required
                                       value="{{ old('due_date', $actionPlan->due_date->format('Y-m-d')) }}"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @error('due_date')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Completion Criteria -->
                        <div class="mb-6">
                            <label for="completion_criteria" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Completion Criteria (Optional)
                            </label>
                            <textarea id="completion_criteria" name="completion_criteria" rows="3"
                                      class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="Define criteria for successful completion...">{{ old('completion_criteria', $actionPlan->completion_criteria) }}</textarea>
                            @error('completion_criteria')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Maximum 500 characters</p>
                        </div>

                        <!-- Completion Date (show if status is Completed) -->
                        <div class="mb-6" id="completion_date_field" style="display: {{ old('status', $actionPlan->status) == 'Completed' ? 'block' : 'none' }}">
                            <label for="completion_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Completion Date
                            </label>
                            <input type="date" id="completion_date" name="completion_date"
                                   value="{{ old('completion_date', $actionPlan->completion_date?->format('Y-m-d')) }}"
                                   max="{{ date('Y-m-d') }}"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('completion_date')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Completion Notes -->
                        <div class="mb-6">
                            <label for="completion_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Completion Notes (Optional)
                            </label>
                            <textarea id="completion_notes" name="completion_notes" rows="3"
                                      class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="Add notes about the completion or progress...">{{ old('completion_notes', $actionPlan->completion_notes) }}</textarea>
                            @error('completion_notes')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Maximum 1000 characters</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ route('capa.problems.show', $actionPlan->problem) }}" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                Update Action Plan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Show/hide completion date field based on status
        document.getElementById('status').addEventListener('change', function() {
            const completionDateField = document.getElementById('completion_date_field');
            if (this.value === 'Completed') {
                completionDateField.style.display = 'block';
            } else {
                completionDateField.style.display = 'none';
            }
        });
    </script>
    @endpush
</x-app-layout>
