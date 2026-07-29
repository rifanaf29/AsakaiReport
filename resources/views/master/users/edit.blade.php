<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <a href="{{ route('master.users.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Edit User</h1>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl">
            <form action="{{ route('master.users.update', $user) }}" method="POST" class="p-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            id="name" 
                            value="{{ old('name', $user->name) }}"
                            class="form-input w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 @error('name') border-red-500 @enderror"
                            required
                        >
                        @error('name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Email Address
                        </label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            value="{{ old('email', $user->email) }}"
                            class="form-input w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 @error('email') border-red-500 @enderror"
                        >
                        @error('email')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Optional. Without an email, SSO matches this account by name.</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            New Password
                        </label>
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            class="form-input w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 @error('password') border-red-500 @enderror"
                        >
                        @error('password')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave blank to keep current password</p>
                    </div>

                    <!-- Password Confirmation -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Confirm New Password
                        </label>
                        <input 
                            type="password" 
                            name="password_confirmation" 
                            id="password_confirmation" 
                            class="form-input w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                        >
                    </div>

                    <!-- Department -->
                    <div>
                        <label for="department_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Department
                        </label>
                        <select
                            name="department_id"
                            id="department_id"
                            class="form-select w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 @error('department_id') border-red-500 @enderror"
                        >
                            <option value="">Select Department</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $user->department_id) == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }} ({{ $dept->code }})
                            </option>
                            @endforeach
                        </select>
                        @error('department_id')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Role -->
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Role
                        </label>
                        <select
                            name="role"
                            id="role"
                            class="form-select w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 @error('role') border-red-500 @enderror"
                        >
                            <option value="">Select Role</option>
                            @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ old('role', $user->roles->first()?->name) === $role->name ? 'selected' : '' }}>
                                {{ ucfirst($role->name) }}
                            </option>
                            @endforeach
                        </select>
                        @error('role')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                <!-- Access Level -->
                <div class="mt-6">
                    <label class="flex items-start">
                        <input 
                            type="checkbox" 
                            name="can_access_all_departments" 
                            value="1"
                            class="form-checkbox mt-0.5 rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                            {{ old('can_access_all_departments', $user->can_access_all_departments) ? 'checked' : '' }}
                        >
                        <span class="ml-2">
                            <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">Can Access All Departments</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Enable this to allow user to view and manage data from all departments</p>
                        </span>
                    </label>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 pt-6 mt-6 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('master.users.index') }}" class="btn border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 text-gray-600 dark:text-gray-300">
                        Cancel
                    </a>
                    <button type="submit" class="btn bg-gray-900 text-gray-100 hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white">
                        Update User
                    </button>
                </div>

            </form>
        </div>

    </div>
</x-app-layout>
