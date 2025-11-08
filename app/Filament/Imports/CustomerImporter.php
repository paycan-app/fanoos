<?php

namespace App\Filament\Imports;

use App\Models\Customer;
use Carbon\Carbon;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Validation\Rule;

class CustomerImporter extends Importer
{
    protected static ?string $model = Customer::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id')
                ->requiredMapping()
                ->rules(['required', Rule::unique('customers', 'id')]),
            ImportColumn::make('first_name')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('last_name')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('email')
                ->rules(['nullable', 'email', 'max:255']),
            ImportColumn::make('phone')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('country')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('state')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('city')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('region')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('birthday')
                ->castStateUsing(fn (?string $state) => blank($state) ? null : Carbon::parse($state)->format('Y-m-d'))
                ->rules(['nullable', 'date']),
            ImportColumn::make('gender')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('segment')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('labels')
                ->multiple(','),
            ImportColumn::make('channel')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('meta')
                ->castStateUsing(fn (?string $state) => blank($state) ? null : json_decode($state, true)),
            ImportColumn::make('created_at')
                ->castStateUsing(fn (?string $state) => blank($state) ? null : Carbon::parse($state)->format('Y-m-d H:i:s'))
                ->rules(['nullable', 'date']),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your customer import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';
        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }
        return $body;
    }

    public function resolveRecord(): ?Customer
    {
        return new Customer();
    }
}