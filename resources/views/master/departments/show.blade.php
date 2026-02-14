<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="mb-8">
            <div class="sm:flex sm:justify-between sm:items-center">
                <div class="flex items-center gap-4 mb-4 sm:mb-0">
                    <a href="{{ route('master.departments.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Department Details</h1>
                </div>
                <div class="flex gap-2">
                    @can('edit departments')
                    <a href="{{ route('master.departments.edit', $department) }}" class="btn bg-gray-900 text-gray-100 hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white">
                        Edit Department
                    </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-6">
            
            <!-- Department Info Card -->
            <div class="col-span-12 lg:col-span-8">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Department Information</h2>
                        
                        <div class="space-y-4">
                            <!-- Code -->
                            <div class="flex items-start">
                                <div class="w-32 text-sm font-medium text-gray-500 dark:text-gray-400">Code:</div>
                                <div class="flex-1 text-sm text-gray-800 dark:text-gray-100 font-mono">{{ $department->code }}</div>
                            </div>

                            <!-- Name -->
                            <div class="flex items-start">
                                <div class="w-32 text-sm font-medium text-gray-500 dark:text-gray-400">Name:</div>
                                <div class="flex-1 text-sm text-gray-800 dark:text-gray-100">{{ $department->name }}</div>
                            </div>

                            <!-- Description -->
                            <div class="flex items-start">
                                <div class="w-32 text-sm font-medium text-gray-500 dark:text-gray-400">Description:</div>
                                <div class="flex-1 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $department->description ?: 'No description provided' }}
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="flex items-start">
                                <div class="w-32 text-sm font-medium text-gray-500 dark:text-gray-400">Status:</div>
                                <div class="flex-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $department->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $department->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Created At -->
                            <div class="flex items-start">
                                <div class="w-32 text-sm font-medium text-gray-500 dark:text-gray-400">Created:</div>
                                <div class="flex-1 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $department->created_at->format('M d, Y H:i') }}
                                </div>
                            </div>

                            <!-- Updated At -->
                            <div class="flex items-start">
                                <div class="w-32 text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated:</div>
                                <div class="flex-1 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $department->updated_at->format('M d, Y H:i') }}
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
                        
                        <div class="space-y-4">
                            <!-- Total Users -->
                            <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ $department->users->count() }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Total Users</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users List -->
            <div class="col-span-12">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Department Users</h2>
                        
                        @if($department->users->isEmpty())
                        <p class="text-gray-500 dark:text-gray-400 text-center py-8">No users assigned to this department</p>
                        @else
                        <div class="overflow-x-auto">
                            <table class="table-auto w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/20">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Name</th>
                                        <th class="px-4 py-3 text-left">Email</th>
                                        <th class="px-4 py-3 text-left">Role</th>
                                        <th class="px-4 py-3 text-center">Access</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($department->users as $user)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-800 dark:text-gray-100">{{ $user->name }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-gray-600 dark:text-gray-400">{{ $user->email }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex gap-1">
                                                @foreach($user->roles as $role)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200">
                                                    {{ $role->name }}
                                                </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if($user->can_access_all_departments)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-200">
                                                All Departments
                                            </span>
                                            @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                This Department
                                            </span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        @if($department->users->count() >= 10)
                        <div class="mt-4 text-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Showing first 10 users</p>
                        </div>
                        @endif
                        @endif
                    </div>
                </div>
            </div>

        </div>

    </div>
</x-app-layout>
