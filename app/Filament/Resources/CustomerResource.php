<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers\OrdersRelationManager;
use App\Models\Customer;
use BackedEnum;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|UnitEnum|null $navigationGroup = 'Data';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationLabel = 'Customers';

    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('first_name')->searchable(),
                Tables\Columns\TextColumn::make('last_name')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('email')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('phone')->toggleable(),
                Tables\Columns\TextColumn::make('segment')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? static::getSegmentBadgeColor($state) : 'gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('segment')
                    ->label('Segment')
                    ->options(fn () => \App\Models\Customer::query()
                        ->whereNotNull('segment')
                        ->distinct()
                        ->orderBy('segment')
                        ->pluck('segment', 'segment')
                        ->all()),
                SelectFilter::make('gender')
                    ->options(fn () => \App\Models\Customer::query()
                        ->whereNotNull('gender')
                        ->distinct()
                        ->orderBy('gender')
                        ->pluck('gender', 'gender')
                        ->all()),
                SelectFilter::make('channel')
                    ->options(fn () => \App\Models\Customer::query()
                        ->whereNotNull('channel')
                        ->distinct()
                        ->orderBy('channel')
                        ->pluck('channel', 'channel')
                        ->all()),
                SelectFilter::make('country')
                    ->options(fn () => \App\Models\Customer::query()
                        ->whereNotNull('country')
                        ->distinct()
                        ->orderBy('country')
                        ->pluck('country', 'country')
                        ->all()),
                SelectFilter::make('region')
                    ->options(fn () => \App\Models\Customer::query()
                        ->whereNotNull('region')
                        ->distinct()
                        ->orderBy('region')
                        ->pluck('region', 'region')
                        ->all()),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')->label('Created from'),
                        DatePicker::make('until')->label('Created until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (! empty($data['from'])) {
                            $indicators[] = 'From '.\Carbon\Carbon::parse($data['from'])->toFormattedDateString();
                        }
                        if (! empty($data['until'])) {
                            $indicators[] = 'Until '.\Carbon\Carbon::parse($data['until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
                Filter::make('birthday')
                    ->form([
                        DatePicker::make('from')->label('Birthday from'),
                        DatePicker::make('until')->label('Birthday until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('birthday', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('birthday', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (! empty($data['from'])) {
                            $indicators[] = 'From '.\Carbon\Carbon::parse($data['from'])->toFormattedDateString();
                        }
                        if (! empty($data['until'])) {
                            $indicators[] = 'Until '.\Carbon\Carbon::parse($data['until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ])
            ->headerActions([
                ImportAction::make()
                    ->importer(\App\Filament\Imports\CustomerImporter::class),
                ExportAction::make()
                    ->exporter(\App\Filament\Exports\CustomerExporter::class),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Grid::make(2)->schema([
                TextEntry::make('id')->label('Customer ID'),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('first_name'),
                TextEntry::make('last_name'),
                TextEntry::make('email'),
                TextEntry::make('phone'),
                TextEntry::make('country'),
                TextEntry::make('state'),
                TextEntry::make('city'),
                TextEntry::make('region'),
                TextEntry::make('birthday')->date(),
                TextEntry::make('gender'),
                TextEntry::make('segment')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? static::getSegmentBadgeColor($state) : 'gray'),
                TextEntry::make('channel'),
                TextEntry::make('recency')->label('Recency (days)'),
                TextEntry::make('frequency')->label('Orders Count'),
                TextEntry::make('monetary')->label('Total Spend')->numeric(2),
            ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'view' => Pages\ViewCustomer::route('/{record}'),
        ];
    }

    protected static function getSegmentBadgeColor(string $segment): string
    {
        return match ($segment) {
            'Champions' => 'warning',
            'Loyal Customers' => 'success',
            'Potential Loyalist' => 'primary',
            'New Customers' => 'purple',
            'Promising' => 'info',
            'Need Attention' => 'warning',
            'About To Sleep' => 'warning',
            'At Risk' => 'danger',
            'Cannot Lose Them' => 'danger',
            'Hibernating' => 'danger',
            'Lost' => 'danger',
            'High Value' => 'success',
            'Medium Value' => 'primary',
            'Low Value' => 'warning',
            default => 'gray',
        };
    }
}
