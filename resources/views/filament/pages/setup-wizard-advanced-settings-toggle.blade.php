<div class=" space-y-3">
    @if(!$this->showAdvanced)
        <x-filament::button
            icon="heroicon-o-cog-6-tooth"
            color="gray"
            wire:click="$set('showAdvanced', true)"
        >
            Advanced Settings
        </x-filament::button>
        
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