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
                    class="mx-auto block h-12 w-12 text-gray-400"
                />
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No segment data available</p>
            </div>
        @else
            <div class="fi-ta-ctn overflow-x-auto">
                <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 dark:divide-white/5">
                    <thead class="fi-ta-header divide-y divide-gray-200 dark:divide-white/5">
                        <tr class="bg-gray-50/50 dark:bg-white/5">
                            <th class="fi-ta-header-cell px-3 py-3.5 sm:first:pl-6 sm:last:pr-6 text-start">
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Segment
                                </span>
                            </th>
                            <th class="fi-ta-header-cell px-3 py-3.5 sm:last:pr-6 text-end">
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Customers
                                </span>
                            </th>
                            <th class="fi-ta-header-cell px-3 py-3.5 sm:last:pr-6 text-end">
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Avg Recency
                                </span>
                            </th>
                            <th class="fi-ta-header-cell px-3 py-3.5 sm:last:pr-6 text-end">
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Avg Frequency
                                </span>
                            </th>
                            <th class="fi-ta-header-cell px-3 py-3.5 sm:last:pr-6 text-end">
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Avg Monetary
                                </span>
                            </th>
                            <th class="fi-ta-header-cell px-3 py-3.5 sm:last:pr-6 text-end">
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Total Revenue
                                </span>
                            </th>
                            <th class="fi-ta-header-cell px-3 py-3.5 sm:last:pr-6 text-end">
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Actions
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="fi-ta-body divide-y divide-gray-200 dark:divide-white/5">
                        @foreach($tableData as $row)
                            <tr class="fi-ta-row [@media(hover:hover)]:transition [@media(hover:hover)]:duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="fi-ta-cell p-0 first:pl-3 last:pr-3 sm:first:pl-6 sm:last:pr-6">
                                    <div class="px-3 py-4">
                                        <div class="flex flex-col gap-1">
                                            <x-filament::badge :color="$row['color']" size="sm">
                                                {{ $row['segment'] }}
                                            </x-filament::badge>
                                            @if(!empty($row['description']))
                                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $row['description'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="fi-ta-cell p-0 first:pl-3 last:pr-3 sm:last:pr-6">
                                    <div class="flex justify-end px-3 py-4">
                                        <span class="text-sm text-gray-950 dark:text-white">
                                            {{ number_format($row['customers']) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="fi-ta-cell p-0 first:pl-3 last:pr-3 sm:last:pr-6">
                                    <div class="flex justify-end px-3 py-4">
                                        <span class="text-sm text-gray-950 dark:text-white">
                                            {{ number_format($row['avg_recency'], 1) }} days
                                        </span>
                                    </div>
                                </td>
                                <td class="fi-ta-cell p-0 first:pl-3 last:pr-3 sm:last:pr-6">
                                    <div class="flex justify-end px-3 py-4">
                                        <span class="text-sm text-gray-950 dark:text-white">
                                            {{ number_format($row['avg_frequency'], 1) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="fi-ta-cell p-0 first:pl-3 last:pr-3 sm:last:pr-6">
                                    <div class="flex justify-end px-3 py-4">
                                        <span class="text-sm text-gray-950 dark:text-white">
                                            ${{ number_format($row['avg_monetary'], 2) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="fi-ta-cell p-0 first:pl-3 last:pr-3 sm:last:pr-6">
                                    <div class="flex justify-end px-3 py-4">
                                        <span class="text-sm text-gray-950 dark:text-white font-semibold">
                                            ${{ number_format($row['total_revenue'], 2) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="fi-ta-cell p-0 first:pl-3 last:pr-3 sm:last:pr-6">
                                    <div class="flex justify-end px-3 py-4">
                                        <a
                                            href="/admin/customers?tableFilters[segment][value]={{ urlencode($row['segment']) }}&tableFilters[segment][isActive]=true"
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-300 dark:border-gray-600 shadow-sm text-xs font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition"
                                        >
                                            <x-filament::icon
                                                icon="heroicon-o-user-group"
                                                class="block h-4 w-4"
                                            />
                                            View
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
