<div class="text-center space-y-3">
    <p class="text-sm text-neutral-600">
        Run segmentation using the current settings, then review stats below.
    </p>
    <x-filament::button
        wire:click="calculateSegments"
        icon="heroicon-o-chart-bar"
        color="primary"
    >
        Calculate Segments
    </x-filament::button>
    <p class="text-xs text-neutral-500">
        Segments are stored on each customer. You can re-run at any time.
    </p>
</div>