<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="mb-8">
            <div class="sm:flex sm:justify-between sm:items-center">
                <div class="flex items-center gap-4 mb-4 sm:mb-0">
                    <a href="{{ route('master.capa-areas.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">CAPA Area Details</h1>
                </div>
                <div class="flex gap-2">
                    @can('create capa')
                    <a href="{{ route('capa.problems.create', ['area' => $capaArea->id]) }}" class="btn bg-indigo-600 text-white hover:bg-indigo-700">
                        <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Problem
                    </a>
                    @endcan
                    @can('edit capa')
                    <a href="{{ route('master.capa-areas.edit', $capaArea) }}" class="btn bg-gray-900 text-gray-100 hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white">
                        Edit Area
                    </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-6">
            
            <!-- Area Info Card -->
            <div class="col-span-12 lg:col-span-8">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Area Information</h2>
                        
                        <div class="space-y-4">
                            <!-- Area Name -->
                            <div class="flex items-start">
                                <div class="w-32 text-sm font-medium text-gray-500 dark:text-gray-400">Area Name:</div>
                                <div class="flex-1 text-sm text-gray-800 dark:text-gray-100">{{ $capaArea->area_name }}</div>
                            </div>

                            <!-- Department -->
                            <div class="flex items-start">
                                <div class="w-32 text-sm font-medium text-gray-500 dark:text-gray-400">Department:</div>
                                <div class="flex-1 text-sm text-gray-800 dark:text-gray-100">
                                    {{ $capaArea->department->name }}
                                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400 font-mono">({{ $capaArea->department->code }})</span>
                                </div>
                            </div>

                            <!-- Description -->
                            @if($capaArea->description)
                            <div class="flex items-start">
                                <div class="w-32 text-sm font-medium text-gray-500 dark:text-gray-400">Description:</div>
                                <div class="flex-1 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $capaArea->description }}
                                </div>
                            </div>
                            @endif

                            <!-- Created At -->
                            <div class="flex items-start">
                                <div class="w-32 text-sm font-medium text-gray-500 dark:text-gray-400">Created:</div>
                                <div class="flex-1 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $capaArea->created_at->format('M d, Y H:i') }}
                                </div>
                            </div>

                            <!-- Updated At -->
                            <div class="flex items-start">
                                <div class="w-32 text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated:</div>
                                <div class="flex-1 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $capaArea->updated_at->format('M d, Y H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Card -->
            <div class="col-span-12 lg:col-span-4">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Statistics</h2>
                        
                        <div class="text-center p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">
                                {{ $capaArea->problems->count() }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Total Problems</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Problems -->
            <div class="col-span-12">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Recent Problems</h2>
                        
                        @if($capaArea->problems->isEmpty())
                        <p class="text-gray-500 dark:text-gray-400 text-center py-8">No problems recorded for this area yet</p>
                        @else
                        <div class="space-y-4">
                            @foreach($capaArea->problems as $problem)
                            <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                <div class="flex items-start justify-between mb-2">
                                    <h3 class="font-medium text-gray-800 dark:text-gray-100">{{ $problem->problem_name }}</h3>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $problem->created_at->format('M d, Y') }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ Str::limit($problem->description, 150) }}</p>
                                <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ $problem->causes->count() }} causes</span>
                                    <span>•</span>
                                    <span>{{ $problem->causes->sum(fn($c) => $c->actionPlans->count()) }} action plans</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        
                        @if($capaArea->problems->count() >= 10)
                        <div class="mt-4 text-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Showing 10 most recent problems</p>
                        </div>
                        @endif
                        @endif
                    </div>
                </div>
            </div>

        </div>

    </div>
</x-app-layout>
