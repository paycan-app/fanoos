<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Detailed Segment Analysis
        </x-slot>

        <x-slot name="description">
            Comprehensive breakdown of each customer segment with business insights
        </x-slot>

        @php
            $tableData = $this->getTableData();
        @endphp

        @if(empty($tableData))
            <div class="text-center py-12">
                <x-filament::icon
                    icon="heroicon-o-table-cells"
                    class="mx-auto h-12 w-12 text-gray-400"
                />
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No segment data available</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Segment
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Customers
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Avg Recency
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Avg Frequency
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Avg Monetary
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Total Revenue
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($tableData as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $row['color'] }}-100 text-{{ $row['color'] }}-800 dark:bg-{{ $row['color'] }}-900/30 dark:text-{{ $row['color'] }}-400 mb-1">
                                            {{ $row['segment'] }}
                                        </span>
                                        @if(!empty($row['description']))
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $row['description'] }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm text-gray-900 dark:text-gray-100">
                                    {{ number_format($row['customers']) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm text-gray-900 dark:text-gray-100">
                                    {{ number_format($row['avg_recency'], 1) }} days
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm text-gray-900 dark:text-gray-100">
                                    {{ number_format($row['avg_frequency'], 1) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm text-gray-900 dark:text-gray-100">
                                    ${{ number_format($row['avg_monetary'], 2) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    ${{ number_format($row['total_revenue'], 2) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                    <a
                                        href="/admin/customers?tableFilters[segment][value]={{ urlencode($row['segment']) }}&tableFilters[segment][isActive]=true"
                                        target="_blank"
                                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 shadow-sm text-xs font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                                    >
                                        <x-filament::icon
                                            icon="heroicon-o-user-group"
                                            class="h-4 w-4 mr-1"
                                        />
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
