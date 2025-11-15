<x-filament-panels::page>

    @if(empty($this->segmentStats) || isset($this->segmentStats['message']))
        <div class="rounded-lg bg-warning-50 dark:bg-warning-500/10 p-6 border border-warning-200 dark:border-warning-500/20">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 flex-shrink-0 text-warning-600 dark:text-warning-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: block;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-warning-800 dark:text-warning-400 mb-1">
                        No RFM Data Available
                    </h3>
                    <p class="text-sm text-warning-700 dark:text-warning-300">
                        {{ $this->segmentStats['message'] ?? 'Please run the RFM calculation from the Setup Wizard first.' }}
                    </p>
                </div>
            </div>
        </div>
    @else

    <div class="space-y-6">

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Total Customers --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-500/10">
                        <svg class="h-6 w-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: block;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Customers</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white truncate">
                            {{ number_format(collect($this->segmentStats)->sum('customers')) }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Total Revenue --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-success-50 dark:bg-success-500/10">
                        <svg class="h-6 w-6 text-success-600 dark:text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: block;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white truncate">
                            ${{ number_format(collect($this->segmentStats)->sum(fn($s) => $s['customers'] * $s['avg_monetary']), 0) }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Active Segments --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-warning-50 dark:bg-warning-500/10">
                        <svg class="h-6 w-6 text-warning-600 dark:text-warning-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: block;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Segments</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ count($this->segmentStats) }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Analysis Date --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-info-50 dark:bg-info-500/10">
                        <svg class="h-6 w-6 text-info-600 dark:text-info-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: block;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Analysis Date</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $this->currentAnalysisDate ?? 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Insights Section --}}
        @if(!empty($this->insights))
        <div class="fi-section rounded-xl bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 shadow-sm ring-1 ring-primary-200 dark:ring-primary-700 p-6">
            <div class="flex items-start gap-3 mb-4">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-primary-100 dark:bg-primary-500/20">
                    <svg class="h-5 w-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: block;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-primary-900 dark:text-primary-100">
                        Key Insights
                    </h3>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($this->insights as $insight)
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl flex-shrink-0">{{ $insight['icon'] ?? '📊' }}</span>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-semibold text-sm text-gray-900 dark:text-white mb-1">
                                {{ $insight['title'] }}
                            </h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                                {{ $insight['message'] }}
                            </p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Charts Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @livewire('app.filament.widgets.rfm-segment-distribution-chart', ['segmentStats' => $this->segmentStats])
            @livewire('app.filament.widgets.rfm-revenue-chart', ['segmentStats' => $this->segmentStats])
        </div>

        {{-- Treemap Chart --}}
        @livewire('app.filament.widgets.rfm-treemap-chart', ['segmentStats' => $this->segmentStats, 'segmentDefinitions' => $this->segmentDefinitions])

        {{-- Metrics Comparison Chart --}}
        @livewire('app.filament.widgets.rfm-metrics-chart', ['segmentStats' => $this->segmentStats])

        {{-- Segment Details Table --}}
        @livewire('app.filament.widgets.rfm-segment-details-table', ['segmentStats' => $this->segmentStats, 'segmentDefinitions' => $this->segmentDefinitions])

    </div>

    @endif

</x-filament-panels::page>
