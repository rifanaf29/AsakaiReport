<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">
            
            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Users</h1>
            </div>

            <!-- Right: Actions -->
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                @can('create users')
                <a href="{{ route('master.users.create') }}" class="btn bg-gray-900 text-gray-100 hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white">
                    <svg class="fill-current shrink-0 xs:hidden" width="16" height="16" viewBox="0 0 16 16">
                        <path d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1z" />
                    </svg>
                    <span class="max-xs:sr-only ml-2">Add User</span>
                </a>
                @endcan
            </div>

        </div>

        <!-- Messages -->
        @if(session('success'))
        <div class="mb-4 px-4 py-3 rounded-lg bg-green-100 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="mb-4 px-4 py-3 rounded-lg bg-red-100 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
        @endif

        <!-- Filters -->
        <div class="mb-5">
            <form method="GET" action="{{ route('master.users.index') }}" class="space-y-4">
                <div class="sm:flex sm:gap-4">
                    <!-- Search -->
                    <div class="flex-1 mb-4 sm:mb-0">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}"
                            placeholder="Search by name or email..." 
                            class="form-input w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"
                        >
                    </div>
                    <!-- Role Filter -->
                    <div class="mb-4 sm:mb-0">
                        <select name="role" class="form-select rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                            <option value="">All Roles</option>
                            @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ request('role') === $role->name ? 'selected' : '' }}>
                                {{ ucfirst($role->name) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Department Filter -->
                    <div class="mb-4 sm:mb-0">
                        <select name="department" class="form-select rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Buttons -->
                    <div class="flex gap-2">
                        <button type="submit" class="btn bg-gray-900 text-gray-100 hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white">
                            Search
                        </button>
                        <a href="{{ route('master.users.index') }}" class="btn border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 text-gray-600 dark:text-gray-300">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl">
            <div class="overflow-x-auto">
                <table class="table-auto w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <!-- Table header -->
                    <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/20 border-t border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3 whitespace-nowrap">
                                <div class="font-semibold text-left">Name</div>
                            </th>
                            <th class="px-4 py-3 whitespace-nowrap">
                                <div class="font-semibold text-left">Email</div>
                            </th>
                            <th class="px-4 py-3 whitespace-nowrap">
                                <div class="font-semibold text-left">Department</div>
                            </th>
                            <th class="px-4 py-3 whitespace-nowrap">
                                <div class="font-semibold text-left">Role</div>
                            </th>
                            <th class="px-4 py-3 whitespace-nowrap">
                                <div class="font-semibold text-center">Access Level</div>
                            </th>
                            <th class="px-4 py-3 whitespace-nowrap">
                                <div class="font-semibold text-center">Actions</div>
                            </th>
                        </tr>
                    </thead>
                    <!-- Table body -->
                    <tbody class="text-sm divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($users as $user)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800 dark:text-gray-100">{{ $user->name }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-gray-600 dark:text-gray-400">{{ $user->email }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-gray-800 dark:text-gray-100">{{ $user->department->name ?? 'N/A' }}</div>
                            </td>
                            <td class="px-4 py-3">
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
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                @if($user->can_access_all_departments)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-200">
                                    All Departments
                                </span>
                                @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    Department Only
                                </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-2">
                                    @can('view users')
                                    <a href="{{ route('master.users.show', $user) }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="View">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    @endcan
                                    @can('edit users')
                                    <a href="{{ route('master.users.edit', $user) }}" class="text-blue-400 hover:text-blue-600" title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    @endcan
                                    @can('delete users')
                                    @if(auth()->id() !== $user->id)
                                    <form action="{{ route('master.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-400 hover:text-red-600" title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                    @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No users found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            @if($users->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $users->links() }}
            </div>
            @endif
        </div>

    </div>
</x-app-layout>
