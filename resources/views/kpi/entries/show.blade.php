<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('KPI Entry Details') }}
            </h2>
            <div class="flex items-center space-x-3">
                <span class="px-3 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-full text-sm font-medium">
                    {{ $entry->template->name }}
                </span>
                <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full text-sm">
                    {{ $entry->department->name }}
                </span>
            </div>
        </div>
    </x-slot>

    <style>
        .capa-area-content {
            max-height: 2000px;
            overflow: hidden;
            transition: max-height 0.4s ease-out, opacity 0.3s ease-out;
            opacity: 1;
        }
        
        .capa-area-content.collapsed {
            max-height: 0;
            opacity: 0;
            transition: max-height 0.3s ease-in, opacity 0.2s ease-in;
        }
        
        .collapse-icon {
            transition: transform 0.3s ease;
        }
        
        .collapse-icon.collapsed {
            transform: rotate(-180deg);
        }
    </style>

    <div class="py-6">
        <div class="max-w mx-auto sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <div class="flex items-center text-sm mb-6">
                <a href="{{ route('kpi.entries.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    KPI Entries
                </a>
                <svg class="w-4 h-4 mx-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-gray-700 dark:text-gray-300 font-medium">Entry Details</span>
            </div>

            @if (session('success'))
                <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 px-5 py-4 rounded-xl shadow-sm">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 px-5 py-4 rounded-xl shadow-sm">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            <!-- Status Banner -->
            <div class="mb-6 bg-gradient-to-r {{ $entry->status == 'OK' ? 'from-green-600 to-emerald-600' : 'from-red-600 to-pink-600' }} rounded-xl shadow-xl overflow-hidden">
                <div class="p-5 flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="bg-white/15 rounded-full p-3">
                            @if($entry->status == 'OK')
                                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @else
                                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @endif
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-white mb-0.5">
                                Status: {{ $entry->status }}
                            </div>
                            <div class="text-white/90 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ $entry->entry_date->format('F d, Y') }}
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-4xl font-bold text-white/90 mb-0.5">
                            {{ $entry->target > 0 ? number_format(($entry->actual / $entry->target) * 100, 0) : '0' }}%
                        </div>
                        <div class="text-sm text-white/80">Achievement</div>
                    </div>
                </div>
            </div>

            <!-- 2-Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Left Column -->
                <div class="space-y-6">
                    <!-- KPI Information Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 bg-blue-50 dark:bg-blue-900/20 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                KPI Information
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 gap-5">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Template</div>
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $entry->template->name }}</div>
                                    </div>
                                </div>
                                
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Department</div>
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $entry->department->name }}</div>
                                    </div>
                                </div>
                                
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Entry Date</div>
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $entry->entry_date->format('Y-m-d') }}</div>
                                    </div>
                                </div>
                                
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Created By</div>
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $entry->creator->name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Metrics Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 bg-purple-50 dark:bg-purple-900/20 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                Performance Metrics
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <!-- Target -->
                                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-5 border border-blue-200 dark:border-blue-800">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-blue-700 dark:text-blue-300">Target Value</span>
                                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                        </svg>
                                    </div>
                                    <div class="text-3xl font-bold text-blue-900 dark:text-blue-100">
                                        {{ number_format($entry->target, 2) }}
                                    </div>
                                </div>
                                
                                <!-- Actual -->
                                <div class="{{ $entry->status == 'OK' ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' }} rounded-xl p-5 border">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium {{ $entry->status == 'OK' ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">Actual Value</span>
                                        @if($entry->status == 'OK')
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="text-3xl font-bold {{ $entry->status == 'OK' ? 'text-green-900 dark:text-green-100' : 'text-red-900 dark:text-red-100' }}">
                                        {{ number_format($entry->actual, 2) }}
                                    </div>
                                </div>
                                
                                <!-- Gap Analysis -->
                                @php
                                    $gap = $entry->actual - $entry->target;
                                    $achievement = $entry->target > 0 ? ($entry->actual / $entry->target) * 100 : 0;
                                @endphp
                                <div class="bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-800/50 dark:to-slate-800/50 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Gap Analysis</span>
                                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                                        </svg>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Gap:</span>
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format($gap, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Achievement:</span>
                                            <span class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($achievement, 1) }}%</span>
                                        </div>
                                        <!-- Progress Bar -->
                                        <div class="mt-3 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500 {{ $achievement >= 100 ? 'bg-gradient-to-r from-green-500 to-emerald-600' : ($achievement >= 80 ? 'bg-gradient-to-r from-yellow-400 to-orange-500' : 'bg-gradient-to-r from-red-500 to-pink-600') }}" 
                                                 style="width: {{ min($achievement, 100) }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Fields -->
                    @if($entry->template->fields->count() > 0 && $entry->dynamic_fields)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 bg-teal-50 dark:bg-teal-900/20 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                </svg>
                                Additional Fields
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                @foreach($entry->template->fields as $field)
                                    @if(isset($entry->dynamic_fields[$field->field_key]))
                                    <div class="flex items-start space-x-3 pb-4 border-b border-gray-100 dark:border-gray-700 last:border-0 last:pb-0">
                                        <div class="flex-shrink-0 w-8 h-8 bg-teal-100 dark:bg-teal-900/30 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $field->label }}</div>
                                            <div class="mt-1 text-gray-900 dark:text-gray-100">{{ $entry->dynamic_fields[$field->field_key] }}</div>
                                        </div>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Notes/Comments -->
                    @if($entry->notes)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 bg-amber-50 dark:bg-amber-900/20 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                </svg>
                                Notes & Comments
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
                                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $entry->notes }}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Record Information -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/30 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Record Information
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Created At</div>
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $entry->created_at->format('Y-m-d H:i:s') }}</div>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Last Updated</div>
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $entry->updated_at->format('Y-m-d H:i:s') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: CAPA Section -->
                <div class="space-y-6">
                    @php
                        $capaAreas = $entry->capaAreas;
                        $totalProblems = $capaAreas->sum(function($area) { return $area->problems->count(); });
                        $totalCauses = $capaAreas->sum(function($area) { return $area->problems->sum(function($p) { return $p->causes->count(); }); });
                        $totalActions = $capaAreas->sum(function($area) { return $area->problems->sum(function($p) { return $p->causes->sum(function($c) { return $c->actionPlans->count(); }); }); });
                    @endphp
                    
                    @if($capaAreas->count() > 0)
                    <!-- CAPA Summary Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-orange-200 dark:border-orange-800 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                CAPA Overview
                            </h3>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 text-center border border-orange-200 dark:border-orange-800">
                                <div class="text-3xl font-bold text-orange-800 dark:text-orange-200 mb-1">{{ $totalProblems }}</div>
                                <div class="text-xs text-orange-700 dark:text-orange-300">{{ Str::plural('Problem', $totalProblems) }}</div>
                            </div>
                            <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 text-center border border-orange-200 dark:border-orange-800">
                                <div class="text-3xl font-bold text-orange-800 dark:text-orange-200 mb-1">{{ $totalCauses }}</div>
                                <div class="text-xs text-orange-700 dark:text-orange-300">{{ Str::plural('Cause', $totalCauses) }}</div>
                            </div>
                            <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 text-center border border-orange-200 dark:border-orange-800">
                                <div class="text-3xl font-bold text-orange-800 dark:text-orange-200 mb-1">{{ $totalActions }}</div>
                                <div class="text-xs text-orange-700 dark:text-orange-300">{{ Str::plural('Action', $totalActions) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- CAPA Areas -->
                    @foreach($capaAreas as $areaIdx => $area)
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-orange-200 dark:border-orange-800 overflow-hidden shadow-lg">
                        <div class="px-5 py-4 bg-orange-50 dark:bg-orange-900/20 flex items-center justify-between cursor-pointer" onclick="toggleArea({{ $areaIdx }})">
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>  
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                CAPA Area ({{ $area->area_name }})
                            </h4>
                            <button type="button" class="text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white transition-colors">
                                <svg id="area-icon-{{ $areaIdx }}" class="w-5 h-5 collapse-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </div>
                        
                        <div id="area-content-{{ $areaIdx }}" class="capa-area-content">
                            <div class="p-6 space-y-4">
                                <!-- Area Info -->
                                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                                        <div>
                                            <div class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">CAPA Date</div>
                                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $area->capa_date->format('d M Y') }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Area Name</div>
                                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $area->area_name }}</div>
                                        </div>
                                    </div>
                                    @if($area->area_description)
                                    <div>
                                        <div class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Description</div>
                                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $area->area_description }}</p>
                                    </div>
                                    @endif
                                </div>

                                <!-- Problems -->
                                @foreach($area->problems as $problemIdx => $problem)
                                <div class="bg-white dark:bg-gray-800 rounded-xl border border-red-200 dark:border-red-800 overflow-hidden shadow-md">
                                    <div class="px-4 py-3 bg-red-50 dark:bg-red-900/20 border-b border-red-200 dark:border-red-800">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-4 h-4 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                </svg>
                                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">Problem #{{ $problemIdx + 1 }}</span>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <span class="px-2 py-0.5 text-xs rounded {{ $problem->severity == 'critical' ? 'bg-red-900 text-red-100' : ($problem->severity == 'high' ? 'bg-orange-600 text-white' : ($problem->severity == 'medium' ? 'bg-yellow-500 text-gray-900' : 'bg-blue-500 text-white')) }}">
                                                    {{ ucfirst($problem->severity) }}
                                                </span>
                                                <span class="px-2 py-0.5 bg-gray-900/5 dark:bg-white/10 text-gray-700 dark:text-gray-200 text-xs rounded font-mono">{{ $problem->problem_number }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-4">
                                        <p class="text-gray-800 dark:text-gray-200 mb-4">{{ $problem->problem_description }}</p>
                                        
                                        <!-- Causes -->
                                        @if($problem->causes->count() > 0)
                                        <div class="space-y-3">
                                            @foreach($problem->causes as $causeIdx => $cause)
                                            <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3 border border-amber-200 dark:border-amber-800">
                                                <div class="flex items-start space-x-2 mb-2">
                                                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                                    </svg>
                                                    <div class="flex-1">
                                                        <div class="text-xs font-semibold text-amber-900 dark:text-amber-200 mb-1">Root Cause #{{ $causeIdx + 1 }}</div>
                                                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $cause->cause_description }}</p>
                                                    </div>
                                                </div>
                                                
                                                <!-- Action Plans -->
                                                @if($cause->actionPlans->count() > 0)
                                                <div class="mt-3 space-y-2">
                                                    <div class="text-xs font-bold text-gray-600 dark:text-gray-400 flex items-center">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                                        </svg>
                                                        CORRECTIVE ACTIONS
                                                    </div>
                                                    @foreach($cause->actionPlans as $actionIdx => $action)
                                                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                                                        <div class="flex items-start justify-between mb-2">
                                                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Action #{{ $actionIdx + 1 }}</span>
                                                            <span class="px-2 py-0.5 text-xs rounded font-medium {{ $action->status == 'close' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : ($action->status == 'progress' ? 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300') }}">
                                                                {{ $action->status == 'close' ? 'Closed' : ($action->status == 'progress' ? 'In Progress' : 'Open') }}
                                                            </span>
                                                        </div>
                                                        <p class="text-sm text-gray-800 dark:text-gray-200 mb-3">{{ $action->description }}</p>
                                                        <div class="grid grid-cols-2 gap-3">
                                                            <div class="flex items-start space-x-2">
                                                                <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                                </svg>
                                                                <div>
                                                                    <div class="text-xs text-gray-600 dark:text-gray-400">Person in Charge</div>
                                                                    <div class="text-xs font-medium text-gray-900 dark:text-gray-100">{{ $action->person_in_charge ?? 'N/A' }}</div>
                                                                </div>
                                                            </div>
                                                            <div class="flex items-start space-x-2">
                                                                <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                                </svg>
                                                                <div>
                                                                    <div class="text-xs text-gray-600 dark:text-gray-400">Due Date</div>
                                                                    <div class="text-xs font-medium text-gray-900 dark:text-gray-100">{{ $action->due_date ? $action->due_date->format('Y-m-d') : 'N/A' }}</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @if($action->keterangan)
                                                        <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Notes:</div>
                                                            <p class="text-xs text-gray-700 dark:text-gray-300">{{ $action->keterangan }}</p>
                                                        </div>
                                                        @endif
                                                    </div>
                                                    @endforeach
                                                </div>
                                                @endif
                                            </div>
                                            @endforeach
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @else
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-8 text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400 text-lg">No CAPA data available</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 flex items-center justify-between bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 px-6 py-4">
                <a href="{{ route('kpi.entries.index') }}" 
                   class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to List
                </a>
                @can('edit kpi')
                    <a href="{{ route('kpi.entries.edit', $entry) }}" 
                       class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-semibold rounded-lg hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all shadow-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Entry
                    </a>
                @endcan
            </div>
        </div>
    </div>

    <script>
        function toggleArea(index) {
            const content = document.getElementById(`area-content-${index}`);
            const icon = document.getElementById(`area-icon-${index}`);
            
            if (content && icon) {
                content.classList.toggle('collapsed');
                icon.classList.toggle('collapsed');
            }
        }
    </script>
</x-app-layout>
