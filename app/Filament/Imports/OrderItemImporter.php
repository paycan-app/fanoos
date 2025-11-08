<?php

namespace App\Filament\Imports;

use App\Models\OrderItem;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class OrderItemImporter extends Importer
{
    protected static ?string $model = OrderItem::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id')
                ->requiredMapping()
                ->rules(['required', Rule::unique('order_items', 'id')]),
            ImportColumn::make('order_id')
                ->requiredMapping()
                ->rules(['required', Rule::exists('orders', 'id')]),
            ImportColumn::make('product_id')
                ->requiredMapping()
                ->rules(['required', Rule::exists('products', 'id')]),
            ImportColumn::make('quantity')
                ->integer()
                ->rules(['nullable', 'integer', 'min:0']),
            ImportColumn::make('unit_price')
                ->castStateUsing(function (?string $state): ?float {
                    if (blank($state)) return null;
                    $state = preg_replace('/[^0-9.]/', '', $state);
                    return round((float) $state, 2);
                })
                ->rules(['nullable', 'numeric', 'min:0']),
            ImportColumn::make('price')
                ->castStateUsing(function (?string $state): ?float {
                    if (blank($state)) return null;
                    $state = preg_replace('/[^0-9.]/', '', $state);
                    return round((float) $state, 2);
                })
                ->rules(['nullable', 'numeric', 'min:0']),
            ImportColumn::make('created_at')
                ->castStateUsing(fn (?string $state) => blank($state) ? null : Carbon::parse($state)->format('Y-m-d H:i:s'))
                ->rules(['nullable', 'date']),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your order items import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';
        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }
        return $body;
    }

    public function resolveRecord(): ?OrderItem
    {
        return new OrderItem();
    }
}