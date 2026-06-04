{{-- Override wrapper: include this from dashboard blades when writable, or loaded via view finder prepend --}}
<div id="kpi-chart-table-sync" class="mt-4 overflow-x-auto">
    <div id="kpi-chart-table-sync-inner">
        <div class="{{ $chartHeightClass ?? 'h-[320px]' }}">
            <canvas id="kpi-actual-target-chart" height="{{ $chartHeight ?? 320 }}"></canvas>
        </div>
        <div id="presentation-kpi-table">
            {{ $kpiTableSlot ?? '' }}
        </div>
    </div>
</div>
@include('pages.dashboard.partials.kpi-chart-table-sync-script')
