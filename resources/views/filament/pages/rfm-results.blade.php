@if(empty($stats) || !is_array($stats))
    <div class="fi-section py-12 text-center">
        <div class="fi-section-header mx-auto flex items-center justify-center h-8 w-8 rounded-full bg-primary-100 dark:bg-primary-900/30 mb-3">
            <x-filament::icon
                alias="heroicon.o-chart-bar"
                icon="heroicon-o-chart-bar"
                class="h-4 w-4 text-primary-600 dark:text-primary-400"
            />
        </div>
        <h3 class="fi-section-heading text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">No Analysis Results Yet</h3>
        <p class="fi-section-description text-gray-600 dark:text-gray-400">Segment calculation will start automatically.</p>
    </div>
@else
    @php
        // Prepare data for widgets
        $statsData = $stats;
        $prevStatsData = $previousStats ?? [];

        // Calculate KPIs
        $totalCustomers = collect($stats)->sum('customers');
        $totalRevenue = collect($stats)->sum(fn ($s) => $s['customers'] * $s['avg_monetary']);
        $avgMonetary = $totalCustomers > 0 ? $totalRevenue / $totalCustomers : 0;
        $activeSegments = count($stats);

        $customerTrend = 0;
        $revenueTrend = 0;
        $avgValueTrend = 0;

        if (!empty($previousStats)) {
            $prevTotalCustomers = collect($previousStats)->sum('customers');
            $prevTotalRevenue = collect($previousStats)->sum(fn ($s) => $s['customers'] * $s['avg_monetary']);
            $prevAvgMonetary = $prevTotalCustomers > 0 ? $prevTotalRevenue / $prevTotalCustomers : 0;

            $customerTrend = $prevTotalCustomers > 0 ? (($totalCustomers - $prevTotalCustomers) / $prevTotalCustomers) * 100 : 0;
            $revenueTrend = $prevTotalRevenue > 0 ? (($totalRevenue - $prevTotalRevenue) / $prevTotalRevenue) * 100 : 0;
            $avgValueTrend = $prevAvgMonetary > 0 ? (($avgMonetary - $prevAvgMonetary) / $prevAvgMonetary) * 100 : 0;
        }
    @endphp

    <div class="space-y-6" x-data="{ statsData: @js($statsData) }">
        {{-- Analysis Period Info --}}
        @if($currentAnalysisDate && $previousAnalysisDate)
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <x-filament::icon
                    icon="heroicon-o-information-circle"
                    class="h-5 w-5 text-blue-400 flex-shrink-0 mt-0.5"
                />
                <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100">Analysis Period Comparison</h4>
                    <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                        Comparing <strong>{{ \Carbon\Carbon::parse($previousAnalysisDate)->format('M d, Y') }}</strong>
                        vs <strong>{{ \Carbon\Carbon::parse($currentAnalysisDate)->format('M d, Y') }}</strong>
                    </p>
                    <p class="mt-1 text-xs text-blue-600 dark:text-blue-400">
                        Trends show the change in performance between these two periods.
                    </p>
                </div>
            </div>
        </div>
        @endif

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="fi-section bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">${{ number_format($totalRevenue, 0) }}</p>
                        @if($revenueTrend != 0)
                        <p class="text-xs mt-1 {{ $revenueTrend > 0 ? 'text-success-600' : 'text-danger-600' }}">
                            {{ $revenueTrend > 0 ? '↗' : '↘' }} {{ number_format(abs($revenueTrend), 1) }}%
                        </p>
                        @endif
                    </div>
                    <x-filament::icon icon="heroicon-o-currency-dollar" class="h-8 w-8 text-info-500" />
                </div>
            </div>

            <div class="fi-section bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Customers</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ number_format($totalCustomers) }}</p>
                        @if($customerTrend != 0)
                        <p class="text-xs mt-1 {{ $customerTrend > 0 ? 'text-success-600' : 'text-danger-600' }}">
                            {{ $customerTrend > 0 ? '↗' : '↘' }} {{ number_format(abs($customerTrend), 1) }}%
                        </p>
                        @endif
                    </div>
                    <x-filament::icon icon="heroicon-o-user-group" class="h-8 w-8 text-success-500" />
                </div>
            </div>

            <div class="fi-section bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg Customer Value</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">${{ number_format($avgMonetary, 2) }}</p>
                        @if($avgValueTrend != 0)
                        <p class="text-xs mt-1 {{ $avgValueTrend > 0 ? 'text-success-600' : 'text-danger-600' }}">
                            {{ $avgValueTrend > 0 ? '↗' : '↘' }} {{ number_format(abs($avgValueTrend), 1) }}%
                        </p>
                        @endif
                    </div>
                    <x-filament::icon icon="heroicon-o-chart-bar" class="h-8 w-8 text-warning-500" />
                </div>
            </div>

            <div class="fi-section bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Segments</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $activeSegments }}</p>
                        <p class="text-xs mt-1 text-gray-500 dark:text-gray-400">segments</p>
                    </div>
                    <x-filament::icon icon="heroicon-o-tag" class="h-8 w-8 text-primary-500" />
                </div>
            </div>
        </div>

        {{-- Insights --}}
        @if(!empty($insights))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center gap-2 mb-4">
                <x-filament::icon icon="heroicon-o-light-bulb" class="h-5 w-5 text-warning-500" />
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Actionable Insights</h3>
            </div>
            <div class="space-y-3">
                @foreach($insights as $insight)
                    @php
                        $bgColor = match($insight['type']) {
                            'alert' => 'bg-danger-50 dark:bg-danger-900/20 border-danger-200 dark:border-danger-800',
                            'opportunity' => 'bg-info-50 dark:bg-info-900/20 border-info-200 dark:border-info-800',
                            'action' => 'bg-warning-50 dark:bg-warning-900/20 border-warning-200 dark:border-warning-800',
                            'success' => 'bg-success-50 dark:bg-success-900/20 border-success-200 dark:border-success-800',
                            default => 'bg-gray-50 dark:bg-gray-900/20 border-gray-200 dark:border-gray-800'
                        };
                    @endphp
                    <div class="border rounded-lg p-4 {{ $bgColor }}">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">{{ $insight['icon'] }}</span>
                            <div class="flex-1">
                                <h4 class="font-semibold text-sm text-gray-900 dark:text-gray-100">{{ $insight['title'] }}</h4>
                                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $insight['message'] }}</p>
                                @if(!empty($insight['tooltip']))
                                <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">{{ $insight['tooltip'] }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Filament Chart Widgets --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                @livewire(\App\Filament\Widgets\RfmSegmentDistributionChart::class, ['segmentStats' => $statsData], key('dist-chart-'.md5(json_encode($statsData))))
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                @livewire(\App\Filament\Widgets\RfmRevenueChart::class, ['segmentStats' => $statsData], key('rev-chart-'.md5(json_encode($statsData))))
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
            @livewire(\App\Filament\Widgets\RfmMetricsChart::class, ['segmentStats' => $statsData], key('metrics-chart-'.md5(json_encode($statsData))))
        </div>

        {{-- Segment Table --}}
        @include('filament.widgets.rfm-segment-table-inline', [
            'stats' => $stats,
            'segmentDefinitions' => $segmentDefinitions ?? []
        ])
    </div>
@endif
