<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="fi-section-header flex flex-col gap-3 px-6 py-4">
        <div>
            <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                Segment Revenue Treemap
            </h3>
            <p class="fi-section-header-description text-sm text-gray-500 dark:text-gray-400 mt-1">
                Hierarchical view of segments by total revenue and customer count
            </p>
        </div>
    </div>

    <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
        <div class="fi-section-content p-6">
            <div id="rfm-treemap-chart-{{ $this->getId() }}" class="h-96" wire:ignore></div>
        </div>
    </div>
</div>

@assets
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.48.0"></script>
@endassets

@script
<script>
    const payload = @js($this->getChartPayload());
    const chartId = 'rfm-treemap-chart-{{ $this->getId() }}';
    const currencyCode = @js($this->currencyCode ?? 'USD');
    const currencySymbol = @js($this->currencySymbol ?? '$');

    const container = document.getElementById(chartId);

    if (!payload.series.length && container) {
        container.innerHTML = `
            <div class="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">
                <p>No data available. Please calculate segments first.</p>
            </div>
        `;
    }

    if (payload.series.length && container) {
        const formatter = new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currencyCode,
            maximumFractionDigits: 0,
        });

        const preciseFormatter = new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currencyCode,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

        const chart = new ApexCharts(container, {
            chart: {
                type: 'donut',
                height: 380,
            },
            labels: payload.labels,
            series: payload.series,
            colors: payload.colors,
            legend: {
                position: 'bottom',
                fontSize: '13px',
                labels: {
                    colors: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#374151',
                },
            },
            dataLabels: {
                formatter(val, opts) {
                    const value = payload.series[opts.seriesIndex] ?? 0;
                    return `${val.toFixed(1)}%`;
                },
            },
            tooltip: {
                y: [{
                    formatter(value, opts) {
                        const meta = payload.meta[opts.seriesIndex] ?? {};

                        return [
                            `Revenue: ${preciseFormatter.format(value ?? 0)}`,
                            `Customers: ${Number(meta.customers ?? 0).toLocaleString()}`,
                            `Avg Spend: ${preciseFormatter.format(meta.avgMonetary ?? 0)}`,
                            `Avg Frequency: ${Number(meta.avgFrequency ?? 0).toLocaleString()} orders`,
                            `Avg Recency: ${meta.avgRecency ?? 0} days`,
                        ].join('<br>');
                    },
                }],
            },
            stroke: {
                colors: ['#ffffff'],
                width: 2,
            },
            responsive: [
                {
                    breakpoint: 768,
                    options: {
                        chart: {
                            height: 320,
                        },
                    },
                },
            ],
        });

        chart.render();
    }
</script>
@endscript
