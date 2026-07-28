<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Root Cause') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('capa.causes.update', $cause) }}">
                        @csrf
                        @method('PUT')

                        <!-- Problem Info (Read-only) -->
                        <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-md">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CAPA Problem</div>
                            <div class="font-mono font-semibold">{{ $cause->problem->problem_number }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ Str::limit($cause->problem->problem_description, 100) }}</div>
                        </div>

                        <!-- Cause Type -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="cause_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Cause Type (5M)
                                </label>
                                <select id="cause_type" name="cause_type"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="" {{ old('cause_type', $cause->cause_type) ? '' : 'selected' }}>- Not set -</option>
                                    <option value="Man" {{ old('cause_type', $cause->cause_type) == 'Man' ? 'selected' : '' }}>Man (Human)</option>
                                    <option value="Machine" {{ old('cause_type', $cause->cause_type) == 'Machine' ? 'selected' : '' }}>Machine (Equipment)</option>
                                    <option value="Material" {{ old('cause_type', $cause->cause_type) == 'Material' ? 'selected' : '' }}>Material</option>
                                    <option value="Method" {{ old('cause_type', $cause->cause_type) == 'Method' ? 'selected' : '' }}>Method (Process)</option>
                                    <option value="Environment" {{ old('cause_type', $cause->cause_type) == 'Environment' ? 'selected' : '' }}>Environment</option>
                                </select>
                                @error('cause_type')
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
                                      placeholder="Describe the root cause in detail...">{{ old('cause_description', $cause->cause_description) }}</textarea>
                            @error('cause_description')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Maximum 1000 characters</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ route('capa.problems.show', $cause->problem) }}" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Update Root Cause
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
