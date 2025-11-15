<div x-data="{
    calculationInProgress: @entangle('calculationInProgress'),
    calculationComplete: @entangle('calculationComplete'),
    calculationError: @entangle('calculationError')
}" x-init="
    if (!calculationComplete && !calculationInProgress && !calculationError) {
        $wire.call('saveRfmSettings').then(() => {
            $wire.call('startCalculation');
        }).catch(error => {
            console.error('Error during calculation:', error);
        });
    }
">

    {{-- Loading State --}}
    <div x-show="calculationInProgress" class="flex flex-col items-center justify-center py-16 space-y-6">
        <div class="relative">
            <div class="w-24 h-24 border-8 border-gray-200 dark:border-gray-700 rounded-full"></div>
            <div class="absolute top-0 left-0 w-24 h-24 border-8 border-t-primary-600 border-r-transparent border-b-transparent border-l-transparent rounded-full animate-spin"></div>
        </div>
        <div class="text-center space-y-2">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                Calculating RFM Segments...
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                This may take a few moments. Please wait while we analyze your customer data.
            </p>
        </div>
    </div>

    {{-- Error State --}}
    <div x-show="calculationError" class="rounded-lg bg-danger-50 dark:bg-danger-500/10 p-6 border border-danger-200 dark:border-danger-500/20">
        <div class="flex items-start space-x-3">
            <svg class="w-6 h-6 flex-shrink-0 text-danger-600 dark:text-danger-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-danger-800 dark:text-danger-400 mb-1">
                    Calculation Failed
                </h3>
                <p class="text-sm text-danger-700 dark:text-danger-300" x-text="calculationError"></p>
                <button
                    type="button"
                    wire:click="startCalculation"
                    class="mt-4 px-4 py-2 bg-danger-600 hover:bg-danger-700 text-white rounded-lg text-sm font-medium transition-colors"
                >
                    Retry Calculation
                </button>
            </div>
        </div>
    </div>

    {{-- Success State - Simplified --}}
    <div x-show="calculationComplete && !calculationInProgress && !calculationError" class="space-y-6">

        {{-- Success Card --}}
        <div class="rounded-xl bg-gradient-to-br from-success-50 to-success-100 dark:from-success-900/20 dark:to-success-800/30 shadow-lg ring-1 ring-success-200 dark:ring-success-700 p-8">
            <div class="flex flex-col items-center text-center space-y-6">
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-success-100 dark:bg-success-500/20 ring-8 ring-success-50 dark:ring-success-900/30">
                    <div class="text-5xl">✓</div>
                </div>

                <div class="space-y-2">
                    <h2 class="text-2xl font-bold text-success-900 dark:text-success-100">
                        Setup Complete!
                    </h2>
                    <p class="text-success-800 dark:text-success-200 max-w-md">
                        Your RFM analysis has been calculated successfully. {{ collect($this->segmentStats)->sum('customers') }} customers have been segmented into {{ count($this->segmentStats) }} segments.
                    </p>
                </div>

                {{-- Quick Stats --}}
                @if(!empty($this->segmentStats))
                <div class="grid grid-cols-2 gap-4 w-full max-w-md">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ number_format(collect($this->segmentStats)->sum('customers')) }}
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            Total Customers
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            ${{ number_format(collect($this->segmentStats)->sum(fn($s) => $s['customers'] * $s['avg_monetary']), 0) }}
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            Total Revenue
                        </div>
                    </div>
                </div>
                @endif

                {{-- View Results Button --}}
                <div class="pt-4">
                    <a
                        href="{{ route('filament.admin.pages.rfm-dashboard') }}"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-success-600 hover:bg-success-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        View Detailed RFM Dashboard
                    </a>
                </div>
            </div>
        </div>

        {{-- Next Steps --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-500/10">
                        <svg class="block h-5 w-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">
                            View Customers
                        </h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            Browse customers filtered by segment
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-warning-50 dark:bg-warning-500/10">
                        <svg class="block h-5 w-5 text-warning-600 dark:text-warning-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">
                            Analyze Segments
                        </h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            View detailed charts and insights
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-info-50 dark:bg-info-500/10">
                        <svg class="block h-5 w-5 text-info-600 dark:text-info-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">
                            Update Settings
                        </h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            Adjust RFM parameters anytime
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
