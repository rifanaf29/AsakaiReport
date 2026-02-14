<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Root Cause Details') }}
            </h2>
            <div class="flex space-x-2">
                @if($cause->problem->status !== 'Closed')
                    @can('edit capa')
                        <a href="{{ route('capa.causes.edit', $cause) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Edit
                        </a>
                    @endcan
                    @can('delete capa')
                        <form action="{{ route('capa.causes.destroy', $cause) }}" method="POST" onsubmit="return confirm('Are you sure?')" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                Delete
                            </button>
                        </form>
                    @endcan
                @endif
                <a href="{{ route('capa.problems.show', $cause->problem) }}" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">
                    Back to Problem
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Related Problem -->
            <div class="mb-4 p-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Related Problem</h3>
                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-md">
                    <div class="font-mono font-semibold text-lg mb-2">{{ $cause->problem->problem_number }}</div>
                    <div class="text-gray-700 dark:text-gray-300 mb-2">{{ $cause->problem->problem_description }}</div>
                    <div class="flex space-x-4 text-sm">
                        <span class="text-gray-600 dark:text-gray-400">
                            <span class="font-medium">Department:</span> {{ $cause->problem->department->name }}
                        </span>
                        <span class="text-gray-600 dark:text-gray-400">
                            <span class="font-medium">Area:</span> {{ $cause->problem->area->name }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Cause Details -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-6">Root Cause Information</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Cause Category</div>
                        <div class="p-3 bg-blue-50 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-md font-semibold">
                            {{ $cause->cause_category }}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Analysis Method</div>
                        <div class="p-3 bg-purple-50 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded-md font-semibold">
                            {{ $cause->analysis_method }}
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Root Cause Description</div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-800 dark:text-gray-200 whitespace-pre-wrap">
                        {{ $cause->cause_description }}
                    </div>
                </div>

                @if($cause->corrective_action)
                <div class="mb-6">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Corrective Action</div>
                    <div class="p-4 bg-green-50 dark:bg-green-900 rounded-lg text-green-800 dark:text-green-200 whitespace-pre-wrap">
                        {{ $cause->corrective_action }}
                    </div>
                </div>
                @endif

                <!-- Record Information -->
                <div class="pt-6 border-t border-gray-200 dark:border-gray-700">
                    <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-4">Record Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                        <div>
                            <div class="text-gray-600 dark:text-gray-400 mb-1">Created By</div>
                            <div>{{ $cause->creator->name ?? 'N/A' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-600 dark:text-gray-400 mb-1">Created At</div>
                            <div>{{ $cause->created_at->format('Y-m-d H:i:s') }}</div>
                        </div>
                        <div>
                            <div class="text-gray-600 dark:text-gray-400 mb-1">Last Updated</div>
                            <div>{{ $cause->updated_at->format('Y-m-d H:i:s') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
