<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('CAPA Management') }} - {{ $problem->problem_number }}
            </h2>
            <div class="flex space-x-2">
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
                <form action="{{ route('capa.problems.update', $problem) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Problem Number</div>
                            <div class="text-2xl font-mono font-bold text-gray-800 dark:text-gray-200">{{ $problem->problem_number }}</div>
                        </div>
                        <div class="flex items-center space-x-4">
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
                                    @elseif($problem->status == 'In Progress') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                    @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                                    @endif">
                                    {{ $problem->status }}
                                </span>
                            </div>
                            @can('edit capa')
                                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                                    Save Changes
                                </button>
                            @endcan
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Department</div>
                            <div class="font-semibold">{{ $problem->department?->name ?? '-' }}</div>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-2">Area</label>
                            <select name="capa_area_id" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}" {{ $problem->capa_area_id == $area->id ? 'selected' : '' }}>
                                        {{ $area->area_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-2">Severity</label>
                            <select name="severity" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                <option value="low" {{ $problem->severity == 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ $problem->severity == 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ $problem->severity == 'high' ? 'selected' : '' }}>High</option>
                                <option value="critical" {{ $problem->severity == 'critical' ? 'selected' : '' }}>Critical</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-2">Problem Description</label>
                        <textarea name="problem_description" rows="4" required
                                  class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $problem->problem_description }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-600 dark:text-gray-400">
                        <div>
                            <span class="font-medium">Created By:</span> {{ $problem->creator->name ?? 'N/A' }}
                        </div>
                        <div>
                            <span class="font-medium">Created At:</span> {{ $problem->created_at->format('Y-m-d H:i') }}
                        </div>
                    </div>
                </form>
            </div>

            <!-- Root Causes Section -->
            <div class="mb-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Root Causes ({{ $problem->causes->count() }})</h3>
                    @can('create capa')
                        <button onclick="addCause()" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                            + Add Root Cause
                        </button>
                    @endcan
                </div>
                <div class="p-6 space-y-4" id="causes-container">
                    @forelse($problem->causes as $index => $cause)
                        <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg cause-item" data-cause-id="{{ $cause->id }}">
                            <form class="cause-form" onsubmit="updateCause(event, {{ $cause->id }})">
                                <div class="flex justify-between items-start mb-3">
                                    <h4 class="font-semibold text-gray-800 dark:text-gray-200">Root Cause #{{ $index + 1 }}</h4>
                                    <div class="flex space-x-2">
                                        @can('edit capa')
                                            <button type="submit" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 text-sm">Save</button>
                                        @endcan
                                        @can('delete capa')
                                            <button type="button" onclick="deleteCause({{ $cause->id }})" class="text-red-600 hover:text-red-800 dark:text-red-400 text-sm">Delete</button>
                                        @endcan
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Cause Description</label>
                                        <textarea name="cause_description" rows="2" required
                                                  class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm">{{ $cause->cause_description }}</textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Cause Type</label>
                                        <select name="cause_type" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm">
                                            <option value="" {{ $cause->cause_type ? '' : 'selected' }}>- Not set -</option>
                                            <option value="Man" {{ $cause->cause_type == 'Man' ? 'selected' : '' }}>Man</option>
                                            <option value="Machine" {{ $cause->cause_type == 'Machine' ? 'selected' : '' }}>Machine</option>
                                            <option value="Material" {{ $cause->cause_type == 'Material' ? 'selected' : '' }}>Material</option>
                                            <option value="Method" {{ $cause->cause_type == 'Method' ? 'selected' : '' }}>Method</option>
                                            <option value="Environment" {{ $cause->cause_type == 'Environment' ? 'selected' : '' }}>Environment</option>
                                        </select>
                                    </div>
                                </div>
                            </form>

                            <!-- Action Plans for this Cause (outside the cause form: nested forms are dropped by the browser) -->
                            <div class="mt-4 pl-4 border-l-2 border-blue-200 dark:border-blue-800">
                                <div class="flex justify-between items-center mb-2">
                                    <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Action Plans ({{ $cause->actionPlans->count() }})</h5>
                                    @can('create capa')
                                        <button type="button" onclick="addActionPlan({{ $cause->id }})" class="text-green-600 hover:text-green-800 text-xs">
                                            + Add Action
                                        </button>
                                    @endcan
                                </div>
                                <div class="space-y-2 action-plans-container" data-cause-id="{{ $cause->id }}">
                                        @foreach($cause->actionPlans as $plan)
                                            <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded action-item" data-action-id="{{ $plan->id }}">
                                                <form class="action-form" onsubmit="updateAction(event, {{ $plan->id }})">
                                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-2">
                                                        <div class="md:col-span-2">
                                                            <input type="text" name="description" placeholder="Action description" required
                                                                   value="{{ $plan->description }}"
                                                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm">
                                                        </div>
                                                        <div>
                                                            <input type="text" name="person_in_charge" placeholder="Person in Charge" required
                                                                   value="{{ $plan->person_in_charge }}"
                                                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm">
                                                        </div>
                                                        <div>
                                                            <input type="date" name="due_date" required
                                                                   value="{{ $plan->due_date->format('Y-m-d') }}"
                                                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm">
                                                        </div>
                                                    </div>
                                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                                        <div>
                                                            <select name="status" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm">
                                                                <option value="open" {{ $plan->status == 'open' ? 'selected' : '' }}>Open</option>
                                                                <option value="progress" {{ $plan->status == 'progress' ? 'selected' : '' }}>In Progress</option>
                                                                <option value="close" {{ $plan->status == 'close' ? 'selected' : '' }}>Closed</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <input type="text" name="keterangan" placeholder="Notes (optional)"
                                                                   value="{{ $plan->keterangan }}"
                                                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm">
                                                        </div>
                                                        <div class="flex justify-end space-x-2">
                                                            <button type="submit" class="text-indigo-600 hover:text-indigo-800 text-xs">Save</button>
                                                            <button type="button" onclick="deleteAction({{ $plan->id }})" class="text-red-600 hover:text-red-800 text-xs">Delete</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">No root causes added yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function addCause() {
            const form = document.createElement('form');
            form.className = 'cause-form p-4 border border-blue-300 dark:border-blue-700 rounded-lg bg-blue-50 dark:bg-blue-900/20';
            form.onsubmit = (e) => createCause(e);
            
            form.innerHTML = `
                <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3">New Root Cause</h4>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Cause Description</label>
                        <textarea name="cause_description" rows="2" required
                                  class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Cause Type</label>
                        <select name="cause_type" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm">
                            <option value="">- Not set -</option>
                            <option value="Man">Man</option>
                            <option value="Machine">Machine</option>
                            <option value="Material">Material</option>
                            <option value="Method">Method</option>
                            <option value="Environment">Environment</option>
                        </select>
                    </div>
                    <div class="flex space-x-2">
                        <button type="submit" class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">Save</button>
                        <button type="button" onclick="this.closest('form').remove()" class="px-3 py-1 bg-gray-400 text-white text-sm rounded hover:bg-gray-500">Cancel</button>
                    </div>
                </div>
            `;
            
            document.getElementById('causes-container').appendChild(form);
        }

        /** POST to a CAPA endpoint and surface the server's validation message on failure. */
        async function capaSubmit(url, body) {
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: body
                });

                if (response.ok) return true;

                let message = `Request failed (${response.status})`;
                try {
                    const data = await response.json();
                    if (data.errors) {
                        message = Object.values(data.errors).flat().join('\n');
                    } else if (data.message) {
                        message = data.message;
                    }
                } catch (e) {
                    // Non-JSON error response (e.g. HTML error page) - keep the status message.
                }
                alert(message);
                return false;
            } catch (error) {
                alert('Error: ' + error.message);
                return false;
            }
        }

        async function createCause(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            formData.append('capa_problem_id', {{ $problem->id }});

            if (await capaSubmit('{{ route("capa.causes.store") }}', formData)) {
                location.reload();
            }
        }

        async function updateCause(event, causeId) {
            event.preventDefault();
            const formData = new FormData(event.target);
            formData.append('_method', 'PUT');

            if (await capaSubmit(`/capa/causes/${causeId}`, formData)) {
                alert('Cause updated successfully');
            }
        }

        async function deleteCause(causeId) {
            if (!confirm('Are you sure you want to delete this cause and all its action plans?')) return;

            const body = new URLSearchParams({ '_method': 'DELETE' });
            if (await capaSubmit(`/capa/causes/${causeId}`, body)) {
                location.reload();
            }
        }

        function addActionPlan(causeId) {
            const container = document.querySelector(`.action-plans-container[data-cause-id="${causeId}"]`);
            const form = document.createElement('div');
            form.className = 'p-3 bg-green-50 dark:bg-green-900/20 rounded border border-green-300 dark:border-green-700';
            
            form.innerHTML = `
                <form class="action-form" onsubmit="createAction(event, ${causeId})">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-2">
                        <div class="md:col-span-2">
                            <input type="text" name="description" placeholder="Action description" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm">
                        </div>
                        <div>
                            <input type="text" name="person_in_charge" placeholder="Person in Charge" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm">
                        </div>
                        <div>
                            <input type="date" name="due_date" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                        <div>
                            <select name="status" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm">
                                <option value="open">Open</option>
                                <option value="progress">In Progress</option>
                                <option value="close">Closed</option>
                            </select>
                        </div>
                        <div>
                            <input type="text" name="keterangan" placeholder="Notes (optional)"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm">
                        </div>
                        <div class="flex justify-end space-x-2">
                            <button type="submit" class="px-2 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700">Save</button>
                            <button type="button" onclick="this.closest('div').remove()" class="px-2 py-1 bg-gray-400 text-white text-xs rounded hover:bg-gray-500">Cancel</button>
                        </div>
                    </div>
                </form>
            `;
            
            container.appendChild(form);
        }

        async function createAction(event, causeId) {
            event.preventDefault();
            const formData = new FormData(event.target);
            formData.append('capa_cause_id', causeId);

            if (await capaSubmit('{{ route("capa.action-plans.store") }}', formData)) {
                location.reload();
            }
        }

        async function updateAction(event, actionId) {
            event.preventDefault();
            const formData = new FormData(event.target);
            formData.append('_method', 'PUT');

            if (await capaSubmit(`/capa/action-plans/${actionId}`, formData)) {
                alert('Action plan updated successfully');
                location.reload();
            }
        }

        async function deleteAction(actionId) {
            if (!confirm('Are you sure you want to delete this action plan?')) return;

            const body = new URLSearchParams({ '_method': 'DELETE' });
            if (await capaSubmit(`/capa/action-plans/${actionId}`, body)) {
                location.reload();
            }
        }
    </script>
    @endpush
</x-app-layout>
