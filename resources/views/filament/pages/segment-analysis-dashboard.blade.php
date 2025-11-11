<x-filament-panels::page>
    <x-filament-panels::header
        :actions="$this->getHeaderActions()"
    />

    {{-- Statistics Overview --}}
    <x-filament::section>
        <x-filament-widgets::stats-overview-widget
            :stats="$this->getStats()"
            :columns="6"
        />
    </x-filament::section>

    {{-- Segment Distribution --}}
    <x-filament::section>
        <x-slot name="heading">
            Segment Distribution
        </x-slot>
        
        <x-slot name="description">
            Customer distribution across RFM segments
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Segment Cards --}}
            <div class="space-y-4">
                @foreach($this->getSegmentDistribution() as $segment)
                    <x-filament::card>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <x-filament::badge :color="$segment['color']">
                                    {{ $segment['segment'] }}
                                </x-filament::badge>
                                <span class="text-sm text-gray-500">
                                    {{ $segment['customers'] }} customers
                                </span>
                            </div>
                            <span class="text-sm font-medium text-gray-900">
                                {{ $segment['percentage'] }}%
                            </span>
                        </div>
                        
                        <div class="mt-3 grid grid-cols-3 gap-2 text-xs text-gray-500">
                            <div>
                                <div class="font-medium">Avg Value</div>
                                <div>${{ number_format($segment['avg_monetary'], 2) }}</div>
                            </div>
                            <div>
                                <div class="font-medium">Avg Orders</div>
                                <div>{{ number_format($segment['avg_frequency'], 1) }}</div>
                            </div>
                            <div>
                                <div class="font-medium">Avg Recency</div>
                                <div>{{ number_format($segment['avg_recency'], 1) }} days</div>
                            </div>
                        </div>
                    </x-filament::card>
                @endforeach
            </div>

            {{-- Summary Chart Placeholder --}}
            <div>
                <x-filament::card>
                    <div class="text-center py-12">
                        <x-filament::icon
                            icon="heroicon-o-chart-pie"
                            class="w-12 h-12 mx-auto text-gray-400 mb-4"
                        />
                        <h3 class="text-lg font-medium text-gray-900">Segment Visualization</h3>
                        <p class="text-sm text-gray-500 mt-2">
                            Pie chart visualization will be displayed here
                        </p>
                    </div>
                </x-filament::card>
            </div>
        </div>
    </x-filament::section>

    {{-- Actionable Insights --}}
    <x-filament::section>
        <x-slot name="heading">
            Actionable Insights
        </x-slot>
        
        <x-slot name="description">
            Key findings from RFM analysis
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <x-filament::card>
                <div class="flex items-center space-x-3">
                    <x-filament::icon
                        icon="heroicon-o-sparkles"
                        class="w-8 h-8 text-amber-500"
                    />
                    <div>
                        <h4 class="font-medium text-gray-900">High-Value Segments</h4>
                        <p class="text-sm text-gray-500">
                            Identify and nurture your most valuable customers
                        </p>
                    </div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="flex items-center space-x-3">
                    <x-filament::icon
                        icon="heroicon-o-exclamation-triangle"
                        class="w-8 h-8 text-red-500"
                    />
                    <div>
                        <h4 class="font-medium text-gray-900">At-Risk Customers</h4>
                        <p class="text-sm text-gray-500">
                            Re-engage customers showing signs of churn
                        </p>
                    </div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="flex items-center space-x-3">
                    <x-filament::icon
                        icon="heroicon-o-light-bulb"
                        class="w-8 h-8 text-blue-500"
                    />
                    <div>
                        <h4 class="font-medium text-gray-900">Growth Opportunities</h4>
                        <p class="text-sm text-gray-500">
                            Target segments with potential for growth
                        </p>
                    </div>
                </div>
            </x-filament::card>
        </div>
    </x-filament::section>
</x-filament-panels::page>