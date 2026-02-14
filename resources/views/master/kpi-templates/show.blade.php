<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="mb-8">
            <div class="sm:flex sm:justify-between sm:items-center">
                <div class="flex items-center gap-4 mb-4 sm:mb-0">
                    <a href="{{ route('master.kpi-templates.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">KPI Template Details</h1>
                </div>
                <div class="flex gap-2">
                    @can('edit kpi templates')
                    <a href="{{ route('master.kpi-templates.edit', $kpiTemplate) }}" class="btn bg-gray-900 text-gray-100 hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white">
                        Edit Template
                    </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-6">
            
            <!-- Template Info Card -->
            <div class="col-span-12">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Template Information</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <!-- Code -->
                                <div class="mb-4">
                                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Code</div>
                                    <div class="text-sm text-gray-800 dark:text-gray-100 font-mono mt-1">{{ $kpiTemplate->code }}</div>
                                </div>

                                <!-- Name -->
                                <div class="mb-4">
                                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</div>
                                    <div class="text-sm text-gray-800 dark:text-gray-100 mt-1">{{ $kpiTemplate->name }}</div>
                                </div>

                                <!-- Department -->
                                <div class="mb-4">
                                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Department</div>
                                    <div class="text-sm text-gray-800 dark:text-gray-100 mt-1">
                                        {{ $kpiTemplate->department->name }} 
                                        <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">({{ $kpiTemplate->department->code }})</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <!-- Status -->
                                <div class="mb-4">
                                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</div>
                                    <div class="mt-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $kpiTemplate->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ $kpiTemplate->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Created At -->
                                <div class="mb-4">
                                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ $kpiTemplate->created_at->format('M d, Y H:i') }}
                                    </div>
                                </div>

                                <!-- Updated At -->
                                <div class="mb-4">
                                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ $kpiTemplate->updated_at->format('M d, Y H:i') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        @if($kpiTemplate->description)
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Description</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ $kpiTemplate->description }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Template Fields -->
            <div class="col-span-12">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Template Fields</h2>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $kpiTemplate->fields->count() }} fields</span>
                        </div>
                        
                        @if($kpiTemplate->fields->isEmpty())
                        <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                            No fields configured yet. 
                            @can('edit kpi templates')
                            <a href="{{ route('master.kpi-templates.edit', $kpiTemplate) }}" class="text-blue-600 dark:text-blue-400 hover:underline">Add fields</a>
                            @endcan
                        </p>
                        @else
                        <div class="overflow-x-auto">
                            <table class="table-auto w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/20">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Order</th>
                                        <th class="px-4 py-3 text-left">Field Name</th>
                                        <th class="px-4 py-3 text-left">Type</th>
                                        <th class="px-4 py-3 text-center">Required</th>
                                        <th class="px-4 py-3 text-left">Options</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($kpiTemplate->fields as $field)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <span class="text-gray-600 dark:text-gray-400">{{ $field->sort_order }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="font-medium text-gray-800 dark:text-gray-100">{{ $field->field_name }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200">
                                                {{ ucfirst($field->field_type) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if($field->is_required)
                                            <svg class="w-5 h-5 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            @else
                                            <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($field->field_options)
                                            <code class="text-xs text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-900 px-2 py-1 rounded">
                                                {{ Str::limit($field->field_options, 40) }}
                                            </code>
                                            @else
                                            <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>

    </div>
</x-app-layout>
