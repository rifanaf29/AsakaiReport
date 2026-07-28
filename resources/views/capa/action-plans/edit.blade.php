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
                            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Root Cause:</span> {{ Str::limit($actionPlan->cause->cause_description, 100) }}
                            </div>
                        </div>

                        <!-- Action Description -->
                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Action Description <span class="text-red-500">*</span>
                            </label>
                            <textarea id="description" name="description" rows="4" required
                                      class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="Describe the action plan in detail...">{{ old('description', $actionPlan->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Maximum 1000 characters</p>
                        </div>

                        <!-- Person in Charge & Due Date -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="person_in_charge" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Person in Charge <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="person_in_charge" name="person_in_charge" required
                                       value="{{ old('person_in_charge', $actionPlan->person_in_charge) }}"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @error('person_in_charge')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="due_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Due Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" id="due_date" name="due_date" required
                                       value="{{ old('due_date', $actionPlan->due_date?->format('Y-m-d')) }}"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @error('due_date')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Status & Notes -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select id="status" name="status" required
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="open" {{ old('status', $actionPlan->status) == 'open' ? 'selected' : '' }}>Open</option>
                                    <option value="progress" {{ old('status', $actionPlan->status) == 'progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="close" {{ old('status', $actionPlan->status) == 'close' ? 'selected' : '' }}>Closed</option>
                                </select>
                                @error('status')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="keterangan" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Keterangan (Optional)
                                </label>
                                <input type="text" id="keterangan" name="keterangan"
                                       value="{{ old('keterangan', $actionPlan->keterangan) }}"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @error('keterangan')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Completed Date (shown when status is Closed) -->
                        <div class="mb-6" id="completed_date_field" style="display: {{ old('status', $actionPlan->status) == 'close' ? 'block' : 'none' }}">
                            <label for="completed_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Completed Date
                            </label>
                            <input type="date" id="completed_date" name="completed_date"
                                   value="{{ old('completed_date', $actionPlan->completed_date?->format('Y-m-d')) }}"
                                   max="{{ date('Y-m-d') }}"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('completed_date')
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
        // Show/hide completed date field based on status
        document.getElementById('status').addEventListener('change', function() {
            document.getElementById('completed_date_field').style.display =
                this.value === 'close' ? 'block' : 'none';
        });
    </script>
    @endpush
</x-app-layout>
