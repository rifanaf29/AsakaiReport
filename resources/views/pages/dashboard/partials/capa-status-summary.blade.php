@php
    $counts = $capaStatusCounts ?? ['open' => 0, 'progress' => 0, 'close' => 0];
    $openCount = (int) ($counts['open'] ?? 0);
    $progressCount = (int) ($counts['progress'] ?? 0);
    $closeCount = (int) ($counts['close'] ?? 0);
    $totalCount = $openCount + $progressCount + $closeCount;
    $selectedCapaStatus = $selectedCapaStatus ?? null;

    $statusButtonClass = function (?string $status) use ($selectedCapaStatus) {
        $isActive = $selectedCapaStatus === $status || ($status === null && $selectedCapaStatus === null);
        $base = 'capa-status-filter inline-flex items-center gap-1.5 rounded-full transition focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500/60 cursor-pointer';
        $active = ' ring-2 ring-violet-500 ring-offset-1 ring-offset-white dark:ring-offset-gray-800';

        return $base . ($isActive ? $active : ' hover:opacity-90');
    };
@endphp
<div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-lg sm:text-xl font-semibold">
    <button type="button" class="{{ $statusButtonClass(null) }}" data-capa-status="" aria-pressed="{{ $selectedCapaStatus === null ? 'true' : 'false' }}">
        <span class="text-gray-600 dark:text-gray-300">Total</span>
        <span class="px-3 py-1 rounded-full text-lg sm:text-xl bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">{{ $totalCount }}</span>
    </button>
    <span class="hidden sm:inline text-gray-300 dark:text-gray-600 text-xl" aria-hidden="true">|</span>
    <button type="button" class="{{ $statusButtonClass('open') }}" data-capa-status="open" aria-pressed="{{ $selectedCapaStatus === 'open' ? 'true' : 'false' }}">
        <span class="text-gray-600 dark:text-gray-300">Open</span>
        <span class="px-3 py-1 rounded-full text-lg sm:text-xl bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">{{ $openCount }}</span>
    </button>
    <button type="button" class="{{ $statusButtonClass('progress') }}" data-capa-status="progress" aria-pressed="{{ $selectedCapaStatus === 'progress' ? 'true' : 'false' }}">
        <span class="text-gray-600 dark:text-gray-300">In Progress</span>
        <span class="px-3 py-1 rounded-full text-lg sm:text-xl bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">{{ $progressCount }}</span>
    </button>
    <button type="button" class="{{ $statusButtonClass('close') }}" data-capa-status="close" aria-pressed="{{ $selectedCapaStatus === 'close' ? 'true' : 'false' }}">
        <span class="text-gray-600 dark:text-gray-300">Closed</span>
        <span class="px-3 py-1 rounded-full text-lg sm:text-xl bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">{{ $closeCount }}</span>
    </button>
</div>
