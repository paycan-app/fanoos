<div class="space-y-6">
    @if(empty($stats) || !is_array($stats))
        <div class="py-8 text-center text-neutral-500">
            <p>No stats yet. Click "Calculate Segments" to analyze your customers.</p>
        </div>
    @else
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg border border-primary-200 dark:border-primary-800">
                <div class="text-sm font-medium text-primary-600 dark:text-primary-400">Total Segments</div>
                <div class="text-2xl font-bold text-primary-900 dark:text-primary-100 mt-1">{{ count($stats) }}</div>
            </div>
            <div class="p-4 bg-success-50 dark:bg-success-900/20 rounded-lg border border-success-200 dark:border-success-800">
                <div class="text-sm font-medium text-success-600 dark:text-success-400">Total Customers</div>
                <div class="text-2xl font-bold text-success-900 dark:text-success-100 mt-1">{{ number_format(array_sum(array_column($stats, 'customers'))) }}</div>
            </div>
            <div class="p-4 bg-warning-50 dark:bg-warning-900/20 rounded-lg border border-warning-200 dark:border-warning-800">
                <div class="text-sm font-medium text-warning-600 dark:text-warning-400">Avg Monetary Value</div>
                <div class="text-2xl font-bold text-warning-900 dark:text-warning-100 mt-1">${{ number_format(array_sum(array_column($stats, 'avg_monetary')) / count($stats), 2) }}</div>
            </div>
        </div>

        <!-- Interactive Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Segment Distribution (Pie Chart) -->
            <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-4">
                <h3 class="text-lg font-semibold mb-4 text-neutral-900 dark:text-neutral-100">Customer Distribution by Segment</h3>
                <div id="pieChart" style="height: 400px;"></div>
            </div>

            <!-- Customer Count by Segment (Bar Chart) -->
            <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-4">
                <h3 class="text-lg font-semibold mb-4 text-neutral-900 dark:text-neutral-100">Customer Count by Segment</h3>
                <div id="barChart" style="height: 400px;"></div>
            </div>
        </div>

        <!-- RFM Metrics Comparison (Multi-bar Chart) -->
        <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-4 mb-6">
            <h3 class="text-lg font-semibold mb-4 text-neutral-900 dark:text-neutral-100">RFM Metrics by Segment</h3>
            <div id="metricsChart" style="height: 500px;"></div>
        </div>

        <!-- Data Table -->
        <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-4">
            <h3 class="text-lg font-semibold mb-4 text-neutral-900 dark:text-neutral-100">Detailed Segment Statistics</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-neutral-200 dark:border-neutral-700">
                            <th class="text-left py-3 pr-4 font-semibold text-neutral-700 dark:text-neutral-300">Segment</th>
                            <th class="text-right py-3 pr-4 font-semibold text-neutral-700 dark:text-neutral-300">Customers</th>
                            <th class="text-right py-3 pr-4 font-semibold text-neutral-700 dark:text-neutral-300">Avg Monetary</th>
                            <th class="text-right py-3 pr-4 font-semibold text-neutral-700 dark:text-neutral-300">Avg Frequency</th>
                            <th class="text-right py-3 font-semibold text-neutral-700 dark:text-neutral-300">Avg Recency (days)</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($stats as $row)
                        <tr class="border-b border-neutral-100 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-700/50">
                            <td class="py-3 pr-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/50 text-primary-800 dark:text-primary-200">
                                    {{ $row['segment'] }}
                                </span>
                            </td>
                            <td class="py-3 pr-4 text-right font-medium text-neutral-900 dark:text-neutral-100">{{ number_format($row['customers']) }}</td>
                            <td class="py-3 pr-4 text-right text-neutral-700 dark:text-neutral-300">${{ number_format($row['avg_monetary'], 2) }}</td>
                            <td class="py-3 pr-4 text-right text-neutral-700 dark:text-neutral-300">{{ number_format($row['avg_frequency'], 1) }}</td>
                            <td class="py-3 text-right text-neutral-700 dark:text-neutral-300">{{ number_format($row['avg_recency'], 0) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Plotly.js Script -->
        <script src="https://cdn.plot.ly/plotly-2.27.0.min.js" charset="utf-8"></script>
        <script>
            (function() {
                const stats = @json($stats);
                const isDarkMode = document.documentElement.classList.contains('dark');

                // Color palette
                const colors = [
                    '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6',
                    '#ec4899', '#14b8a6', '#f97316', '#6366f1', '#84cc16', '#06b6d4'
                ];

                // Common layout settings for dark mode
                const layoutDefaults = {
                    paper_bgcolor: isDarkMode ? '#262626' : '#ffffff',
                    plot_bgcolor: isDarkMode ? '#262626' : '#ffffff',
                    font: {
                        color: isDarkMode ? '#e5e5e5' : '#171717',
                        family: 'Inter, system-ui, sans-serif'
                    },
                    margin: { t: 40, r: 20, b: 60, l: 60 },
                };

                // 1. Pie Chart - Customer Distribution
                const pieData = [{
                    values: stats.map(s => s.customers),
                    labels: stats.map(s => s.segment),
                    type: 'pie',
                    marker: {
                        colors: colors,
                        line: {
                            color: isDarkMode ? '#404040' : '#ffffff',
                            width: 2
                        }
                    },
                    textinfo: 'label+percent',
                    textposition: 'inside',
                    hovertemplate: '<b>%{label}</b><br>Customers: %{value}<br>Percentage: %{percent}<extra></extra>',
                }];

                const pieLayout = {
                    ...layoutDefaults,
                    showlegend: true,
                    legend: {
                        orientation: 'v',
                        x: 1,
                        y: 0.5,
                        font: {
                            color: isDarkMode ? '#e5e5e5' : '#171717'
                        }
                    }
                };

                Plotly.newPlot('pieChart', pieData, pieLayout, {
                    responsive: true,
                    displayModeBar: true,
                    displaylogo: false
                });

                // 2. Bar Chart - Customer Count
                const barData = [{
                    x: stats.map(s => s.segment),
                    y: stats.map(s => s.customers),
                    type: 'bar',
                    marker: {
                        color: colors,
                        line: {
                            color: isDarkMode ? '#404040' : '#ffffff',
                            width: 1
                        }
                    },
                    hovertemplate: '<b>%{x}</b><br>Customers: %{y}<extra></extra>',
                }];

                const barLayout = {
                    ...layoutDefaults,
                    xaxis: {
                        title: 'Segment',
                        gridcolor: isDarkMode ? '#404040' : '#e5e5e5',
                        color: isDarkMode ? '#e5e5e5' : '#171717'
                    },
                    yaxis: {
                        title: 'Customer Count',
                        gridcolor: isDarkMode ? '#404040' : '#e5e5e5',
                        color: isDarkMode ? '#e5e5e5' : '#171717'
                    }
                };

                Plotly.newPlot('barChart', barData, barLayout, {
                    responsive: true,
                    displayModeBar: true,
                    displaylogo: false
                });

                // 3. Multi-bar Chart - RFM Metrics Comparison
                const metricsData = [
                    {
                        x: stats.map(s => s.segment),
                        y: stats.map(s => s.avg_monetary),
                        name: 'Avg Monetary ($)',
                        type: 'bar',
                        marker: { color: '#10b981' },
                        yaxis: 'y',
                        hovertemplate: '<b>%{x}</b><br>Monetary: $%{y:.2f}<extra></extra>',
                    },
                    {
                        x: stats.map(s => s.segment),
                        y: stats.map(s => s.avg_frequency),
                        name: 'Avg Frequency',
                        type: 'bar',
                        marker: { color: '#3b82f6' },
                        yaxis: 'y2',
                        hovertemplate: '<b>%{x}</b><br>Frequency: %{y:.1f}<extra></extra>',
                    },
                    {
                        x: stats.map(s => s.segment),
                        y: stats.map(s => s.avg_recency),
                        name: 'Avg Recency (days)',
                        type: 'bar',
                        marker: { color: '#f59e0b' },
                        yaxis: 'y3',
                        hovertemplate: '<b>%{x}</b><br>Recency: %{y:.0f} days<extra></extra>',
                    }
                ];

                const metricsLayout = {
                    ...layoutDefaults,
                    barmode: 'group',
                    xaxis: {
                        title: 'Segment',
                        gridcolor: isDarkMode ? '#404040' : '#e5e5e5',
                        color: isDarkMode ? '#e5e5e5' : '#171717'
                    },
                    yaxis: {
                        title: 'Monetary ($)',
                        titlefont: { color: '#10b981' },
                        tickfont: { color: '#10b981' },
                        gridcolor: isDarkMode ? '#404040' : '#e5e5e5',
                    },
                    yaxis2: {
                        title: 'Frequency',
                        titlefont: { color: '#3b82f6' },
                        tickfont: { color: '#3b82f6' },
                        overlaying: 'y',
                        side: 'right',
                        showgrid: false,
                    },
                    yaxis3: {
                        title: 'Recency (days)',
                        titlefont: { color: '#f59e0b' },
                        tickfont: { color: '#f59e0b' },
                        anchor: 'free',
                        overlaying: 'y',
                        side: 'right',
                        position: 0.95,
                        showgrid: false,
                    },
                    legend: {
                        orientation: 'h',
                        y: -0.2,
                        font: {
                            color: isDarkMode ? '#e5e5e5' : '#171717'
                        }
                    }
                };

                Plotly.newPlot('metricsChart', metricsData, metricsLayout, {
                    responsive: true,
                    displayModeBar: true,
                    displaylogo: false
                });
            })();
        </script>
    @endif
</div>
