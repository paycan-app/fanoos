<div
    class="fi-section text-center space-y-3"
    x-data="{ calculated: @entangle('segmentStats').live }"
    x-init="
        // Auto-calculate when this view loads if not already calculated
        $nextTick(() => {
            if (!calculated || calculated.length === 0) {
                $wire.calculateSegments();
            }
        });
    "
>
    <div x-show="!calculated || calculated.length === 0" class="space-y-3">
        <div class="flex justify-center">
            <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
        <p class="fi-section-heading text-sm text-gray-600 dark:text-gray-400">
            Calculating customer segments...
        </p>
        <p class="fi-section-description text-xs text-gray-500 dark:text-gray-400">
            This may take a moment depending on your customer count.
        </p>
    </div>

    <div x-show="calculated && calculated.length > 0" class="space-y-2">
        <div class="flex justify-center">
            <div class="h-12 w-12 rounded-full bg-success-100 dark:bg-success-900/30 flex items-center justify-center">
                <x-filament::icon
                    alias="heroicon-o-check-circle"
                    icon="heroicon-o-check-circle"
                    class="h-6 w-6 text-success-600 dark:text-success-400"
                />
            </div>
        </div>
        <p class="fi-section-heading text-sm font-medium text-gray-900 dark:text-gray-100">
            Segments Calculated Successfully!
        </p>
        <p class="fi-section-description text-xs text-gray-500 dark:text-gray-400">
            Analysis complete. Review results below.
        </p>
        <x-filament::button
            type="button"
            icon="heroicon-o-arrow-path"
            wire:click="calculateSegments"
            size="sm"
            color="gray"
            outlined
        >
            Recalculate
        </x-filament::button>
    </div>
</div>
