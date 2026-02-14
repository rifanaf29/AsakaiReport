<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Action Plan Details') }}
            </h2>
            <div class="flex space-x-2">
                @if($actionPlan->problem->status !== 'Closed')
                    @can('edit capa')
                        <a href="{{ route('capa.action-plans.edit', $actionPlan) }}" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            Edit
                        </a>
                    @endcan
                    @can('delete capa')
                        <form action="{{ route('capa.action-plans.destroy', $actionPlan) }}" method="POST" onsubmit="return confirm('Are you sure?')" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                Delete
                            </button>
                        </form>
                    @endcan
                @endif
                <a href="{{ route('capa.problems.show', $actionPlan->problem) }}" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">
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
                    <div class="font-mono font-semibold text-lg mb-2">{{ $actionPlan->problem->problem_number }}</div>
                    <div class="text-gray-700 dark:text-gray-300 mb-2">{{ $actionPlan->problem->problem_description }}</div>
                    <div class="flex space-x-4 text-sm">
                        <span class="text-gray-600 dark:text-gray-400">
                            <span class="font-medium">Department:</span> {{ $actionPlan->problem->department->name }}
                        </span>
                        <span class="text-gray-600 dark:text-gray-400">
                            <span class="font-medium">Area:</span> {{ $actionPlan->problem->area->name }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Action Plan Details -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-start mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Action Plan Information</h3>
                    <div class="flex space-x-2">
                        <span class="px-3 py-1 text-sm font-semibold rounded-full 
                            @if($actionPlan->action_type == 'Corrective') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300
                            @else bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                            @endif">
                            {{ $actionPlan->action_type }}
                        </span>
                        <span class="px-3 py-1 text-sm font-semibold rounded-full 
                            @if($actionPlan->status == 'Completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                            @elseif($actionPlan->status == 'In Progress') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                            @elseif($actionPlan->status == 'Cancelled') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                            @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                            @endif">
                            {{ $actionPlan->status }}
                        </span>
                    </div>
                </div>

                <div class="mb-6">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Action Description</div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-800 dark:text-gray-200 whitespace-pre-wrap">
                        {{ $actionPlan->action_description }}
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Responsible Person</div>
                        <div class="p-3 bg-blue-50 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-md font-semibold">
                            {{ $actionPlan->responsible_person }}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Due Date</div>
                        <div class="p-3 {{ $actionPlan->due_date->isPast() && $actionPlan->status != 'Completed' ? 'bg-red-50 dark:bg-red-900 text-red-800 dark:text-red-200' : 'bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-200' }} rounded-md font-semibold">
                            {{ $actionPlan->due_date->format('Y-m-d') }}
                            @if($actionPlan->due_date->isPast() && $actionPlan->status != 'Completed')
                                <span class="text-xs">(Overdue)</span>
                            @endif
                        </div>
                    </div>
                </div>

                @if($actionPlan->completion_criteria)
                <div class="mb-6">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Completion Criteria</div>
                    <div class="p-4 bg-purple-50 dark:bg-purple-900 rounded-lg text-purple-800 dark:text-purple-200 whitespace-pre-wrap">
                        {{ $actionPlan->completion_criteria }}
                    </div>
                </div>
                @endif

                @if($actionPlan->status == 'Completed' && $actionPlan->completion_date)
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg">
                    <div class="flex items-center mb-2">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div class="font-semibold text-green-800 dark:text-green-200">Completed on {{ $actionPlan->completion_date->format('Y-m-d') }}</div>
                    </div>
                    @if($actionPlan->completion_notes)
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Completion Notes</div>
                        <div class="text-green-800 dark:text-green-300 whitespace-pre-wrap">{{ $actionPlan->completion_notes }}</div>
                    @endif
                </div>
                @elseif($actionPlan->completion_notes)
                <div class="mb-6">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Progress Notes</div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-800 dark:text-gray-200 whitespace-pre-wrap">
                        {{ $actionPlan->completion_notes }}
                    </div>
                </div>
                @endif

                <!-- Record Information -->
                <div class="pt-6 border-t border-gray-200 dark:border-gray-700">
                    <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-4">Record Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                        <div>
                            <div class="text-gray-600 dark:text-gray-400 mb-1">Created By</div>
                            <div>{{ $actionPlan->creator->name ?? 'N/A' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-600 dark:text-gray-400 mb-1">Created At</div>
                            <div>{{ $actionPlan->created_at->format('Y-m-d H:i:s') }}</div>
                        </div>
                        <div>
                            <div class="text-gray-600 dark:text-gray-400 mb-1">Last Updated</div>
                            <div>{{ $actionPlan->updated_at->format('Y-m-d H:i:s') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
