<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Add Action Plan') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('capa.action-plans.store') }}">
                        @csrf

                        <!-- Problem Selection -->
                        <div class="mb-6">
                            <label for="capa_problem_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                CAPA Problem <span class="text-red-500">*</span>
                            </label>
                            <select id="capa_problem_id" name="capa_problem_id" required
                                    @if($problem) disabled @endif
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @if($problem)
                                    <option value="{{ $problem->id }}" selected>{{ $problem->problem_number }} - {{ Str::limit($problem->problem_description, 60) }}</option>
                                @else
                                    <option value="">Select a problem...</option>
                                    @foreach($problems as $prob)
                                        <option value="{{ $prob->id }}" {{ old('capa_problem_id') == $prob->id ? 'selected' : '' }}>
                                            {{ $prob->problem_number }} - {{ Str::limit($prob->problem_description, 60) }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            @if($problem)
                                <input type="hidden" name="capa_problem_id" value="{{ $problem->id }}">
                            @endif
                            @error('capa_problem_id')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Action Type -->
                        <div class="mb-6">
                            <label for="action_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Action Type <span class="text-red-500">*</span>
                            </label>
                            <select id="action_type" name="action_type" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select type...</option>
                                <option value="Corrective" {{ old('action_type', 'Corrective') == 'Corrective' ? 'selected' : '' }}>Corrective Action</option>
                                <option value="Preventive" {{ old('action_type') == 'Preventive' ? 'selected' : '' }}>Preventive Action</option>
                            </select>
                            @error('action_type')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Corrective: Fixes the current problem. Preventive: Prevents future occurrences.
                            </p>
                        </div>

                        <!-- Action Description -->
                        <div class="mb-6">
                            <label for="action_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Action Description <span class="text-red-500">*</span>
                            </label>
                            <textarea id="action_description" name="action_description" rows="4" required
                                      class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="Describe the action plan in detail...">{{ old('action_description') }}</textarea>
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
                                       value="{{ old('responsible_person', auth()->user()->name) }}"
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
                                       value="{{ old('due_date') }}"
                                       min="{{ date('Y-m-d') }}"
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
                                      placeholder="Define criteria for successful completion...">{{ old('completion_criteria') }}</textarea>
                            @error('completion_criteria')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Maximum 500 characters</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-4">
                            @if($problem)
                                <a href="{{ route('capa.problems.show', $problem) }}" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">
                                    Cancel
                                </a>
                            @else
                                <a href="{{ url()->previous() }}" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">
                                    Cancel
                                </a>
                            @endif
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                Add Action Plan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
