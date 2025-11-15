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
            <div id="rfm-treemap-chart-{{ $this->getId() }}" style="height: 400px;" wire:ignore></div>
        </div>
    </div>
</div>

@assets
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.min.js"></script>
@endassets

@script
<script>
    $wire.on('$refresh', () => {
        location.reload();
    });

    // Get treemap data from Livewire component
    const treemapData = @js($this->getTreemapData());
    const chartId = 'rfm-treemap-chart-{{ $this->getId() }}';

    if (treemapData && treemapData.length > 0) {
            const options = {
                series: [{
                    data: treemapData
                }],
                chart: {
                    type: 'treemap',
                    height: 400,
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            zoom: false,
                            zoomin: false,
                            zoomout: false,
                            pan: false,
                            reset: false
                        }
                    },
                    animations: {
                        enabled: true,
                        speed: 800,
                        animateGradually: {
                            enabled: true,
                            delay: 150
                        }
                    }
                },
                plotOptions: {
                    treemap: {
                        distributed: true,
                        enableShades: false,
                        colorScale: {
                            ranges: treemapData.map(item => ({
                                from: item.y,
                                to: item.y,
                                color: item.fillColor
                            }))
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    style: {
                        fontSize: '14px',
                        fontWeight: 'bold',
                        colors: ['#fff']
                    },
                    formatter: function(text, op) {
                        return [text, op.value(op.dataPointIndex).toLocaleString('en-US', {
                            style: 'currency',
                            currency: 'USD',
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 0
                        })];
                    }
                },
                tooltip: {
                    enabled: true,
                    custom: function({seriesIndex, dataPointIndex, w}) {
                        const data = w.config.series[0].data[dataPointIndex];
                        return `
                            <div class="px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow-lg">
                                <div class="font-bold text-sm mb-1">${data.x}</div>
                                <div class="text-xs space-y-1">
                                    <div><strong>Customers:</strong> ${data.customers.toLocaleString()}</div>
                                    <div><strong>Total Revenue:</strong> $${data.y.toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                                    <div><strong>Avg Monetary:</strong> $${data.avgMonetary}</div>
                                    <div><strong>Avg Frequency:</strong> ${data.avgFrequency} orders</div>
                                    <div><strong>Avg Recency:</strong> ${data.avgRecency} days</div>
                                </div>
                            </div>
                        `;
                    }
                },
                legend: {
                    show: false
                },
                theme: {
                    mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                }
            };

        // Render chart
        const chartElement = document.querySelector(`#${chartId}`);
        if (chartElement) {
            const chart = new ApexCharts(chartElement, options);
            chart.render();

            // Listen for theme changes
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.attributeName === 'class') {
                        const isDark = document.documentElement.classList.contains('dark');
                        chart.updateOptions({
                            theme: {
                                mode: isDark ? 'dark' : 'light'
                            }
                        });
                    }
                });
            });

            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class']
            });
        }
    } else {
        const chartElement = document.querySelector(`#${chartId}`);
        if (chartElement) {
            chartElement.innerHTML = `
                <div class="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">
                    <p>No data available. Please calculate segments first.</p>
                </div>
            `;
        }
    }
</script>
@endscript
