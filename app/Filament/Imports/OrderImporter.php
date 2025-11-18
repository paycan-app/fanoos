<?php

namespace App\Filament\Imports;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Validation\Rule;

class OrderImporter extends Importer
{
    protected static ?string $model = Order::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id')
                ->requiredMapping()
                ->rules(['required', Rule::unique('orders', 'id')]),
            ImportColumn::make('customer_id')
                ->rules(['nullable', Rule::exists('customers', 'id')]),
            ImportColumn::make('created_at')
                ->castStateUsing(fn (?string $state) => blank($state) ? null : Carbon::parse($state)->format('Y-m-d H:i:s'))
                ->rules(['nullable', 'date']),
            ImportColumn::make('total_amount')
                ->castStateUsing(function (?string $state): ?float {
                    if (blank($state)) {
                        return null;
                    }
                    $state = preg_replace('/[^0-9.]/', '', $state);

                    return round((float) $state, 2);
                })
                ->rules(['nullable', 'numeric', 'min:0']),
            ImportColumn::make('status')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('meta')
                ->castStateUsing(fn (?string $state) => blank($state) ? null : json_decode($state, true)),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your order import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';
        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }

    public function resolveRecord(): ?Order
    {
        return new Order;
    }
}
