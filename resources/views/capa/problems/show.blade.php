<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('CAPA Problem Details') }}
            </h2>
            <div class="flex space-x-2">
                @if($problem->status !== 'Closed')
                    @can('edit capa')
                        <a href="{{ route('capa.problems.edit', $problem) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Edit Problem
                        </a>
                    @endcan
                @endif
                <a href="{{ route('capa.problems.index') }}" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">
                    Back to List
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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

            <!-- Problem Header -->
            <div class="mb-4 p-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Problem Number</div>
                        <div class="text-2xl font-mono font-bold text-gray-800 dark:text-gray-200">{{ $problem->problem_number }}</div>
                    </div>
                    <div class="flex space-x-4">
                        <div>
                            <span class="px-3 py-1 text-sm font-semibold rounded-full 
                                @if($problem->priority == 'Critical') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                                @elseif($problem->priority == 'High') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300
                                @elseif($problem->priority == 'Medium') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                @else bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                @endif">
                                {{ $problem->priority }}
                            </span>
                        </div>
                        <div>
                            <span class="px-3 py-1 text-sm font-semibold rounded-full 
                                @if($problem->status == 'Closed') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                @elseif($problem->status == 'Resolved') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                @elseif($problem->status == 'In Progress') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                                @endif">
                                {{ $problem->status }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Department</div>
                        <div class="font-semibold">{{ $problem->department->name }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Area</div>
                        <div class="font-semibold">{{ $problem->area->name }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Problem Date</div>
                        <div class="font-semibold">{{ $problem->problem_date->format('Y-m-d') }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Reported By</div>
                        <div class="font-semibold">{{ $problem->reported_by }}</div>
                    </div>
                </div>

                <div class="mt-6">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Problem Description</div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-800 dark:text-gray-200">
                        {{ $problem->problem_description }}
                    </div>
                </div>
            </div>

            <!-- Causes Section -->
            <div class="mb-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Root Causes ({{ $problem->causes->count() }})</h3>
                    @can('create capa')
                        @if($problem->status !== 'Closed')
                            <a href="{{ route('capa.causes.create', ['problem_id' => $problem->id]) }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                                Add Cause
                            </a>
                        @endif
                    @endcan
                </div>
                <div class="p-6">
                    @forelse($problem->causes as $cause)
                        <div class="mb-4 last:mb-0 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-800 dark:text-gray-200 mb-1">{{ $cause->cause_description }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        <span class="font-medium">Category:</span> {{ $cause->cause_category }} | 
                                        <span class="font-medium">Analysis Method:</span> {{ $cause->analysis_method }}
                                    </div>
                                    @if($cause->corrective_action)
                                        <div class="mt-2 text-sm">
                                            <span class="font-medium text-gray-700 dark:text-gray-300">Corrective Action:</span>
                                            <div class="text-gray-600 dark:text-gray-400">{{ $cause->corrective_action }}</div>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex space-x-2 ml-4">
                                    <a href="{{ route('capa.causes.show', $cause) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-sm">View</a>
                                    @can('edit capa')
                                        @if($problem->status !== 'Closed')
                                            <a href="{{ route('capa.causes.edit', $cause) }}" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 text-sm">Edit</a>
                                        @endif
                                    @endcan
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">No root causes added yet.</p>
                    @endforelse
                </div>
            </div>

            <!-- Action Plans Section -->
            <div class="mb-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Action Plans ({{ $problem->actionPlans->count() }})</h3>
                    @can('create capa')
                        @if($problem->status !== 'Closed')
                            <a href="{{ route('capa.action-plans.create', ['problem_id' => $problem->id]) }}" class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
                                Add Action Plan
                            </a>
                        @endif
                    @endcan
                </div>
                <div class="p-6">
                    @forelse($problem->actionPlans as $plan)
                        <div class="mb-4 last:mb-0 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-800 dark:text-gray-200 mb-1">{{ $plan->action_description }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        <span class="font-medium">Type:</span> {{ $plan->action_type }} | 
                                        <span class="font-medium">Responsible:</span> {{ $plan->responsible_person }} | 
                                        <span class="font-medium">Due:</span> {{ $plan->due_date->format('Y-m-d') }}
                                    </div>
                                    <div class="mt-2">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            @if($plan->status == 'Completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                            @elseif($plan->status == 'In Progress') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                            @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                            @endif">
                                            {{ $plan->status }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex space-x-2 ml-4">
                                    <a href="{{ route('capa.action-plans.show', $plan) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-sm">View</a>
                                    @can('edit capa')
                                        @if($problem->status !== 'Closed')
                                            <a href="{{ route('capa.action-plans.edit', $plan) }}" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 text-sm">Edit</a>
                                        @endif
                                    @endcan
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">No action plans added yet.</p>
                    @endforelse
                </div>
            </div>

            <!-- Record Information -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Record Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                    <div>
                        <div class="text-gray-600 dark:text-gray-400 mb-1">Created By</div>
                        <div>{{ $problem->creator->name ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-600 dark:text-gray-400 mb-1">Created At</div>
                        <div>{{ $problem->created_at->format('Y-m-d H:i:s') }}</div>
                    </div>
                    <div>
                        <div class="text-gray-600 dark:text-gray-400 mb-1">Last Updated</div>
                        <div>{{ $problem->updated_at->format('Y-m-d H:i:s') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
