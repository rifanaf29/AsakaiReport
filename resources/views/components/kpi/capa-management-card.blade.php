@props(['capaAreas', 'totalProblems', 'totalCauses', 'totalActions'])

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="px-6 py-4 bg-orange-50 dark:bg-orange-900/20 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                CAPA Management
            </h3>
            @if($capaAreas->count() > 0)
                <span class="px-3 py-1 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-full text-xs font-medium">{{ $capaAreas->count() }} Area(s)</span>
            @else
                <span class="px-3 py-1 bg-gray-50 dark:bg-gray-900/30 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-full text-xs font-medium">No CAPA</span>
            @endif
        </div>
    </div>
    
    <div class="p-6">
        <!-- CAPA Stats Overview -->
        <div class="grid grid-cols-3 gap-3 mb-6">
            <div class="bg-gray-50 dark:bg-gray-900/30 rounded-lg p-3 text-center border border-gray-200 dark:border-gray-700">
                <span class="block text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totalProblems }}</span>
                <span class="text-xs text-gray-600 dark:text-gray-400">Problems</span>
            </div>
            <div class="bg-gray-50 dark:bg-gray-900/30 rounded-lg p-3 text-center border border-gray-200 dark:border-gray-700">
                <span class="block text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totalCauses }}</span>
                <span class="text-xs text-gray-600 dark:text-gray-400">Causes</span>
            </div>
            <div class="bg-gray-50 dark:bg-gray-900/30 rounded-lg p-3 text-center border border-gray-200 dark:border-gray-700">
                <span class="block text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totalActions }}</span>
                <span class="text-xs text-gray-600 dark:text-gray-400">Actions</span>
            </div>
        </div>

        <!-- Add CAPA Area Button -->
        <div class="mb-6 space-y-3">
            <button type="button" onclick="addCapaArea()" 
                    class="w-full inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-semibold rounded-lg hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add CAPA Area
            </button>
            
            @if($capaAreas->count() > 0)
            <div class="flex gap-2">
                <button type="button" onclick="collapseAllCapaAreas()" 
                        class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                    </svg>
                    Collapse All
                </button>
                <button type="button" onclick="expandAllCapaAreas()" 
                        class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                    Expand All
                </button>
            </div>
            @endif
        </div>

        <!-- CAPA Areas Container -->
        <div id="capa-areas-container" class="space-y-6 max-h-[600px] overflow-y-auto pr-2 custom-scrollbar">
            <!-- CAPA areas will be dynamically added here -->
        </div>

        @if($capaAreas->count() == 0)
        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <p class="text-sm text-blue-800 dark:text-blue-300 flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                No CAPA data yet. Click "Add CAPA Area" to start.
            </p>
        </div>
        @endif
    </div>
</div>
