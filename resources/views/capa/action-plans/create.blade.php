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
                    @if($causes->isEmpty())
                        <div class="mb-6 bg-yellow-100 dark:bg-yellow-900 border border-yellow-400 dark:border-yellow-700 text-yellow-800 dark:text-yellow-300 px-4 py-3 rounded">
                            No open root cause is available. Add a root cause to a problem first.
                        </div>
                        <a href="{{ $problem ? route('capa.problems.show', $problem) : route('capa.problems.index') }}"
                           class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">
                            Back
                        </a>
                    @else
                    <form method="POST" action="{{ route('capa.action-plans.store') }}">
                        @csrf

                        <!-- Root Cause Selection -->
                        <div class="mb-6">
                            <label for="capa_cause_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Root Cause <span class="text-red-500">*</span>
                            </label>
                            <select id="capa_cause_id" name="capa_cause_id" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select a root cause...</option>
                                @foreach($causes as $cause)
                                    <option value="{{ $cause->id }}"
                                        {{ old('capa_cause_id', $selectedCauseId) == $cause->id ? 'selected' : '' }}>
                                        {{ $cause->problem->problem_number }} - {{ Str::limit($cause->cause_description, 60) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('capa_cause_id')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            @if($problem)
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Showing root causes of {{ $problem->problem_number }}.
                                </p>
                            @endif
                        </div>

                        <!-- Action Description -->
                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Action Description <span class="text-red-500">*</span>
                            </label>
                            <textarea id="description" name="description" rows="4" required
                                      class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="Describe the action plan in detail...">{{ old('description') }}</textarea>
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
                                       value="{{ old('person_in_charge') }}"
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
                                       value="{{ old('due_date') }}"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @error('due_date')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Status & Keterangan -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select id="status" name="status" required
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="open" {{ old('status', 'open') == 'open' ? 'selected' : '' }}>Open</option>
                                    <option value="progress" {{ old('status') == 'progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="close" {{ old('status') == 'close' ? 'selected' : '' }}>Closed</option>
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
                                       value="{{ old('keterangan') }}"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @error('keterangan')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ $problem ? route('capa.problems.show', $problem) : url()->previous() }}" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                Add Action Plan
                            </button>
                        </div>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
