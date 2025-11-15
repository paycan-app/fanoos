<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="fi-section-header flex flex-col gap-3 px-6 py-4">
        <div>
            <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                Segment Details & Performance
            </h3>
            <p class="fi-section-header-description text-sm text-gray-500 dark:text-gray-400 mt-1">
                Comprehensive breakdown of each customer segment with key metrics
            </p>
        </div>
    </div>

    <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
        <div class="fi-section-content p-6">
            @php
                $tableData = $this->getTableData();
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
                                        % of Total
                                    </span>
                                </th>
                                <th class="fi-ta-header-cell px-3 py-3.5 sm:last:pr-6 text-end">
                                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                        Total Revenue
                                    </span>
                                </th>
                                <th class="fi-ta-header-cell px-3 py-3.5 sm:last:pr-6 text-end">
                                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                        Avg Spend
                                    </span>
                                </th>
                                <th class="fi-ta-header-cell px-3 py-3.5 sm:last:pr-6 text-end">
                                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                        Avg Orders
                                    </span>
                                </th>
                                <th class="fi-ta-header-cell px-3 py-3.5 sm:last:pr-6 text-end">
                                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                        Avg Recency
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="fi-ta-body divide-y divide-gray-200 dark:divide-white/5">
                            @foreach($tableData as $row)
                            <tr class="fi-ta-row [@media(hover:hover)]:transition [@media(hover:hover)]:duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="fi-ta-cell p-0 first:pl-3 last:pr-3 sm:first:pl-6 sm:last:pr-6">
                                    <div class="px-3 py-4">
                                        <x-filament::badge :color="$row['color']" size="sm">
                                            {{ $row['segment'] }}
                                        </x-filament::badge>
                                    </div>
                                </td>
                                <td class="fi-ta-cell p-0 first:pl-3 last:pr-3 sm:last:pr-6">
                                    <div class="flex justify-end px-3 py-4">
                                        <span class="text-sm text-gray-950 dark:text-white font-medium">
                                            {{ number_format($row['customers']) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="fi-ta-cell p-0 first:pl-3 last:pr-3 sm:last:pr-6">
                                    <div class="flex justify-end px-3 py-4">
                                        <span class="text-sm text-gray-950 dark:text-white">
                                            {{ number_format($row['percentage'], 1) }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="fi-ta-cell p-0 first:pl-3 last:pr-3 sm:last:pr-6">
                                    <div class="flex justify-end px-3 py-4">
                                        <span class="text-sm text-gray-950 dark:text-white font-semibold">
                                            ${{ number_format($row['total_revenue'], 0) }}
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
                                        <span class="text-sm text-gray-950 dark:text-white">
                                            {{ number_format($row['avg_frequency'], 1) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="fi-ta-cell p-0 first:pl-3 last:pr-3 sm:last:pr-6">
                                    <div class="flex justify-end px-3 py-4">
                                        <span class="text-sm text-gray-950 dark:text-white">
                                            {{ number_format($row['avg_recency'], 0) }}d
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="fi-ta-footer divide-y divide-gray-200 dark:divide-white/5">
                            <tr class="bg-gray-50/50 dark:bg-white/5">
                                <td class="fi-ta-cell p-0 first:pl-3 last:pr-3 sm:first:pl-6">
                                    <div class="px-3 py-3.5">
                                        <span class="text-sm font-semibold text-gray-950 dark:text-white">Total</span>
                                    </div>
                                </td>
                                <td class="fi-ta-cell p-0 first:pl-3 last:pr-3 sm:last:pr-6">
                                    <div class="flex justify-end px-3 py-3.5">
                                        <span class="text-sm font-semibold text-gray-950 dark:text-white">
                                            {{ number_format(collect($tableData)->sum('customers')) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="fi-ta-cell p-0 first:pl-3 last:pr-3 sm:last:pr-6">
                                    <div class="flex justify-end px-3 py-3.5">
                                        <span class="text-sm font-semibold text-gray-950 dark:text-white">100%</span>
                                    </div>
                                </td>
                                <td class="fi-ta-cell p-0 first:pl-3 last:pr-3 sm:last:pr-6">
                                    <div class="flex justify-end px-3 py-3.5">
                                        <span class="text-sm font-semibold text-gray-950 dark:text-white">
                                            ${{ number_format(collect($tableData)->sum('total_revenue'), 0) }}
                                        </span>
                                    </div>
                                </td>
                                <td colspan="3" class="fi-ta-cell p-0 first:pl-3 last:pr-3 sm:last:pr-6"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
