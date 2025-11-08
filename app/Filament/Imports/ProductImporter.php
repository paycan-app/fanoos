<?php

namespace App\Filament\Imports;

use App\Models\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Validation\Rule;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id')
                ->requiredMapping()
                ->rules(['required', Rule::unique('products', 'id')]),
            ImportColumn::make('title')
                ->rules(['required', 'max:255']),
            ImportColumn::make('category')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('subcategory')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('brand')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('sku')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('price')
                ->castStateUsing(function (?string $state): ?float {
                    if (blank($state)) return null;
                    $state = preg_replace('/[^0-9.]/', '', $state);
                    return round((float) $state, 2);
                })
                ->rules(['nullable', 'numeric', 'min:0']),
            ImportColumn::make('meta')
                ->castStateUsing(fn (?string $state) => blank($state) ? null : json_decode($state, true)),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';
        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }
        return $body;
    }

    public function resolveRecord(): ?Product
    {
        return new Product();
    }
}