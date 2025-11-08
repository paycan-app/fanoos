<div class="text-center space-y-4">
    <p class="text-sm text-neutral-600">
        Start by importing your CSV files. Use the buttons below to open the import interfaces for Orders and Order Items.
    </p>
    <div class="flex justify-center gap-3">
        <x-filament::button
            tag="a"
            href="{{ route('filament.admin.resources.orders.index') }}"
            icon="heroicon-o-arrow-up-tray"
        >
            Import Orders CSV
        </x-filament::button>

        <x-filament::button
            tag="a"
            href="{{ route('filament.admin.resources.order-items.index') }}"
            icon="heroicon-o-arrow-up-tray"
            color="gray"
        >
            Import Order Items CSV
        </x-filament::button>
    </div>
    <p class="text-xs text-neutral-500">
        Tip: Map columns to fields, validate rows, then return here and click Next.
    </p>
</div>