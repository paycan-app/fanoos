@php
    $presetColors = [
        'saved_timeframe' => 'border-amber-300 bg-amber-50 text-amber-900 hover:bg-amber-100 dark:border-amber-400/50 dark:bg-amber-500/20 dark:text-amber-100',
        'last_quarter' => 'border-emerald-300 bg-emerald-50 text-emerald-900 hover:bg-emerald-100 dark:border-emerald-400/40 dark:bg-emerald-500/15 dark:text-emerald-100',
        'half_year' => 'border-sky-300 bg-sky-50 text-sky-900 hover:bg-sky-100 dark:border-sky-400/40 dark:bg-sky-500/15 dark:text-sky-100',
        'full_year' => 'border-indigo-300 bg-indigo-50 text-indigo-900 hover:bg-indigo-100 dark:border-indigo-400/40 dark:bg-indigo-500/15 dark:text-indigo-100',
    ];
@endphp

<div class="space-y-2">
    <p class="text-sm font-semibold text-neutral-800 dark:text-neutral-100">Quick period suggestions</p>
    <div class="flex flex-wrap gap-2">
        @foreach($presets as $preset)
            @php($colorClass = $presetColors[$preset['key']] ?? 'border-primary-300 bg-primary-50 text-primary-900 dark:border-primary-400/40 dark:bg-primary-500/15 dark:text-primary-100')
            <button
                type="button"
                wire:click="applyPreset('{{ $preset['key'] }}')"
                wire:key="segment-preset-{{ $preset['key'] }}"
                class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-400 {{ $colorClass }}"
            >
                {{ $preset['label'] }}
            </button>
        @endforeach
    </div>
    <p class="text-xs text-neutral-500 dark:text-neutral-300">
        @php($first = $presets[0]['description'] ?? null)
        {{ $first ?? 'Pick a chip to set both dates automatically.' }}
    </p>
</div>

