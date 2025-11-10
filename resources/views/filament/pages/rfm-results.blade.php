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
        $avgMonetary = $totalCustomers > 0 ? array_sum(array_map(fn($s) => $s['customers'] * $s['avg_monetary'], $stats)) / $totalCustomers : 0;
        $totalRevenue = array_sum(array_map(fn($s) => $s['customers'] * $s['avg_monetary'], $stats));
        $activeSegments = count($stats);

        // Previous period comparison
        $prevTotalCustomers = !empty($previousStats) ? array_sum(array_column($previousStats, 'customers')) : 0;
        $prevTotalRevenue = !empty($previousStats) ? array_sum(array_map(fn($s) => $s['customers'] * $s['avg_monetary'], $previousStats)) : 0;
        $prevAvgMonetary = $prevTotalCustomers > 0 ? $prevTotalRevenue / $prevTotalCustomers : 0;

        // Calculate trends
        $customerTrend = $prevTotalCustomers > 0 ? (($totalCustomers - $prevTotalCustomers) / $prevTotalCustomers) * 100 : 0;
        $revenueTrend = $prevTotalRevenue > 0 ? (($totalRevenue - $prevTotalRevenue) / $prevTotalRevenue) * 100 : 0;
        $avgValueTrend = $prevAvgMonetary > 0 ? (($avgMonetary - $prevAvgMonetary) / $prevAvgMonetary) * 100 : 0;
    @endphp

    {{-- Load Plotly Once --}}
    @once
    <script src="https://cdn.plot.ly/plotly-2.27.0.min.js" charset="utf-8"></script>
    @endonce

    <div class="space-y-6" x-data="rfmDashboard()" x-init="init()">
        {{-- Analysis Period Info --}}
        @if($currentAnalysisDate && $previousAnalysisDate)
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Analysis Comparison</h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                        <p><strong>Current Period:</strong> {{ $currentAnalysisDate }} ({{ $totalCustomers }} customers)</p>
                        <p><strong>Previous Period:</strong> {{ $previousAnalysisDate }} ({{ $prevTotalCustomers }} customers)</p>
                        <p class="text-xs mt-1 text-blue-600 dark:text-blue-400">Insights compared between these two periods</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Summary Stats Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Total Revenue Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">${{ number_format($totalRevenue, 0) }}</p>
                        @if($revenueTrend != 0)
                        <p class="text-xs mt-1 {{ $revenueTrend > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $revenueTrend > 0 ? '↗' : '↘' }} {{ number_format(abs($revenueTrend), 1) }}%
                        </p>
                        @endif
                    </div>
                    <div class="h-10 w-10 rounded-full bg-info-100 dark:bg-info-900/30 flex items-center justify-center">
                        <svg class="h-5 w-5 text-info-600 dark:text-info-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Total Customers Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Customers</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ number_format($totalCustomers) }}</p>
                        @if($customerTrend != 0)
                        <p class="text-xs mt-1 {{ $customerTrend > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $customerTrend > 0 ? '↗' : '↘' }} {{ number_format(abs($customerTrend), 1) }}%
                        </p>
                        @endif
                    </div>
                    <div class="h-10 w-10 rounded-full bg-success-100 dark:bg-success-900/30 flex items-center justify-center">
                        <svg class="h-5 w-5 text-success-600 dark:text-success-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Avg Customer Value Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg Customer Value</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">${{ number_format($avgMonetary, 2) }}</p>
                        @if($avgValueTrend != 0)
                        <p class="text-xs mt-1 {{ $avgValueTrend > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $avgValueTrend > 0 ? '↗' : '↘' }} {{ number_format(abs($avgValueTrend), 1) }}%
                        </p>
                        @endif
                    </div>
                    <div class="h-10 w-10 rounded-full bg-warning-100 dark:bg-warning-900/30 flex items-center justify-center">
                        <svg class="h-5 w-5 text-warning-600 dark:text-warning-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Active Segments Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Segments</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $activeSegments }}</p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                        <svg class="h-5 w-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actionable Insights --}}
        @if(!empty($insights))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Actionable Insights</h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Smart recommendations based on segment changes</p>
            </div>
            <div class="p-4 space-y-3">
                @foreach($insights as $insight)
                    @php
                        $bgColor = match($insight['type']) {
                            'alert' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
                            'success' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
                            'opportunity' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
                            'action' => 'bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-800',
                            default => 'bg-gray-50 dark:bg-gray-900/20 border-gray-200 dark:border-gray-800'
                        };
                        $textColor = match($insight['type']) {
                            'alert' => 'text-red-800 dark:text-red-200',
                            'success' => 'text-green-800 dark:text-green-200',
                            'opportunity' => 'text-blue-800 dark:text-blue-200',
                            'action' => 'text-purple-800 dark:text-purple-200',
                            default => 'text-gray-800 dark:text-gray-200'
                        };
                    @endphp
                    <div class="border rounded-lg p-3 {{ $bgColor }}">
                        <div class="flex items-start gap-3">
                            <span class="text-xl">{{ $insight['icon'] }}</span>
                            <div class="flex-1">
                                <h4 class="font-semibold {{ $textColor }} text-sm">{{ $insight['title'] }}</h4>
                                <p class="text-sm {{ $textColor }} opacity-90 mt-0.5">{{ $insight['message'] }}</p>
                                <p class="text-xs {{ $textColor }} opacity-75 mt-1">{{ $insight['tooltip'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Charts Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Treemap --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Customer Segments</h3>
                </div>
                <div class="p-4">
                    <div id="treemapChart" style="width: 100%; height: 400px;"></div>
                </div>
            </div>

            {{-- Bar Chart --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Customers by Segment</h3>
                </div>
                <div class="p-4">
                    <div id="barChart" style="width: 100%; height: 400px;"></div>
                </div>
            </div>
        </div>

        {{-- Revenue Chart --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Revenue by Segment</h3>
            </div>
            <div class="p-4">
                <div id="revenueChart" style="width: 100%; height: 350px;"></div>
            </div>
        </div>

        {{-- RFM Metrics --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">RFM Metrics Comparison</h3>
            </div>
            <div class="p-4">
                <div id="metricsChart" style="width: 100%; height: 400px;"></div>
            </div>
        </div>

        {{-- Detailed Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Detailed Segment Statistics</h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Click segment names to view customers</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Segment</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Customers</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-700 dark:text-gray-300">% Share</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Avg Monetary</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Avg Frequency</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Avg Recency</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Total Revenue</th>
                            <th class="px-6 py-3 text-center font-medium text-gray-700 dark:text-gray-300">Actions</th>
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
                                str_contains($row['segment'], 'High') => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                str_contains($row['segment'], 'Medium') => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                                str_contains($row['segment'], 'Low') => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                            };
                            $customerUrl = '/admin/customers?tableFilters[segment][value]=' . urlencode($row['segment']);
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4">
                                <a href="{{ $customerUrl }}" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeColor }} hover:opacity-75">
                                    {{ $row['segment'] }}
                                </a>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-gray-100 text-right">{{ number_format($row['customers']) }}</td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400 text-right">{{ number_format($percentage, 1) }}%</td>
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-100 text-right">${{ number_format($row['avg_monetary'], 2) }}</td>
                            <td class="px-6 py-4 text-gray-700 dark:text-gray-300 text-right">{{ number_format($row['avg_frequency'], 1) }}</td>
                            <td class="px-6 py-4 text-gray-700 dark:text-gray-300 text-right">{{ number_format($row['avg_recency'], 0) }} days</td>
                            <td class="px-6 py-4 font-semibold text-green-700 dark:text-green-400 text-right">${{ number_format($segmentRevenue, 2) }}</td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ $customerUrl }}" class="text-xs font-medium text-primary-600 dark:text-primary-400 hover:underline">View</a>
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
                            <td class="px-6 py-4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <script>
        function rfmDashboard() {
            return {
                stats: @json($stats),

                init() {
                    // Wait for Plotly to load
                    if (typeof Plotly === 'undefined') {
                        console.error('Plotly not loaded');
                        return;
                    }

                    this.$nextTick(() => {
                        this.renderCharts();
                    });
                },

                renderCharts() {
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
                        displayModeBar: false,
                        displaylogo: false
                    };

                    const layout = {
                        paper_bgcolor: isDark ? '#1f2937' : '#ffffff',
                        plot_bgcolor: isDark ? '#1f2937' : '#ffffff',
                        font: { color: isDark ? '#e5e7eb' : '#1f2937', size: 11 },
                        margin: { t: 10, r: 10, b: 40, l: 50 }
                    };

                    // Treemap
                    Plotly.newPlot('treemapChart', [{
                        type: 'treemap',
                        labels: this.stats.map(s => s.segment),
                        parents: this.stats.map(() => ''),
                        values: this.stats.map(s => s.customers),
                        text: this.stats.map(s => s.customers + ' customers'),
                        textposition: 'middle center',
                        marker: {
                            colors: this.stats.map(s => getColor(s.segment)),
                            line: { width: 2, color: isDark ? '#1f2937' : '#ffffff' }
                        },
                        hovertemplate: '<b>%{label}</b><br>%{value} customers<extra></extra>'
                    }], layout, config);

                    // Bar Chart
                    const sorted = [...this.stats].sort((a, b) => b.customers - a.customers);
                    Plotly.newPlot('barChart', [{
                        x: sorted.map(s => s.segment),
                        y: sorted.map(s => s.customers),
                        type: 'bar',
                        marker: { color: sorted.map(s => getColor(s.segment)) },
                        hovertemplate: '<b>%{x}</b><br>%{y} customers<extra></extra>'
                    }], {
                        ...layout,
                        xaxis: { tickangle: -45 },
                        yaxis: { title: 'Customers' }
                    }, config);

                    // Revenue Chart
                    const revSorted = [...this.stats]
                        .map(s => ({ segment: s.segment, revenue: s.customers * s.avg_monetary }))
                        .sort((a, b) => b.revenue - a.revenue);

                    Plotly.newPlot('revenueChart', [{
                        x: revSorted.map(s => s.segment),
                        y: revSorted.map(s => s.revenue),
                        type: 'bar',
                        marker: { color: revSorted.map(s => getColor(s.segment)) },
                        hovertemplate: '<b>%{x}</b><br>$%{y:,.2f}<extra></extra>'
                    }], {
                        ...layout,
                        xaxis: { tickangle: -45 },
                        yaxis: { title: 'Revenue ($)' }
                    }, config);

                    // RFM Metrics
                    Plotly.newPlot('metricsChart', [
                        {
                            x: this.stats.map(s => s.segment),
                            y: this.stats.map(s => s.avg_monetary),
                            name: 'Monetary',
                            type: 'bar',
                            marker: { color: '#22c55e' }
                        },
                        {
                            x: this.stats.map(s => s.segment),
                            y: this.stats.map(s => s.avg_frequency),
                            name: 'Frequency',
                            type: 'bar',
                            marker: { color: '#3b82f6' }
                        },
                        {
                            x: this.stats.map(s => s.segment),
                            y: this.stats.map(s => s.avg_recency),
                            name: 'Recency',
                            type: 'bar',
                            marker: { color: '#f59e0b' }
                        }
                    ], {
                        ...layout,
                        barmode: 'group',
                        xaxis: { tickangle: -45 },
                        legend: { orientation: 'h', y: -0.3 }
                    }, config);
                }
            }
        }
    </script>
@endif
