<div class="flex gap-3">
    @php
        $scheduleLater = $this->data['schedule_later'] ?? false;
    @endphp

    @if (!$scheduleLater)
    <x-filament::button
        type="button"
        wire:click="launchCampaignNow"
        color="success"
        icon="heroicon-o-paper-airplane"
    >
        Launch Campaign Now
    </x-filament::button>
    @endif

    <x-filament::button
        type="submit"
        wire:click="create"
        wire:loading.attr="disabled"
    >
        {{ $scheduleLater ? 'Schedule Campaign' : 'Save as Draft' }}
    </x-filament::button>

    <x-filament::button
        type="button"
        wire:click="sendTestMessage"
        color="gray"
        icon="heroicon-o-paper-airplane"
        outlined
    >
        Send Test Message
    </x-filament::button>
</div>
