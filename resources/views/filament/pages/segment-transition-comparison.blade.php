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
            $primaryInsight = data_get($changes, 'insights.0');
            $secondaryInsight = data_get($changes, 'insights.1');
            $additionalInsights = collect($changes['insights'] ?? [])->skip(2)->values();
        @endphp

        <div id="segmentComparisonData"
             data-matrix="{{ base64_encode(json_encode($transitionMatrix)) }}"
             data-sankey="{{ base64_encode(json_encode($sankeyData)) }}"
             class="hidden"
             aria-hidden="true"></div>

        <x-filament::section>
            <x-slot name="heading">Segment Sankey flow</x-slot>
            <x-slot name="description">Visualize how customers move between segments from Period A to Period B.</x-slot>

            @if(!empty($sankeyData['node']['labels']))
                <div id="segmentSankey" class="w-full" style="min-height: 560px;"></div>
            @else
                <p class="py-10 text-center text-sm text-neutral-500 dark:text-neutral-300">Run an analysis to populate the Sankey flow.</p>
            @endif
        </x-filament::section>

        <div class="grid gap-4 md:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">Period A Snapshot</x-slot>
                <x-slot name="description">Baseline measured as of {{ $snapshotA['as_of'] ?? '—' }}.</x-slot>

                <dl class="mt-4 grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-300">Total customers</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-white">{{ number_format($snapshotA['total_customers'] ?? 0) }}</dd>
                    </div>
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-300">Active customers</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-white">{{ number_format($snapshotA['active_customers'] ?? 0) }}</dd>
                    </div>
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-300">Total monetary</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-white">{{ data_get($snapshotA, 'metrics.total_monetary_formatted', $baselineCurrency.' 0.00') }}</dd>
                    </div>
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-300">Avg frequency</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-white">{{ number_format(data_get($snapshotAMetrics, 'avg_frequency', 0), 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-300">Avg monetary</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-white">{{ number_format(data_get($snapshotAMetrics, 'avg_monetary', 0), 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-300">Avg recency</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-white">{{ data_get($snapshotAMetrics, 'avg_recency') !== null ? number_format(data_get($snapshotAMetrics, 'avg_recency'), 1).' days' : '—' }}</dd>
                    </div>
                </dl>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Period B Snapshot</x-slot>
                <x-slot name="description">Comparison measured as of {{ $snapshotB['as_of'] ?? '—' }}.</x-slot>
                <dl class="mt-4 grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-300">Total customers</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-white">{{ number_format($snapshotB['total_customers'] ?? 0) }}</dd>
                    </div>
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-300">Active customers</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-white">{{ number_format($snapshotB['active_customers'] ?? 0) }}</dd>
                    </div>
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-300">Total monetary</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-white">{{ data_get($snapshotB, 'metrics.total_monetary_formatted', $comparisonCurrency.' 0.00') }}</dd>
                    </div>
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-300">Avg frequency</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-white">{{ number_format(data_get($snapshotBMetrics, 'avg_frequency', 0), 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-300">Avg monetary</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-white">{{ number_format(data_get($snapshotBMetrics, 'avg_monetary', 0), 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-neutral-500 dark:text-neutral-300">Avg recency</dt>
                        <dd class="text-lg font-semibold text-neutral-900 dark:text-white">{{ data_get($snapshotBMetrics, 'avg_recency') !== null ? number_format(data_get($snapshotBMetrics, 'avg_recency'), 1).' days' : '—' }}</dd>
                    </div>
                </dl>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">Change Highlights</x-slot>
            <div class="mt-3 rounded-md border-l-4 border-primary-400 bg-primary-50/70 px-4 py-3 text-sm text-primary-900 dark:border-primary-300 dark:bg-primary-500/10 dark:text-primary-100">
                <p>{{ $primaryInsight ?? 'Choose two periods and run the analysis to generate human-readable insights.' }}</p>
                @if($secondaryInsight)
                    <p class="mt-1">{{ $secondaryInsight }}</p>
                @endif
                @if($additionalInsights->isNotEmpty())
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-primary-900/80 dark:text-primary-100/80">
                        @foreach($additionalInsights as $insight)
                            <li>{{ $insight }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div>
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">Net customers</p>
                    <p class="text-2xl font-semibold text-neutral-900 dark:text-white">
                        {{ number_format($customerComparison ?? 0) }}
                        <span class="text-sm {{ $customerDeltaClass }}">
                            ({{ $customerDelta > 0 ? '+' : '' }}{{ number_format($customerDelta) }})
                        </span>
                    </p>
                </div>
                <div>
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">Active customers</p>
                    <p class="text-2xl font-semibold text-neutral-900 dark:text-white">
                        {{ number_format($activeComparison ?? 0) }}
                        <span class="text-sm {{ $activeDeltaClass }}">
                            ({{ $activeDelta > 0 ? '+' : '' }}{{ number_format($activeDelta) }})
                        </span>
                    </p>
                </div>
                <div>
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">Total monetary</p>
                    <p class="text-2xl font-semibold text-neutral-900 dark:text-white">
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
                    <div class="mt-2 flex flex-wrap gap-2">
                        @forelse($changes['top_gainers'] ?? [] as $item)
                            <x-filament::badge color="success">
                                <span class="font-semibold">{{ $item['segment'] }}</span>
                                <span class="ml-2 text-xs">+{{ number_format($item['delta']) }}</span>
                            </x-filament::badge>
                        @empty
                            <x-filament::badge color="gray">No positive movement yet.</x-filament::badge>
                        @endforelse
                    </div>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-neutral-900 dark:text-neutral-50">Top losses</h4>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @forelse($changes['top_losers'] ?? [] as $item)
                            <x-filament::badge color="danger">
                                <span class="font-semibold">{{ $item['segment'] }}</span>
                                <span class="ml-2 text-xs">{{ number_format($item['delta']) }}</span>
                            </x-filament::badge>
                        @empty
                            <x-filament::badge color="gray">No negative movement detected.</x-filament::badge>
                        @endforelse
                    </div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Transitions Heatmap</x-slot>
            @if(($transitionMatrix['labels'] ?? []) && ($transitionMatrix['matrix'] ?? []))
                <div id="segmentComparisonHeatmap" class="mt-4 w-full" style="min-height: 520px;"></div>
            @else
                <p class="py-10 text-center text-sm text-neutral-500 dark:text-neutral-400">Not enough data to render the heatmap.</p>
            @endif
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Segment Movement Detail</x-slot>
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
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Transitions Matrix</x-slot>
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
        </x-filament::section>
    @endif
</div>

<script src="https://cdn.plot.ly/plotly-2.27.0.min.js" charset="utf-8"></script>
<script>
    (function () {
        const decodePayload = (value) => {
            if (!value) {
                return null;
            }

            try {
                return JSON.parse(atob(value));
            } catch (error) {
                console.error('Failed to decode segment comparison payload', error);
                return null;
            }
        };

        const getPayload = () => {
            const holder = document.getElementById('segmentComparisonData');

            if (!holder) {
                return { matrix: null, sankey: null };
            }

            return {
                matrix: decodePayload(holder.dataset.matrix || ''),
                sankey: decodePayload(holder.dataset.sankey || ''),
            };
        };

        const computeHeight = (element, fallback = 520) => {
            if (!element) {
                return fallback;
            }

            const width = element.clientWidth || fallback;

            return Math.max(fallback, width * 0.55);
        };

        const renderCharts = () => {
            if (typeof Plotly === 'undefined') {
                return;
            }

            const isDark = document.documentElement.classList.contains('dark');
            const layoutDefaults = {
                paper_bgcolor: isDark ? '#1f1f1f' : '#ffffff',
                plot_bgcolor: isDark ? '#1f1f1f' : '#ffffff',
                font: { color: isDark ? '#f5f5f5' : '#171717', family: 'Inter, system-ui, sans-serif' },
                margin: { t: 40, r: 40, b: 80, l: 120 },
            };

            const { matrix, sankey } = getPayload();
            const heatmapEl = document.getElementById('segmentComparisonHeatmap');
            const sankeyEl = document.getElementById('segmentSankey');

            if (heatmapEl) {
                if (matrix?.labels?.length && matrix?.matrix?.length) {
                    Plotly.react(heatmapEl, [{
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
                        height: computeHeight(heatmapEl),
                    }, {
                        responsive: true,
                        displayModeBar: true,
                        displaylogo: false,
                    });
                } else {
                    Plotly.purge(heatmapEl);
                }
            }

            if (sankeyEl) {
                if (sankey?.node?.labels?.length) {
                    Plotly.react(sankeyEl, [{
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
                        margin: { t: 20, r: 40, b: 40, l: 40 },
                        height: computeHeight(sankeyEl, 560),
                    }, {
                        responsive: true,
                        displayModeBar: true,
                        displaylogo: false,
                    });
                } else {
                    Plotly.purge(sankeyEl);
                }
            }
        };

        const scheduleRender = () => requestAnimationFrame(renderCharts);

        if (!window.segmentComparisonChartsInitialized) {
            window.segmentComparisonChartsInitialized = true;

            document.addEventListener('DOMContentLoaded', scheduleRender);

            const registerLivewireHook = () => {
                if (window.Livewire) {
                    Livewire.on('segment-comparison-refreshed', scheduleRender);
                }
            };

            if (window.Livewire) {
                registerLivewireHook();
            } else {
                document.addEventListener('livewire:init', () => registerLivewireHook(), { once: true });
            }

            const themeObserver = new MutationObserver((mutations) => {
                for (const mutation of mutations) {
                    if (mutation.attributeName === 'class') {
                        scheduleRender();
                        break;
                    }
                }
            });

            themeObserver.observe(document.documentElement, { attributes: true });
        }

        scheduleRender();
    })();
</script>

