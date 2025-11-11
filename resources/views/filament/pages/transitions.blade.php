<div class="space-y-6">
    @if($message)
        <div class="py-8 text-center text-neutral-500">
            <p>{{ $message }}</p>
        </div>
    @else
        <!-- Controls -->
        <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Baseline Period (days)</label>
                    <select class="fi-input mt-1 w-full rounded-md border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-900"
                            id="baselineDays">
                        <option value="90">90</option>
                        <option value="180">180</option>
                        <option value="365">365</option>
                        <option value="730">730</option>
                        <option value="1825">1825</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Comparison Period (days)</label>
                    <select class="fi-input mt-1 w-full rounded-md border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-900"
                            id="comparisonDays">
                        <option value="90">90</option>
                        <option value="180">180</option>
                        <option value="365">365</option>
                        <option value="730">730</option>
                        <option value="1825">1825</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="button"
                            class="fi-btn px-4 py-2 rounded-md bg-primary-600 text-white hover:bg-primary-700">
                        Analyze Transitions
                    </button>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <!-- Controls are provided by the page’s DatePickers above -->
        
            <!-- Heatmap Chart -->
            <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-4">
                <h3 class="text-lg font-semibold mb-4 text-neutral-900 dark:text-neutral-100">Segment Transitions Heatmap</h3>
                <div id="transitionsHeatmap" style="height: 540px;"></div>
            </div>
            <!-- Churn Distribution Chart -->
            <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-4">
                <h3 class="text-lg font-semibold mb-4 text-neutral-900 dark:text-neutral-100">Churn Distribution</h3>
                <div id="churnDistributionChart" style="height: 540px;"></div>
            </div>
        
            <!-- Transitions Table -->
            <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-4">
                <h3 class="text-lg font-semibold mb-4 text-neutral-900 dark:text-neutral-100">Transitions Matrix</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-neutral-200 dark:border-neutral-700">
                                <th class="text-left py-3 pr-4 font-semibold text-neutral-700 dark:text-neutral-300">From \ To</th>
                                @foreach($labels as $to)
                                    <th class="text-right py-3 pr-4 font-semibold text-neutral-700 dark:text-neutral-300">{{ $to }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($matrix as $i => $row)
                            <tr class="border-b border-neutral-100 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-700/50">
                                <td class="py-3 pr-4 font-medium text-neutral-900 dark:text-neutral-100">{{ $labels[$i] }}</td>
                                @foreach($row as $val)
                                    <td class="py-3 pr-4 text-right text-neutral-700 dark:text-neutral-300">{{ number_format($val) }}</td>
                                @endforeach
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
                    const labels = @json($labels);
                    const matrix = @json($matrix);
                    const isDarkMode = document.documentElement.classList.contains('dark');
        
                    const layoutDefaults = {
                        paper_bgcolor: isDarkMode ? '#262626' : '#ffffff',
                        plot_bgcolor: isDarkMode ? '#262626' : '#ffffff',
                        font: { color: isDarkMode ? '#e5e5e5' : '#171717', family: 'Inter, system-ui, sans-serif' },
                        margin: { t: 40, r: 40, b: 80, l: 120 },
                    };
        
                    const data = [{
                        z: matrix,
                        x: labels,
                        y: labels,
                        type: 'heatmap',
                        colorscale: 'Blues',
                        hoverongaps: false,
                        hovertemplate: 'From %{y} to %{x}<br>Count: %{z}<extra></extra>',
                    }];
        
                    const layout = {
                        ...layoutDefaults,
                        xaxis: { title: 'To Segment', color: isDarkMode ? '#e5e5e5' : '#171717', gridcolor: isDarkMode ? '#404040' : '#e5e5e5' },
                        yaxis: { title: 'From Segment', color: isDarkMode ? '#e5e5e5' : '#171717', gridcolor: isDarkMode ? '#404040' : '#e5e5e5', autorange: 'reversed' },
                        showlegend: false,
                    };
        
                    Plotly.newPlot('transitionsHeatmap', data, layout, {
                        responsive: true,
                        displayModeBar: true,
                        displaylogo: false,
                    });
                })();

                // Churn JSON plot (via fetch + asset URL)
                (function () {
                    const isDarkMode = document.documentElement.classList.contains('dark');
                    const url = '{{ asset('examples/churn_distribution.json') }}';

                    function decodeB64ToFloat64(b64) {
                        const bin = atob(b64);
                        const bytes = new Uint8Array(bin.length);
                        for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
                        const view = new DataView(bytes.buffer);
                        const out = new Array(bytes.length / 8);
                        for (let i = 0; i < out.length; i++) out[i] = view.getFloat64(i * 8, true);
                        return out;
                    }
                    function normalizeAxis(axis) {
                        if (axis && typeof axis === 'object' && axis.bdata && axis.dtype === 'f8') {
                            return decodeB64ToFloat64(axis.bdata);
                        }
                        return axis;
                    }
                    function normalizeFig(fig) {
                        if (!fig || !Array.isArray(fig.data)) return fig;
                        fig.data = fig.data.map((trace) => {
                            const t = { ...trace };
                            t.x = normalizeAxis(t.x);
                            t.y = normalizeAxis(t.y);
                            return t;
                        });
                        return fig;
                    }

                    fetch(url, { cache: 'no-store' })
                        .then((resp) => {
                            if (!resp.ok) throw new Error('HTTP ' + resp.status + ' for ' + url);
                            return resp.json();
                        })
                        .then((fig) => {
                            const normalized = normalizeFig(fig);

                            const layout = { ...(normalized.layout || {}) };
                            if (isDarkMode) {
                                layout.paper_bgcolor = '#262626';
                                layout.plot_bgcolor = '#262626';
                                layout.font = { ...(layout.font || {}), color: '#e5e5e5' };
                                layout.xaxis = { ...(layout.xaxis || {}), gridcolor: '#404040', linecolor: '#e5e5e5' };
                                layout.yaxis = { ...(layout.yaxis || {}), gridcolor: '#404040', linecolor: '#e5e5e5' };
                            }

                            Plotly.newPlot('churnDistributionChart', normalized.data, layout, {
                                responsive: true,
                                displayModeBar: true,
                                displaylogo: false,
                            });
                        })
                        .catch((err) => {
                            console.error('Failed to load churn_distribution.json', err);
                        });
                })();
            </script>
        </div>
    @endif
</div>