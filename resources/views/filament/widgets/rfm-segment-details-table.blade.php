<x-filament::section>
    <x-slot name="heading">Segment Details &amp; Performance</x-slot>
    <x-slot name="description">Comprehensive breakdown of each customer segment with key metrics</x-slot>

    @php
        $tableData = $this->getTableData();
        $totals = [
            'customers' => collect($tableData)->sum('customers'),
            'revenue' => collect($tableData)->sum('total_revenue'),
        ];
    @endphp

    @if(empty($tableData))
        <div class="text-center py-8">
            <div class="mx-auto h-12 w-12 text-gray-400">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-full h-full" style="display: block;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No segment data available</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Calculate RFM segments to see data here.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10 text-sm">
                <thead class="bg-gray-50/50 dark:bg-white/5">
                    <tr class="text-gray-600 dark:text-gray-300 uppercase tracking-wider text-xs">
                        <th class="py-3.5 pl-4 pr-3 text-left sm:pl-6">Segment</th>
                        <th class="px-3 py-3.5 text-right">Customers</th>
                        <th class="px-3 py-3.5 text-right">Share</th>
                        <th class="px-3 py-3.5 text-right">Avg value</th>
                        <th class="px-3 py-3.5 text-right">Avg orders</th>
                        <th class="px-3 py-3.5 text-right">Avg recency</th>
                        <th class="px-3 py-3.5 text-right">Revenue</th>
                        <th class="py-3.5 pl-3 pr-4 text-left sm:pr-6">Action focus</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach($tableData as $row)
                        <tr class="bg-white dark:bg-gray-900/40">
                            <td class="py-4 pl-4 pr-3 align-top sm:pl-6">
                                <div class="space-y-2">
                                    <x-filament::badge :color="$row['color']" size="sm">
                                        {{ $row['segment'] }}
                                    </x-filament::badge>
                                    @if($row['description'])
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $row['description'] }}
                                        </p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-4 text-right font-semibold text-gray-900 dark:text-white">
                                {{ number_format($row['customers']) }}
                            </td>
                            <td class="px-3 py-4 text-right text-gray-700 dark:text-gray-300">
                                {{ number_format($row['percentage'], 1) }}%
                            </td>
                            <td class="px-3 py-4 text-right text-gray-900 dark:text-white">
                                {{ $this->currencySymbol }}{{ number_format($row['avg_monetary'], 2) }}
                            </td>
                            <td class="px-3 py-4 text-right text-gray-900 dark:text-white">
                                {{ number_format($row['avg_frequency'], 1) }}
                            </td>
                            <td class="px-3 py-4 text-right text-gray-900 dark:text-white">
                                {{ number_format($row['avg_recency'], 0) }}d
                            </td>
                            <td class="px-3 py-4 text-right font-semibold text-gray-900 dark:text-white">
                                {{ $this->currencySymbol }}{{ number_format($row['total_revenue'], 0) }}
                            </td>
                            <td class="py-4 pl-3 pr-4 text-sm text-gray-600 dark:text-gray-300 sm:pr-6">
                                {{ $row['business_action'] ?: 'Monitor segment performance and adjust campaigns accordingly.' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50/50 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:bg-white/5 dark:text-gray-300">
                    <tr>
                        <td class="py-3.5 pl-4 pr-3 text-left sm:pl-6">Total</td>
                        <td class="px-3 py-3.5 text-right text-gray-900 dark:text-white">
                            {{ number_format($totals['customers']) }}
                        </td>
                        <td class="px-3 py-3.5 text-right text-gray-900 dark:text-white">100%</td>
                        <td colspan="3"></td>
                        <td class="px-3 py-3.5 text-right text-gray-900 dark:text-white">
                            {{ $this->currencySymbol }}{{ number_format($totals['revenue'], 0) }}
                        </td>
                        <td class="py-3.5 pl-3 pr-4 sm:pr-6"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</x-filament::section>
