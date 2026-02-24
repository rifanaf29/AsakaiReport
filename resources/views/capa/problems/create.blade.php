<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create CAPA Problem') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('capa.problems.store') }}">
                        @csrf

                        <!-- CAPA Area -->
                        <div class="mb-6">
                            <label for="capa_area_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                CAPA Area <span class="text-red-500">*</span>
                            </label>
                            <select id="capa_area_id" name="capa_area_id" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select an area...</option>
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}" {{ old('capa_area_id', $selectedAreaId ?? null) == $area->id ? 'selected' : '' }}>
                                        {{ $area->area_name }} ({{ $area->department->name }})
                                    </option>
                                @endforeach
                            </select>
                            @error('capa_area_id')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Severity -->
                        <div class="mb-6">
                            <label for="severity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Severity <span class="text-red-500">*</span>
                            </label>
                            <select id="severity" name="severity" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select Severity</option>
                                <option value="low" {{ old('severity') == 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ old('severity') == 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ old('severity') == 'high' ? 'selected' : '' }}>High</option>
                                <option value="critical" {{ old('severity') == 'critical' ? 'selected' : '' }}>Critical</option>
                            </select>
                            @error('severity')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Problem Description -->
                        <div class="mb-6">
                            <label for="problem_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Problem Description <span class="text-red-500">*</span>
                            </label>
                            <textarea id="problem_description" name="problem_description" rows="5" required
                                      class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="Describe the problem in detail...">{{ old('problem_description') }}</textarea>
                            @error('problem_description')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Maximum 1000 characters</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ route('capa.problems.index') }}" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Create Problem
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
