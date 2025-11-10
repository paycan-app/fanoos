<div class="space-y-6">
    @if($message)
        <div class="py-8 text-center text-neutral-500">
            <p>{{ $message }}</p>
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
                @php($totalCustomers = array_sum(array_column($stats, 'customers')))
                <div class="text-2xl font-bold text-success-900 dark:text-success-100 mt-1">{{ number_format($totalCustomers) }}</div>
            </div>
            <div class="p-4 bg-warning-50 dark:bg-warning-900/20 rounded-lg border border-warning-200 dark:border-warning-800">
                <div class="text-sm font-medium text-warning-600 dark:text-warning-400">Avg Monetary Value</div>
                <div class="text-2xl font-bold text-warning-900 dark:text-warning-100 mt-1">
                    ${{ count($stats) ? number_format(array_sum(array_column($stats, 'avg_monetary')) / count($stats), 2) : '0.00' }}
                </div>
            </div>
        </div>

        <!-- Marimekko Chart -->
        <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-4 mb-6">
            <h3 class="text-lg font-semibold mb-4 text-neutral-900 dark:text-neutral-100">Marimekko: Monetary Distribution within Segments</h3>
            <div id="mekkoChart" style="height: 500px;"></div>
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
            (function () {
                const mekko = @json($mekko);
                const isDarkMode = document.documentElement.classList.contains('dark');

                const colors = [
                    '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6',
                    '#ec4899', '#14b8a6', '#f97316', '#6366f1', '#84cc16', '#06b6d4'
                ];

                const layoutDefaults = {
                    paper_bgcolor: isDarkMode ? '#262626' : '#ffffff',
                    plot_bgcolor: isDarkMode ? '#262626' : '#ffffff',
                    font: { color: isDarkMode ? '#e5e5e5' : '#171717', family: 'Inter, system-ui, sans-serif' },
                    margin: { t: 40, r: 20, b: 60, l: 60 },
                };

                const segments = mekko.segments || [];
                const binLabels = mekko.binLabels || [];

                // Compute segment centers and widths on a linear axis (Marimekko style)
                let cum = 0;
                const xCenters = [];
                const widths = [];
                const tickvals = [];
                const ticktext = [];

                segments.forEach((s) => {
                    const w = s.share; // proportional width (0..1)
                    const x = cum + (w / 2);
                    xCenters.push(x);
                    widths.push(w);
                    tickvals.push(x);
                    ticktext.push(s.key);
                    cum += w;
                });

                // Build stacked traces for each monetary bin across segments
                const traces = binLabels.map((label, idx) => {
                    return {
                        x: xCenters,
                        y: segments.map(s => Math.round((s.bins[label] || 0) * 100)),
                        name: label,
                        type: 'bar',
                        marker: { color: colors[idx % colors.length] },
                        width: widths,
                        hovertemplate: `<b>${label}</b><br>% within segment: %{y:.1f}%<extra></extra>`,
                    };
                });

                const layout = {
                    ...layoutDefaults,
                    barmode: 'stack',
                    xaxis: {
                        type: 'linear',
                        tickmode: 'array',
                        tickvals: tickvals,
                        ticktext: ticktext,
                        title: 'Segment (variable width = segment share)',
                        gridcolor: isDarkMode ? '#404040' : '#e5e5e5',
                        color: isDarkMode ? '#e5e5e5' : '#171717',
                        range: [0, 1], // total width sums to 1
                    },
                    yaxis: {
                        title: 'Composition (%)',
                        gridcolor: isDarkMode ? '#404040' : '#e5e5e5',
                        color: isDarkMode ? '#e5e5e5' : '#171717',
                        range: [0, 100],
                    },
                    showlegend: true,
                    legend: {
                        orientation: 'h',
                        y: -0.2,
                        font: { color: isDarkMode ? '#e5e5e5' : '#171717' },
                    },
                };

                Plotly.newPlot('mekkoChart', traces, layout, {
                    responsive: true,
                    displayModeBar: true,
                    displaylogo: false,
                });
            })();
        </script>
    @endif
</div>