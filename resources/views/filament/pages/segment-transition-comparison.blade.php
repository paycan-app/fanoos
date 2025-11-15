<div class="space-y-6">
    @if($message)
        <div class="py-12 text-center text-neutral-500">
            <p>{{ $message }}</p>
        </div>
    @else
        @php
            $baselineCurrency = $snapshotA['currency'] ?? 'USD';
            $comparisonCurrency = $snapshotB['currency'] ?? 'USD';
            $snapshotAMetrics = data_get($snapshotA, 'metrics', []);
            $snapshotBMetrics = data_get($snapshotB, 'metrics', []);
            $customerDelta = data_get($changes, 'totals.customers.delta', 0);
            $customerDeltaClass = $customerDelta > 0 ? 'text-emerald-500' : ($customerDelta < 0 ? 'text-rose-500' : 'text-neutral-500');
            $activeDelta = data_get($changes, 'totals.active_customers.delta', 0);
            $activeDeltaClass = $activeDelta > 0 ? 'text-emerald-500' : ($activeDelta < 0 ? 'text-rose-500' : 'text-neutral-500');
            $monetaryDelta = data_get($changes, 'totals.monetary.delta', 0);
            $monetaryDeltaClass = $monetaryDelta > 0 ? 'text-emerald-500' : ($monetaryDelta < 0 ? 'text-rose-500' : 'text-neutral-500');
            $customerComparison = data_get($changes, 'totals.customers.comparison', 0);
            $activeComparison = data_get($changes, 'totals.active_customers.comparison', 0);
            $monetaryComparison = data_get($changes, 'totals.monetary.comparison', 0);
        @endphp

        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-lg border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">Period A</p>
                        <p class="text-xl font-semibold text-neutral-900 dark:text-neutral-50">{{ $snapshotA['as_of'] ?? '—' }}</p>
                    </div>
                    <span class="text-xs text-neutral-500 dark:text-neutral-400">{{ $snapshotA['timeframe_days'] ?? '—' }}-day lookback</span>
                </div>
                <dl class="mt-4 grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-400">Total customers</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-neutral-50">{{ number_format($snapshotA['total_customers'] ?? 0) }}</dd>
                    </div>
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-400">Active customers</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-neutral-50">{{ number_format($snapshotA['active_customers'] ?? 0) }}</dd>
                    </div>
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-400">Total monetary</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-neutral-50">{{ data_get($snapshotA, 'metrics.total_monetary_formatted', $baselineCurrency.' 0.00') }}</dd>
                    </div>
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-400">Avg frequency</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-neutral-50">{{ number_format(data_get($snapshotAMetrics, 'avg_frequency', 0), 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-400">Avg monetary</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-neutral-50">{{ number_format(data_get($snapshotAMetrics, 'avg_monetary', 0), 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-400">Avg recency</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-neutral-50">{{ data_get($snapshotAMetrics, 'avg_recency') !== null ? number_format(data_get($snapshotAMetrics, 'avg_recency'), 1).' days' : '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">Period B</p>
                        <p class="text-xl font-semibold text-neutral-900 dark:text-neutral-50">{{ $snapshotB['as_of'] ?? '—' }}</p>
                    </div>
                    <span class="text-xs text-neutral-500 dark:text-neutral-400">{{ $snapshotB['timeframe_days'] ?? '—' }}-day lookback</span>
                </div>
                <dl class="mt-4 grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-400">Total customers</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-neutral-50">{{ number_format($snapshotB['total_customers'] ?? 0) }}</dd>
                    </div>
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-400">Active customers</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-neutral-50">{{ number_format($snapshotB['active_customers'] ?? 0) }}</dd>
                    </div>
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-400">Total monetary</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-neutral-50">{{ data_get($snapshotB, 'metrics.total_monetary_formatted', $comparisonCurrency.' 0.00') }}</dd>
                    </div>
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-400">Avg frequency</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-neutral-50">{{ number_format(data_get($snapshotBMetrics, 'avg_frequency', 0), 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-400">Avg monetary</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-neutral-50">{{ number_format(data_get($snapshotBMetrics, 'avg_monetary', 0), 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-400">Avg recency</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-neutral-50">{{ data_get($snapshotBMetrics, 'avg_recency') !== null ? number_format(data_get($snapshotBMetrics, 'avg_recency'), 1).' days' : '—' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="rounded-lg border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
            <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-50">Change Highlights</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div>
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">Net customers</p>
                    <p class="text-2xl font-semibold text-neutral-900 dark:text-neutral-50">
                        {{ number_format($customerComparison ?? 0) }}
                        <span class="text-sm {{ $customerDeltaClass }}">
                            ({{ $customerDelta > 0 ? '+' : '' }}{{ number_format($customerDelta) }})
                        </span>
                    </p>
                </div>
                <div>
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">Active customers</p>
                    <p class="text-2xl font-semibold text-neutral-900 dark:text-neutral-50">
                        {{ number_format($activeComparison ?? 0) }}
                        <span class="text-sm {{ $activeDeltaClass }}">
                            ({{ $activeDelta > 0 ? '+' : '' }}{{ number_format($activeDelta) }})
                        </span>
                    </p>
                </div>
                <div>
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">Total monetary</p>
                    <p class="text-2xl font-semibold text-neutral-900 dark:text-neutral-50">
                        {{ $comparisonCurrency }} {{ number_format($monetaryComparison ?? 0, 2) }}
                        <span class="text-sm {{ $monetaryDeltaClass }}">
                            ({{ $monetaryDelta > 0 ? '+' : '' }}{{ number_format($monetaryDelta, 2) }})
                        </span>
                    </p>
                </div>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <div>
                    <h4 class="text-sm font-semibold text-neutral-900 dark:text-neutral-50">Top gainers</h4>
                    <ul class="mt-2 space-y-2 text-sm text-neutral-700 dark:text-neutral-300">
                        @forelse($changes['top_gainers'] ?? [] as $item)
                            <li class="flex items-center justify-between rounded-md border border-emerald-100 px-3 py-2 text-emerald-700 dark:border-emerald-500/20 dark:text-emerald-300">
                                <span>{{ $item['segment'] }}</span>
                                <span>+{{ number_format($item['delta']) }}</span>
                            </li>
                        @empty
                            <li class="rounded-md border border-dashed border-neutral-300 px-3 py-2 text-neutral-500 dark:border-neutral-600 dark:text-neutral-400">
                                No positive movement yet.
                            </li>
                        @endforelse
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-neutral-900 dark:text-neutral-50">Top losses</h4>
                    <ul class="mt-2 space-y-2 text-sm text-neutral-700 dark:text-neutral-300">
                        @forelse($changes['top_losers'] ?? [] as $item)
                            <li class="flex items-center justify-between rounded-md border border-rose-100 px-3 py-2 text-rose-600 dark:border-rose-500/20 dark:text-rose-300">
                                <span>{{ $item['segment'] }}</span>
                                <span>{{ number_format($item['delta']) }}</span>
                            </li>
                        @empty
                            <li class="rounded-md border border-dashed border-neutral-300 px-3 py-2 text-neutral-500 dark:border-neutral-600 dark:text-neutral-400">
                                No negative movement detected.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="mt-6">
                <h4 class="text-sm font-semibold text-neutral-900 dark:text-neutral-50">Insights</h4>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-neutral-700 dark:text-neutral-300">
                    @forelse($changes['insights'] ?? [] as $insight)
                        <li>{{ $insight }}</li>
                    @empty
                        <li>No notable insights yet. Adjust dates to load data.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="rounded-lg border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
            <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-50">Segment movement detail</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-neutral-200 text-left text-neutral-600 dark:border-neutral-700 dark:text-neutral-300">
                            <th class="py-2 pr-4">Segment</th>
                            <th class="py-2 pr-4 text-right">Period A</th>
                            <th class="py-2 pr-4 text-right">Period B</th>
                            <th class="py-2 pr-4 text-right">Δ Customers</th>
                            <th class="py-2 pr-4 text-right">Δ Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($changes['segment_deltas'] ?? [] as $delta)
                            @php
                                $deltaClass = $delta['delta'] > 0 ? 'text-emerald-600' : ($delta['delta'] < 0 ? 'text-rose-600' : 'text-neutral-500');
                            @endphp
                            <tr class="border-b border-neutral-100 text-neutral-800 dark:border-neutral-700 dark:text-neutral-200">
                                <td class="py-2 pr-4 font-medium">{{ $delta['segment'] }}</td>
                                <td class="py-2 pr-4 text-right">{{ number_format($delta['baseline']) }}</td>
                                <td class="py-2 pr-4 text-right">{{ number_format($delta['comparison']) }}</td>
                                <td class="py-2 pr-4 text-right {{ $deltaClass }}">
                                    {{ $delta['delta'] > 0 ? '+' : '' }}{{ number_format($delta['delta']) }}
                                </td>
                                <td class="py-2 pr-4 text-right {{ $deltaClass }}">
                                    {{ $delta['share_delta'] > 0 ? '+' : '' }}{{ number_format($delta['share_delta'], 2) }}%
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-neutral-500 dark:text-neutral-400">
                                    No segment movement calculated yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-lg border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
                <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-50">Transitions heatmap</h3>
                @if(($transitionMatrix['labels'] ?? []) && ($transitionMatrix['matrix'] ?? []))
                    <div id="segmentComparisonHeatmap" style="height: 480px;"></div>
                @else
                    <p class="py-10 text-center text-sm text-neutral-500 dark:text-neutral-400">Not enough data to render the heatmap.</p>
                @endif
            </div>
            <div class="rounded-lg border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
                <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-50">Segment Sankey flow</h3>
                @if(!empty($sankeyData['node']['labels']))
                    <div id="segmentSankey" style="height: 480px;"></div>
                @else
                    <p class="py-10 text-center text-sm text-neutral-500 dark:text-neutral-400">Sankey will appear once transitions are available.</p>
                @endif
            </div>
        </div>

        <div class="rounded-lg border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
            <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-50">Transitions matrix</h3>
            <div class="mt-4 overflow-x-auto">
                @if(($transitionMatrix['labels'] ?? []) && ($transitionMatrix['matrix'] ?? []))
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-neutral-200 dark:border-neutral-700">
                                <th class="py-2 pr-4 text-left text-neutral-600 dark:text-neutral-300">From \ To</th>
                                @foreach($transitionMatrix['labels'] as $label)
                                    <th class="py-2 pr-4 text-right text-neutral-600 dark:text-neutral-300">{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transitionMatrix['matrix'] as $rowIndex => $row)
                                <tr class="border-b border-neutral-100 dark:border-neutral-700">
                                    <td class="py-2 pr-4 font-medium text-neutral-900 dark:text-neutral-100">{{ $transitionMatrix['labels'][$rowIndex] }}</td>
                                    @foreach($row as $value)
                                        <td class="py-2 pr-4 text-right text-neutral-700 dark:text-neutral-300">{{ number_format($value) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="py-10 text-center text-sm text-neutral-500 dark:text-neutral-400">Transitions matrix will populate once both dates contain activity.</p>
                @endif
            </div>
        </div>
    @endif
</div>

<script src="https://cdn.plot.ly/plotly-2.27.0.min.js" charset="utf-8"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const matrix = @json($transitionMatrix);
        const sankey = @json($sankeyData);
        const isDark = document.documentElement.classList.contains('dark');
        const layoutDefaults = {
            paper_bgcolor: isDark ? '#262626' : '#ffffff',
            plot_bgcolor: isDark ? '#262626' : '#ffffff',
            font: { color: isDark ? '#f5f5f5' : '#171717', family: 'Inter, system-ui, sans-serif' },
            margin: { t: 40, r: 40, b: 80, l: 120 },
        };

        if (matrix.labels && matrix.labels.length && matrix.matrix && matrix.matrix.length) {
            Plotly.newPlot('segmentComparisonHeatmap', [{
                z: matrix.matrix,
                x: matrix.labels,
                y: matrix.labels,
                type: 'heatmap',
                colorscale: 'Viridis',
                hoverongaps: false,
                hovertemplate: 'From %{y} to %{x}<br>Count: %{z}<extra></extra>',
            }], {
                ...layoutDefaults,
                xaxis: { title: 'To segment', color: layoutDefaults.font.color, gridcolor: isDark ? '#404040' : '#e5e5e5' },
                yaxis: { title: 'From segment', color: layoutDefaults.font.color, gridcolor: isDark ? '#404040' : '#e5e5e5', autorange: 'reversed' },
                showlegend: false,
            }, {
                responsive: true,
                displayModeBar: true,
                displaylogo: false,
            });
        }

        if (sankey.node && sankey.node.labels && sankey.node.labels.length) {
            Plotly.newPlot('segmentSankey', [{
                type: 'sankey',
                orientation: 'h',
                node: {
                    pad: 24,
                    thickness: 18,
                    label: sankey.node.labels,
                    color: sankey.node.colors,
                },
                link: sankey.link ?? {},
            }], {
                ...layoutDefaults,
                title: undefined,
            }, {
                responsive: true,
                displayModeBar: true,
                displaylogo: false,
            });
        }
    });
</script>

