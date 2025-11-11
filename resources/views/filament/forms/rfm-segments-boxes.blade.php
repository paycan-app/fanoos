@php $current = data_get($this, $modelPath); @endphp
<div class="space-y-2">
    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Segmentation Level</div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        @foreach($options as $value => $opt)
            <button
                type="button"
                class="rounded-lg border px-4 py-3 text-left transition {{ (int) $current === (int) $value ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800' }}"
                wire:click="$set('{{ $modelPath }}', {{ (int) $value }})"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-semibold">{{ $opt['label'] }}</div>
                        @if(!empty($opt['desc']))
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $opt['desc'] }}</div>
                        @endif
                    </div>
                    <x-filament::icon
                        icon="heroicon-o-check-circle"
                        class="h-5 w-5 {{ (int) $current === (int) $value ? 'text-primary-600' : 'text-transparent' }}"
                    />
                </div>
            </button>
        @endforeach
    </div>
</div>