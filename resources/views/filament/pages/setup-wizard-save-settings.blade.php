<div class="text-center space-y-3">
    <p class="text-sm text-neutral-600">
        Save your RFM configuration so it’s applied during segmentation.
    </p>
    <x-filament::button
        wire:click="saveRfmSettings"
        icon="heroicon-o-check-circle"
        color="success"
    >
        Save RFM Settings
    </x-filament::button>
    <p class="text-xs text-neutral-500">
        RFM bins set quantile splits for Recency, Frequency, and Monetary scores.
    </p>
</div>