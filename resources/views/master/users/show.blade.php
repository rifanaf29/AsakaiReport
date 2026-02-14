<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="mb-8">
            <div class="sm:flex sm:justify-between sm:items-center">
                <div class="flex items-center gap-4 mb-4 sm:mb-0">
                    <a href="{{ route('master.users.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">User Details</h1>
                </div>
                <div class="flex gap-2">
                    @can('edit users')
                    <a href="{{ route('master.users.edit', $user) }}" class="btn bg-gray-900 text-gray-100 hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white">
                        Edit User
                    </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-6">
            
            <!-- User Info Card -->
            <div class="col-span-12 lg:col-span-8">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">User Information</h2>
                        
                        <div class="space-y-4">
                            <!-- Name -->
                            <div class="flex items-start">
                                <div class="w-32 text-sm font-medium text-gray-500 dark:text-gray-400">Name:</div>
                                <div class="flex-1 text-sm text-gray-800 dark:text-gray-100">{{ $user->name }}</div>
                            </div>

                            <!-- Email -->
                            <div class="flex items-start">
                                <div class="w-32 text-sm font-medium text-gray-500 dark:text-gray-400">Email:</div>
                                <div class="flex-1 text-sm text-gray-800 dark:text-gray-100">{{ $user->email }}</div>
                            </div>

                            <!-- Department -->
                            <div class="flex items-start">
                                <div class="w-32 text-sm font-medium text-gray-500 dark:text-gray-400">Department:</div>
                                <div class="flex-1 text-sm text-gray-800 dark:text-gray-100">
                                    {{ $user->department->name ?? 'N/A' }}
                                    @if($user->department)
                                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400 font-mono">({{ $user->department->code }})</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Role -->
                            <div class="flex items-start">
                                <div class="w-32 text-sm font-medium text-gray-500 dark:text-gray-400">Role:</div>
                                <div class="flex-1">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($user->roles as $role)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            @if($role->name === 'admin') bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-200
                                            @elseif($role->name === 'manager') bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200
                                            @else bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200
                                            @endif">
                                            {{ ucfirst($role->name) }}
                                        </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <!-- Access Level -->
                            <div class="flex items-start">
                                <div class="w-32 text-sm font-medium text-gray-500 dark:text-gray-400">Access Level:</div>
                                <div class="flex-1">
                                    @if($user->can_access_all_departments)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-200">
                                        All Departments
                                    </span>
                                    @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        Department Only
                                    </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Email Verified -->
                            <div class="flex items-start">
                                <div class="w-32 text-sm font-medium text-gray-500 dark:text-gray-400">Email Verified:</div>
                                <div class="flex-1">
                                    @if($user->email_verified_at)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200">
                                        Verified
                                    </span>
                                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $user->email_verified_at->format('M d, Y') }}
                                    </span>
                                    @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200">
                                        Not Verified
                                    </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Created At -->
                            <div class="flex items-start">
                                <div class="w-32 text-sm font-medium text-gray-500 dark:text-gray-400">Created:</div>
                                <div class="flex-1 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $user->created_at->format('M d, Y H:i') }}
                                </div>
                            </div>

                            <!-- Updated At -->
                            <div class="flex items-start">
                                <div class="w-32 text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated:</div>
                                <div class="flex-1 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $user->updated_at->format('M d, Y H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permissions Card -->
            <div class="col-span-12 lg:col-span-4">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Permissions</h2>
                        
                        @if($user->roles->isNotEmpty())
                        @foreach($user->roles as $role)
                        <div class="mb-4">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ ucfirst($role->name) }} Role
                            </h3>
                            <div class="space-y-1 text-xs">
                                @foreach($role->permissions->take(10) as $permission)
                                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                    <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $permission->name }}</span>
                                </div>
                                @endforeach
                                @if($role->permissions->count() > 10)
                                <div class="pt-2 text-gray-500 dark:text-gray-400">
                                    ... and {{ $role->permissions->count() - 10 }} more
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                        @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">No roles assigned</p>
                        @endif
                    </div>
                </div>
            </div>

        </div>

    </div>
</x-app-layout>
