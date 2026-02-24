<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Monthly KPI Targets</h1>
                @can('create kpi templates')
                <a href="{{ route('master.kpi-monthly-targets.create') }}" 
                   class="btn bg-gray-900 text-gray-100 hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white">
                    <svg class="w-4 h-4 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Set Monthly Targets
                </a>
                @endcan
            </div>
        </div>

        @if (session('success'))
        <div class="mb-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
        @endif

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl mb-6 p-6">
            <form method="GET" action="{{ route('master.kpi-monthly-targets.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Year</label>
                    <select name="year" class="form-select w-full rounded-lg">
                        @for($y = date('Y') + 1; $y >= date('Y') - 2; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Department</label>
                    <select name="department" class="form-select w-full rounded-lg">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit" class="btn bg-indigo-500 hover:bg-indigo-600 text-white w-full">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Targets Table -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl">
            <div class="overflow-x-auto">
                <table class="table-auto w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/20 border-t border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3 whitespace-nowrap text-left font-semibold">Template</th>
                            <th class="px-4 py-3 whitespace-nowrap text-left font-semibold">Department</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center font-semibold">Month</th>
                            <th class="px-4 py-3 whitespace-nowrap text-right font-semibold">Target</th>
                            <th class="px-4 py-3 whitespace-nowrap text-left font-semibold">Set By</th>
                            <th class="px-4 py-3 whitespace-nowrap text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($targets as $target)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($target->template)
                                    <div class="font-medium text-gray-800 dark:text-gray-100">{{ $target->template->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $target->template->code }}</div>
                                @else
                                    <div class="font-medium text-red-600 dark:text-red-400">Template Deleted</div>
                                    <div class="text-xs text-gray-500">ID: {{ $target->kpi_template_id }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($target->template && $target->template->department)
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                        {{ $target->template->department->name }}
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                        N/A
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                {{ \Carbon\Carbon::create($target->target_year, $target->target_month, 1)->format('F Y') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right font-semibold text-gray-900 dark:text-gray-100">
                                {{ number_format($target->target_value, 2) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-600 dark:text-gray-400">
                                {{ $target->creator->name ?? 'System' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @can('edit kpi templates')
                                    @if($target->template)
                                    <a href="{{ route('master.kpi-monthly-targets.edit', $target) }}" 
                                       class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                                       title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    @endif
                                    @endcan

                                    @can('delete kpi templates')
                                    <form action="{{ route('master.kpi-monthly-targets.destroy', $target) }}" method="POST" 
                                          onsubmit="return confirm('Delete this target?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-700 dark:text-red-400" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No monthly targets set for {{ $year }}. Click "Set Monthly Targets" to create them.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($targets->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $targets->links() }}
            </div>
            @endif
        </div>

    </div>
</x-app-layout>
