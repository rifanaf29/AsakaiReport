<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Add Root Cause') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('capa.causes.store') }}">
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

                        <!-- Cause Category & Analysis Method -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="cause_category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Cause Category (5M) <span class="text-red-500">*</span>
                                </label>
                                <select id="cause_category" name="cause_category" required
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select category...</option>
                                    <option value="Man" {{ old('cause_category') == 'Man' ? 'selected' : '' }}>Man (Human)</option>
                                    <option value="Machine" {{ old('cause_category') == 'Machine' ? 'selected' : '' }}>Machine (Equipment)</option>
                                    <option value="Material" {{ old('cause_category') == 'Material' ? 'selected' : '' }}>Material</option>
                                    <option value="Method" {{ old('cause_category') == 'Method' ? 'selected' : '' }}>Method (Process)</option>
                                    <option value="Environment" {{ old('cause_category') == 'Environment' ? 'selected' : '' }}>Environment</option>
                                    <option value="Other" {{ old('cause_category') == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('cause_category')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="analysis_method" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Analysis Method <span class="text-red-500">*</span>
                                </label>
                                <select id="analysis_method" name="analysis_method" required
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select method...</option>
                                    <option value="5 Whys" {{ old('analysis_method') == '5 Whys' ? 'selected' : '' }}>5 Whys</option>
                                    <option value="Fishbone" {{ old('analysis_method') == 'Fishbone' ? 'selected' : '' }}>Fishbone Diagram</option>
                                    <option value="Pareto" {{ old('analysis_method') == 'Pareto' ? 'selected' : '' }}>Pareto Analysis</option>
                                    <option value="FMEA" {{ old('analysis_method') == 'FMEA' ? 'selected' : '' }}>FMEA</option>
                                    <option value="Other" {{ old('analysis_method') == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('analysis_method')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Cause Description -->
                        <div class="mb-6">
                            <label for="cause_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Root Cause Description <span class="text-red-500">*</span>
                            </label>
                            <textarea id="cause_description" name="cause_description" rows="4" required
                                      class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="Describe the root cause in detail...">{{ old('cause_description') }}</textarea>
                            @error('cause_description')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Maximum 1000 characters</p>
                        </div>

                        <!-- Corrective Action -->
                        <div class="mb-6">
                            <label for="corrective_action" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Corrective Action (Optional)
                            </label>
                            <textarea id="corrective_action" name="corrective_action" rows="4"
                                      class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="Describe the corrective action for this cause...">{{ old('corrective_action') }}</textarea>
                            @error('corrective_action')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Maximum 1000 characters</p>
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
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Add Root Cause
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
