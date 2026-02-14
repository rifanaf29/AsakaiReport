<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <a href="{{ route('master.capa-areas.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Create CAPA Area</h1>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl">
            <form action="{{ route('master.capa-areas.store') }}" method="POST" class="p-6">
                @csrf

                <!-- Area Name -->
                <div class="mb-6">
                    <label for="area_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Area Name <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="area_name" 
                        id="area_name" 
                        value="{{ old('area_name') }}"
                        class="form-input w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 @error('area_name') border-red-500 @enderror"
                        placeholder="e.g., Production Line 1, Quality Control" 
                        required
                    >
                    @error('area_name')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Department -->
                <div class="mb-6">
                    <label for="department_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Department <span class="text-red-500">*</span>
                    </label>
                    <select 
                        name="department_id" 
                        id="department_id" 
                        class="form-select w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 @error('department_id') border-red-500 @enderror"
                        required
                    >
                        <option value="">Select Department</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }} ({{ $dept->code }})
                        </option>
                        @endforeach
                    </select>
                    @error('department_id')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="mb-6">
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Description
                    </label>
                    <textarea 
                        name="description" 
                        id="description" 
                        rows="4"
                        class="form-textarea w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 @error('description') border-red-500 @enderror"
                        placeholder="Brief description of this CAPA area"
                    >{{ old('description') }}</textarea>
                    @error('description')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('master.capa-areas.index') }}" class="btn border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 text-gray-600 dark:text-gray-300">
                        Cancel
                    </a>
                    <button type="submit" class="btn bg-gray-900 text-gray-100 hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white">
                        Create CAPA Area
                    </button>
                </div>

            </form>
        </div>

    </div>
</x-app-layout>
