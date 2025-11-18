@php
    $brandName = \Filament\Facades\Filament::getBrandName();
@endphp

<div class="flex items-center gap-3">
    <img 
        src="{{ asset('img/fanoos-logo.png') }}" 
        alt="Fanoos Logo" 
        style="height: 3rem;"
        class="object-contain"
    />
    <span class="text-xl font-semibold">{{ $brandName }}</span>
</div>

