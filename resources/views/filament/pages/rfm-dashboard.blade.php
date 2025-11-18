<x-filament-panels::page>
    @php
        $summary = $this->summary ?? [];
        $symbol = $this->currencySymbol ?? '$';
    @endphp

    @if(($summary['has_data'] ?? false) === false)
        <div class="rounded-2xl bg-warning-50 dark:bg-warning-500/10 p-6 border border-warning-200 dark:border-warning-500/20">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 flex-shrink-0 text-warning-600 dark:text-warning-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: block;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-warning-800 dark:text-warning-400 mb-1">
                        RFM data is not ready
                    </h3>
                    <p class="text-sm text-warning-700 dark:text-warning-300">
                        {{ $this->statusMessage ?? 'Please run the RFM calculation from the Setup Wizard first.' }}
                    </p>
                </div>
            </div>
        </div>
    @else
        <div class="space-y-10">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-filament::card>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Revenue ({{ $this->timeframeLabel }})</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ $summary['total_revenue']['formatted'] ?? ($symbol . '0') }}</p>
                    <p class="text-xs text-gray-400 mt-1">vs {{ $this->previousAnalysisDate }}</p>
                </x-filament::card>

                <x-filament::card>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Active Customers</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ number_format($summary['total_customers'] ?? 0) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Across {{ $summary['active_segments'] ?? 0 }} segments</p>
                </x-filament::card>

                <x-filament::card>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Average Customer Value</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ $summary['average_value']['formatted'] ?? ($symbol . '0') }}</p>
                    <p class="text-xs text-gray-400 mt-1">High-value share {{ $summary['high_value_share'] ?? 0 }}%</p>
                </x-filament::card>

                <x-filament::card>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Analysis Window</p>
                    <p class="mt-2 text-xl font-semibold text-gray-900 dark:text-white">{{ $this->timeframeLabel }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $this->previousAnalysisDate }} → {{ $this->currentAnalysisDate }}</p>
                </x-filament::card>
            </div>

            @if(!empty($this->segmentStats))
                <x-filament::section>
                    <x-slot name="heading">Segment Revenue Distribution</x-slot>
                    <x-slot name="description">Visual breakdown of revenue by customer segment</x-slot>

                    <div class="mt-6">
                        <div id="segmentTreemap" style="width: 100%; height: 500px;"></div>
                    </div>
                    <div id="segmentTreemapData" data-segments="{{ json_encode($this->segmentStats) }}" data-currency="{{ $symbol }}" style="display: none;"></div>
                </x-filament::section>
            @endif

            @if(!empty($this->insights))
                <x-filament::section>
                    <x-slot name="heading">Opportunity radar</x-slot>
                    <x-slot name="description">
                        {{ count($this->insights) }} insights from the latest analysis window.
                    </x-slot>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        @foreach($this->insights as $insight)
                            <article class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900/80 p-4 shadow-sm">
                                <div class="flex items-start gap-3">
                                    <span class="text-2xl">{{ $insight['icon'] ?? '📊' }}</span>
                                    <div class="space-y-1 flex-1">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $insight['title'] }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $insight['message'] }}
                                        </p>
                                    </div>
                                    <span class="text-xs uppercase tracking-wide px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                                        {{ $insight['type'] ?? 'info' }}
                                    </span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </x-filament::section>
            @endif

            <div class="grid gap-6 xl:grid-cols-12">
                <div class="xl:col-span-8 space-y-6">
                    <x-filament::section>
                        <x-slot name="heading">Segment momentum</x-slot>
                        <x-slot name="description">Customer movement compared to {{ $this->previousAnalysisDate }}</x-slot>

                        <div class="mt-6 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                                <thead>
                                    <tr>
                                        <th class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 pb-3">Segment</th>
                                        <th class="text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 pb-3">Customers</th>
                                        <th class="text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 pb-3">Avg spend</th>
                                        <th class="text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 pb-3">Movement</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @forelse($this->segmentMomentum as $row)
                                        <tr>
                                            <td class="py-3 pr-3">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $row['segment'] }}</span>
                                            </td>
                                            <td class="py-3 text-right text-sm text-gray-700 dark:text-gray-300">
                                                {{ number_format($row['customers']) }}
                                            </td>
                                            <td class="py-3 text-right text-sm text-gray-700 dark:text-gray-300">
                                                {{ $symbol }}{{ number_format($row['avg_monetary'], 0) }}
                                            </td>
                                            <td class="py-3 text-right text-sm font-medium">
                                                @if($row['delta_customers'] === 0)
                                                    <span class="text-gray-500 dark:text-gray-400">–</span>
                                                @else
                                                    <span class="{{ $row['delta_customers'] > 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                                                        {{ $row['delta_customers'] > 0 ? '▲' : '▼' }}
                                                        {{ abs($row['delta_customers']) }}
                                                        @if(! is_null($row['delta_percent']))
                                                            ({{ $row['delta_percent'] > 0 ? '+' : '-' }}{{ abs($row['delta_percent']) }}%)
                                                        @endif
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                                No historical comparison available.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </x-filament::section>
                </div>
                <div class="xl:col-span-4 space-y-6">
                    <x-filament::section>
                        <x-slot name="heading">Top revenue builders</x-slot>
                        <x-slot name="description">Segments contributing the most revenue</x-slot>

                        <div class="mt-0 space-y-4">
                            @foreach($this->topSegments as $segment)
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $segment['segment'] }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ number_format($segment['customers']) }} customers
                                        </p>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $symbol }}{{ number_format($segment['customers'] * $segment['avg_monetary'], 0) }}
                                    </p>
                                </div>
                            @endforeach
                            @if(empty($this->topSegments))
                                <p class="text-sm text-gray-500 dark:text-gray-400">Once data is available you'll see the leading segments here.</p>
                            @endif
                        </div>
                    </x-filament::section>

                    <x-filament::section>
                        <x-slot name="heading">Win-back priority list</x-slot>
                        <x-slot name="description">High-value groups drifting away</x-slot>

                        <div class="mt-0 space-y-2">
                            @foreach($this->winBackTargets as $target)
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $target['segment'] }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ number_format($target['customers']) }} customers · {{ $symbol }}{{ number_format($target['avg_monetary'], 0) }} avg
                                        </p>
                                    </div>
                                    <p class="text-sm font-semibold text-danger-600 dark:text-danger-400">
                                        {{ $symbol }}{{ number_format($target['potential_revenue'], 0) }}
                                    </p>
                                </div>
                            @endforeach
                            @if(empty($this->winBackTargets))
                                <p class="text-sm text-gray-500 dark:text-gray-400">No at-risk segments detected.</p>
                            @endif
                        </div>
                    </x-filament::section>
                </div>
            </div>

            <div class="space-y-10 mt-12">
                <div class="grid gap-6 lg:grid-cols-2">
                    @livewire('app.filament.widgets.rfm-revenue-chart', [
                        'segmentStats' => $this->segmentStats,
                        'currencySymbol' => $this->currencySymbol,
                        'currencyCode' => $this->currencyCode,
                    ], key('rfm-revenue-chart'))

                    <x-filament::section>
                        <x-slot name="heading">Metric cheat sheet</x-slot>
                        <x-slot name="description">Quick definitions for every KPI in this dashboard</x-slot>

                        <div class="mt-6 grid gap-3">
                            @foreach($this->metricDefinitions as $metric)
                                <div class="rounded-xl bg-gray-50 dark:bg-gray-800/60 px-4 py-3">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $metric['name'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $metric['description'] }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $metric['business_meaning'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </x-filament::section>
                </div>

                @livewire('app.filament.widgets.rfm-metrics-chart', [
                    'segmentStats' => $this->segmentStats,
                    'currencySymbol' => $this->currencySymbol,
                ], key('rfm-metrics-chart'))
            </div>

            <div class="mt-12">
                @livewire('app.filament.widgets.rfm-segment-details-table', [
                    'segmentStats' => $this->segmentStats,
                    'segmentDefinitions' => $this->segmentDefinitions,
                    'currencySymbol' => $this->currencySymbol,
                ], key('rfm-segment-details-table'))
            </div>
        </div>
    @endif

    @if(!empty($this->segmentStats))
        <script src="https://cdn.plot.ly/plotly-2.27.0.min.js" charset="utf-8"></script>
        <script>
            (function () {
                const renderTreemap = () => {
                    if (typeof Plotly === 'undefined') {
                        return;
                    }

                    const dataElement = document.getElementById('segmentTreemapData');
                    const treemapElement = document.getElementById('segmentTreemap');

                    if (!dataElement || !treemapElement) {
                        return;
                    }

                    const segments = JSON.parse(dataElement.dataset.segments || '[]');
                    const currency = dataElement.dataset.currency || '$';

                    if (!segments || segments.length === 0) {
                        return;
                    }

                    const isDark = document.documentElement.classList.contains('dark');

                    // Color palette - distinct colors for each segment
                    const colorPalette = isDark ? [
                        '#6366f1', // Indigo
                        '#8b5cf6', // Purple
                        '#ec4899', // Pink
                        '#f59e0b', // Amber
                        '#10b981', // Emerald
                        '#3b82f6', // Blue
                        '#f97316', // Orange
                        '#06b6d4', // Cyan
                        '#84cc16', // Lime
                        '#ef4444', // Red
                        '#14b8a6', // Teal
                        '#a855f7', // Violet
                    ] : [
                        '#4f46e5', // Indigo
                        '#7c3aed', // Purple
                        '#db2777', // Pink
                        '#d97706', // Amber
                        '#059669', // Emerald
                        '#2563eb', // Blue
                        '#ea580c', // Orange
                        '#0891b2', // Cyan
                        '#65a30d', // Lime
                        '#dc2626', // Red
                        '#0d9488', // Teal
                        '#9333ea', // Violet
                    ];

                    // Build treemap data structure
                    const labels = ['All Segments'];
                    const parents = [''];
                    const values = [];
                    const customerCounts = [];
                    const avgMonetaryValues = [];
                    const colors = [isDark ? '#374151' : '#e5e7eb']; // Root color (gray)

                    segments.forEach((segment, index) => {
                        const revenue = segment.customers * segment.avg_monetary;
                        labels.push(segment.segment);
                        parents.push('All Segments');
                        values.push(revenue);
                        customerCounts.push(segment.customers);
                        avgMonetaryValues.push(segment.avg_monetary);
                        // Assign color from palette, cycling if needed
                        colors.push(colorPalette[index % colorPalette.length]);
                    });

                    // Calculate total revenue and customers for root
                    const totalCustomers = segments.reduce((sum, s) => sum + s.customers, 0);
                    const totalRevenue = values.reduce((sum, val) => sum + val, 0);
                    const avgMonetaryRoot = totalCustomers > 0 ? totalRevenue / totalCustomers : 0;
                    customerCounts.unshift(totalCustomers);
                    avgMonetaryValues.unshift(avgMonetaryRoot);
                    values.unshift(totalRevenue);

                    const data = [{
                        type: 'treemap',
                        labels: labels,
                        parents: parents,
                        values: values,
                        customdata: customerCounts.map((count, i) => [count, avgMonetaryValues[i]]),
                        textinfo: 'label+percent parent',
                        texttemplate: '<b>%{label}</b><br>%{percentParent:.1%}',
                        hovertemplate: '<b>%{label}</b><br>' +
                            `Revenue: ${currency}%{value:,.0f}<br>` +
                            'Share: %{percentParent:.1%}<br>' +
                            'Customers: %{customdata[0]:,}<br>' +
                            `Avg Value: ${currency}%{customdata[1]:,.0f}<extra></extra>`,
                        marker: {
                            colors: colors,
                            line: {
                                width: 2,
                                color: isDark ? '#404040' : '#e5e5e5',
                            },
                        },
                        pathbar: {
                            visible: true,
                            edgeshape: '>',
                        },
                    }];

                    const layout = {
                        paper_bgcolor: isDark ? '#1f1f1f' : '#ffffff',
                        plot_bgcolor: isDark ? '#1f1f1f' : '#ffffff',
                        font: {
                            color: isDark ? '#f5f5f5' : '#171717',
                            family: 'Inter, system-ui, sans-serif',
                            size: 12,
                        },
                        margin: { t: 20, r: 20, b: 20, l: 20 },
                        height: 500,
                    };

                    Plotly.react(treemapElement, data, layout, {
                        responsive: true,
                        displayModeBar: true,
                        displaylogo: false,
                        modeBarButtonsToRemove: ['pan2d', 'lasso2d'],
                    });
                };

                // Render on page load
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', renderTreemap);
                } else {
                    renderTreemap();
                }

                // Re-render on dark mode toggle (if needed)
                const observer = new MutationObserver(() => {
                    const treemapElement = document.getElementById('segmentTreemap');
                    if (treemapElement && treemapElement.data) {
                        renderTreemap();
                    }
                });

                observer.observe(document.documentElement, {
                    attributes: true,
                    attributeFilter: ['class'],
                });
            })();
        </script>
    @endif
</x-filament-panels::page>
