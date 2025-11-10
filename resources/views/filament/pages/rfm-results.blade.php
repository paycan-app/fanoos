<div wire:key="rfm-results-{{ md5(json_encode($stats ?? [])) }}">
@if(empty($stats) || !is_array($stats))
    <div class="py-12 text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-primary-100 dark:bg-primary-900/30 mb-4">
            <svg class="h-6 w-6 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">No Analysis Results Yet</h3>
        <p class="text-gray-600 dark:text-gray-400">Click "Calculate Segments" above to analyze your customers.</p>
    </div>
@else
    @php
        $totalCustomers = array_sum(array_column($stats, 'customers'));
        $avgMonetary = count($stats) > 0 ? array_sum(array_column($stats, 'avg_monetary')) / count($stats) : 0;
        $totalRevenue = array_sum(array_map(fn($s) => $s['customers'] * $s['avg_monetary'], $stats));
    @endphp

    <div class="space-y-6">
        {{-- Summary Stats Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Segments</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ count($stats) }}</p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
                        <svg class="h-5 w-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Customers</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ number_format($totalCustomers) }}</p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-success-100 dark:bg-success-900/30 flex items-center justify-center flex-shrink-0">
                        <svg class="h-5 w-5 text-success-600 dark:text-success-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg Customer Value</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">${{ number_format($avgMonetary, 2) }}</p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-warning-100 dark:bg-warning-900/30 flex items-center justify-center flex-shrink-0">
                        <svg class="h-5 w-5 text-warning-600 dark:text-warning-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">${{ number_format($totalRevenue, 0) }}</p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-info-100 dark:bg-info-900/30 flex items-center justify-center flex-shrink-0">
                        <svg class="h-5 w-5 text-info-600 dark:text-info-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Treemap Chart --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Customer Segments Treemap</h3>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Size represents customer count per segment</p>
                </div>
                <div class="p-4">
                    <div id="treemapChart" style="height: 400px; width: 100%;"></div>
                </div>
            </div>

            {{-- Bar Chart --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Customers by Segment</h3>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Sorted by customer count</p>
                </div>
                <div class="p-4">
                    <div id="barChart" style="height: 400px; width: 100%;"></div>
                </div>
            </div>
        </div>

        {{-- Revenue Chart --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Revenue Contribution by Segment</h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Total revenue generated per segment</p>
            </div>
            <div class="p-4">
                <div id="revenueChart" style="height: 350px; width: 100%;"></div>
            </div>
        </div>

        {{-- RFM Metrics Comparison --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">RFM Metrics Comparison</h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Average Recency, Frequency, and Monetary values</p>
            </div>
            <div class="p-4">
                <div id="metricsChart" style="height: 400px; width: 100%;"></div>
            </div>
        </div>

        {{-- Detailed Stats Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Detailed Segment Statistics</h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Complete breakdown of all metrics</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th scope="col" class="px-6 py-3 font-medium text-gray-700 dark:text-gray-300">Segment</th>
                            <th scope="col" class="px-6 py-3 font-medium text-gray-700 dark:text-gray-300 text-right">Customers</th>
                            <th scope="col" class="px-6 py-3 font-medium text-gray-700 dark:text-gray-300 text-right">% Share</th>
                            <th scope="col" class="px-6 py-3 font-medium text-gray-700 dark:text-gray-300 text-right">Avg Monetary</th>
                            <th scope="col" class="px-6 py-3 font-medium text-gray-700 dark:text-gray-300 text-right">Avg Frequency</th>
                            <th scope="col" class="px-6 py-3 font-medium text-gray-700 dark:text-gray-300 text-right">Avg Recency</th>
                            <th scope="col" class="px-6 py-3 font-medium text-gray-700 dark:text-gray-300 text-right">Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($stats as $row)
                        @php
                            $percentage = $totalCustomers > 0 ? ($row['customers'] / $totalCustomers) * 100 : 0;
                            $segmentRevenue = $row['customers'] * $row['avg_monetary'];
                            $badgeColor = match(true) {
                                str_contains($row['segment'], 'Champion') => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                str_contains($row['segment'], 'Loyal') => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                str_contains($row['segment'], 'Potential') || str_contains($row['segment'], 'Promising') => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                                str_contains($row['segment'], 'At Risk') || str_contains($row['segment'], 'Sleep') => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                                str_contains($row['segment'], 'Lost') || str_contains($row['segment'], 'Hibernating') => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                str_contains($row['segment'], 'New') => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeColor }}">
                                    {{ $row['segment'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-gray-100 text-right">
                                {{ number_format($row['customers']) }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400 text-right">
                                {{ number_format($percentage, 1) }}%
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-100 text-right">
                                ${{ number_format($row['avg_monetary'], 2) }}
                            </td>
                            <td class="px-6 py-4 text-gray-700 dark:text-gray-300 text-right">
                                {{ number_format($row['avg_frequency'], 1) }}
                            </td>
                            <td class="px-6 py-4 text-gray-700 dark:text-gray-300 text-right">
                                {{ number_format($row['avg_recency'], 0) }} days
                            </td>
                            <td class="px-6 py-4 font-semibold text-green-700 dark:text-green-400 text-right">
                                ${{ number_format($segmentRevenue, 2) }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-900/50 font-semibold">
                        <tr>
                            <td class="px-6 py-4 text-gray-900 dark:text-gray-100">Total</td>
                            <td class="px-6 py-4 text-gray-900 dark:text-gray-100 text-right">{{ number_format($totalCustomers) }}</td>
                            <td class="px-6 py-4 text-gray-900 dark:text-gray-100 text-right">100.0%</td>
                            <td class="px-6 py-4 text-gray-900 dark:text-gray-100 text-right">${{ number_format($avgMonetary, 2) }}</td>
                            <td class="px-6 py-4"></td>
                            <td class="px-6 py-4"></td>
                            <td class="px-6 py-4 text-green-700 dark:text-green-400 text-right">${{ number_format($totalRevenue, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Plotly Charts Script - Wire with Livewire lifecycle --}}
    <script src="https://cdn.plot.ly/plotly-2.27.0.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize charts immediately and after Livewire updates
            initRfmCharts();

            // Re-initialize after Livewire updates the component
            Livewire.hook('morph.updated', () => {
                setTimeout(initRfmCharts, 100);
            });
        });

        function initRfmCharts() {
            const stats = @json($stats);

            // Validate stats data
            if (!stats || !Array.isArray(stats) || stats.length === 0) {
                console.log('No stats data available for charts');
                return;
            }

            const isDark = document.documentElement.classList.contains('dark');

            const colors = {
                'Champions': '#eab308', 'Loyal Customers': '#22c55e',
                'Potential Loyalist': '#3b82f6', 'New Customers': '#a855f7',
                'Promising': '#06b6d4', 'Customers Needing Attention': '#f97316',
                'Need Attention': '#f97316', 'About To Sleep': '#f59e0b',
                'At Risk': '#fb923c', 'Cannot Lose Them': '#dc2626',
                'Hibernating': '#991b1b', 'Lost': '#7f1d1d',
                'High Value': '#22c55e', 'Medium Value': '#3b82f6', 'Low Value': '#f59e0b'
            };

            const getColor = (segment) => colors[segment] || '#6b7280';

            const config = {
                responsive: true,
                displayModeBar: true,
                displaylogo: false,
                modeBarButtonsToRemove: ['lasso2d', 'select2d']
            };

            const layout = {
                paper_bgcolor: isDark ? '#1f2937' : '#ffffff',
                plot_bgcolor: isDark ? '#1f2937' : '#ffffff',
                font: { color: isDark ? '#e5e7eb' : '#1f2937', family: 'system-ui', size: 11 },
                margin: { t: 20, r: 20, b: 60, l: 60 }
            };

            // 1. Treemap
            const treemapEl = document.getElementById('treemapChart');
            if (treemapEl) {
                Plotly.newPlot('treemapChart', [{
                    type: 'treemap',
                    labels: stats.map(s => s.segment),
                    parents: stats.map(() => ''),
                    values: stats.map(s => s.customers),
                    text: stats.map(s => s.customers.toLocaleString() + ' customers'),
                    textposition: 'middle center',
                    marker: {
                        colors: stats.map(s => getColor(s.segment)),
                        line: { width: 2, color: isDark ? '#1f2937' : '#ffffff' }
                    },
                    hovertemplate: '<b>%{label}</b><br>%{value:,} customers<extra></extra>'
                }], { ...layout, margin: { t: 10, r: 10, b: 10, l: 10 } }, config);
            }

            // 2. Bar Chart
            const barEl = document.getElementById('barChart');
            if (barEl) {
                const sorted = [...stats].sort((a, b) => b.customers - a.customers);
                Plotly.newPlot('barChart', [{
                    x: sorted.map(s => s.segment),
                    y: sorted.map(s => s.customers),
                    type: 'bar',
                    marker: { color: sorted.map(s => getColor(s.segment)), opacity: 0.9 },
                    text: sorted.map(s => s.customers.toLocaleString()),
                    textposition: 'outside',
                    hovertemplate: '<b>%{x}</b><br>%{y:,} customers<extra></extra>'
                }], {
                    ...layout,
                    xaxis: { gridcolor: isDark ? '#374151' : '#e5e7eb', tickangle: -45 },
                    yaxis: { title: 'Customers', gridcolor: isDark ? '#374151' : '#e5e7eb' }
                }, config);
            }

            // 3. Revenue Chart
            const revenueEl = document.getElementById('revenueChart');
            if (revenueEl) {
                const revenueSorted = [...stats]
                    .map(s => ({ segment: s.segment, revenue: s.customers * s.avg_monetary }))
                    .sort((a, b) => b.revenue - a.revenue);

                Plotly.newPlot('revenueChart', [{
                    x: revenueSorted.map(s => s.segment),
                    y: revenueSorted.map(s => s.revenue),
                    type: 'bar',
                    marker: { color: revenueSorted.map(s => getColor(s.segment)), opacity: 0.9 },
                    text: revenueSorted.map(s => '$' + Math.round(s.revenue).toLocaleString()),
                    textposition: 'outside',
                    hovertemplate: '<b>%{x}</b><br>$%{y:,.2f}<extra></extra>'
                }], {
                    ...layout,
                    xaxis: { gridcolor: isDark ? '#374151' : '#e5e7eb', tickangle: -45 },
                    yaxis: { title: 'Revenue ($)', gridcolor: isDark ? '#374151' : '#e5e7eb' }
                }, config);
            }

            // 4. RFM Metrics
            const metricsEl = document.getElementById('metricsChart');
            if (metricsEl) {
                Plotly.newPlot('metricsChart', [
                    {
                        x: stats.map(s => s.segment),
                        y: stats.map(s => s.avg_monetary),
                        name: 'Monetary ($)',
                        type: 'bar',
                        marker: { color: '#22c55e' },
                        yaxis: 'y'
                    },
                    {
                        x: stats.map(s => s.segment),
                        y: stats.map(s => s.avg_frequency),
                        name: 'Frequency',
                        type: 'bar',
                        marker: { color: '#3b82f6' },
                        yaxis: 'y2'
                    },
                    {
                        x: stats.map(s => s.segment),
                        y: stats.map(s => s.avg_recency),
                        name: 'Recency (days)',
                        type: 'bar',
                        marker: { color: '#f59e0b' },
                        yaxis: 'y3'
                    }
                ], {
                    ...layout,
                    barmode: 'group',
                    xaxis: { gridcolor: isDark ? '#374151' : '#e5e7eb', tickangle: -45 },
                    yaxis: { title: 'Monetary', titlefont: { color: '#22c55e' }, tickfont: { color: '#22c55e' }, gridcolor: isDark ? '#374151' : '#e5e7eb' },
                    yaxis2: { title: 'Frequency', titlefont: { color: '#3b82f6' }, tickfont: { color: '#3b82f6' }, overlaying: 'y', side: 'right', showgrid: false },
                    yaxis3: { title: 'Recency', titlefont: { color: '#f59e0b' }, tickfont: { color: '#f59e0b' }, anchor: 'free', overlaying: 'y', side: 'right', position: 0.94, showgrid: false },
                    legend: { orientation: 'h', y: -0.2, x: 0.5, xanchor: 'center' }
                }, config);
            }
        }
    </script>
@endif
</div>
