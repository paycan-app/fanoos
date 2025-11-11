<div class="text-center space-y-3">
    @if(!$this->showAdvanced)
        <x-filament::button
            icon="heroicon-o-cog-6-tooth"
            color="gray"
            wire:click="$set('showAdvanced', true)"
        >
            Advanced Settings
        </x-filament::button>
        <p class="text-xs text-neutral-500">
            Enable segmentation, segment count, bins, and analysis date.
        </p>
    @else
        <x-filament::button
            icon="heroicon-o-chevron-up"
            color="gray"
            wire:click="$set('showAdvanced', false)"
        >
            Hide Advanced Settings
        </x-filament::button>
    @endif
</div>