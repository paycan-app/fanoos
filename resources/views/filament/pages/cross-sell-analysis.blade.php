<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Top Cross-Sell Product Pairs</x-slot>
        <x-slot name="description">Products that are frequently purchased together in the same order.</x-slot>

        @if(empty($this->productCounts))
            <div class="rounded-2xl bg-warning-50 dark:bg-warning-500/10 p-6 border border-warning-200 dark:border-warning-500/20">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 flex-shrink-0 text-warning-600 dark:text-warning-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: block;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-warning-800 dark:text-warning-400 mb-1">
                            No cross-sell data available
                        </h3>
                        <p class="text-sm text-warning-700 dark:text-warning-300">
                            There are no orders with multiple products. Cross-sell analysis requires orders containing at least 2 different products.
                        </p>
                    </div>
                </div>
            </div>
        @else
            <div id="chart" class="w-full" style="min-height: 500px;"></div>

            <script src="https://cdn.plot.ly/plotly-2.27.0.min.js" charset="utf-8"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Plotly === 'undefined') {
                        console.error('Plotly.js failed to load');
                        return;
                    }

                    // Get data from Laravel
                    var productCounts = @json($this->productCounts);
                    var labels = @json($this->productLabels);

                    // Convert to arrays for Plotly
                    var values = Object.values(productCounts);

                    // Reverse arrays for horizontal bar chart (top to bottom)
                    labels = labels.reverse();
                    values = values.reverse();

                    // Create the chart
                    var data = [{
                        x: values,
                        y: labels,
                        type: 'bar',
                        orientation: 'h',
                        text: values,
                        textposition: 'outside',
                        marker: {
                            color: values,
                            colorscale: 'Viridis',
                            showscale: false
                        }
                    }];

                    var layout = {
                        title: {
                            text: 'Top 10 Cross-Sell Product Pairs',
                            font: {
                                size: 18
                            }
                        },
                        xaxis: {
                            title: 'Number of Orders'
                        },
                        yaxis: {
                            title: 'Product Pairs'
                        },
                        margin: {
                            l: 200,
                            r: 50,
                            t: 80,
                            b: 50
                        },
                        height: Math.max(500, labels.length * 50 + 150)
                    };

                    var config = {
                        responsive: true,
                        displayModeBar: true
                    };

                    // Render the chart
                    Plotly.newPlot('chart', data, layout, config);
                });
            </script>
        @endif
    </x-filament::section>
</x-filament-panels::page>
