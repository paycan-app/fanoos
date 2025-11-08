<?php

namespace App\Filament\Exports;

use App\Models\Customer;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CustomerExporter extends Exporter
{
    protected static ?string $model = Customer::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('first_name'),
            ExportColumn::make('last_name'),
            ExportColumn::make('email'),
            ExportColumn::make('phone'),
            ExportColumn::make('country'),
            ExportColumn::make('state'),
            ExportColumn::make('city'),
            ExportColumn::make('region'),
            ExportColumn::make('birthday'),
            ExportColumn::make('gender'),
            ExportColumn::make('segment'),
            ExportColumn::make('labels')
                ->state(fn (Customer $record) => json_encode($record->labels ?? []))
                ->enabledByDefault(false),
            ExportColumn::make('channel'),
            ExportColumn::make('created_at'),
            ExportColumn::make('recency')
                ->state(fn (Customer $record): ?int => $record->recency),
            ExportColumn::make('frequency')
                ->state(fn (Customer $record): int => $record->frequency),
            ExportColumn::make('monetary')
                ->state(fn (Customer $record): float => $record->monetary),
            ExportColumn::make('meta')
                ->state(fn (Customer $record) => json_encode($record->meta ?? []))
                ->enabledByDefault(false),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your customer export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';
        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }
        return $body;
    }
}