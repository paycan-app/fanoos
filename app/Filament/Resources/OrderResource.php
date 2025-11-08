<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\OrderItemsRelationManager;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use UnitEnum;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource {
    protected static ?string $model = Order::class;
    protected static string | UnitEnum | null $navigationGroup = 'Data';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-receipt-refund';
    protected static ?string $navigationLabel = 'Orders';
    protected static ?int $navigationSort = 20;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('customer_id')->label('Customer ID')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->toggleable(),
                Tables\Columns\TextColumn::make('total_amount')->numeric(2)->label('Amount')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(fn () => \App\Models\Order::query()
                        ->whereNotNull('status')
                        ->distinct()
                        ->orderBy('status')
                        ->pluck('status', 'status')
                        ->all()),
                Tables\Filters\SelectFilter::make('customer')
                    ->relationship('customer', 'email')
                    ->searchable()
                    ->preload(),
                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Created from'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Created until'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (!empty($data['from'])) {
                            $indicators[] = 'From ' . \Carbon\Carbon::parse($data['from'])->toFormattedDateString();
                        }
                        if (!empty($data['until'])) {
                            $indicators[] = 'Until ' . \Carbon\Carbon::parse($data['until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
                Filter::make('total_amount')
                    ->form([
                        TextInput::make('min')->label('Min')->numeric(),
                        TextInput::make('max')->label('Max')->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['min'] ?? null, fn (Builder $q, $min) => $q->where('total_amount', '>=', $min))
                            ->when($data['max'] ?? null, fn (Builder $q, $max) => $q->where('total_amount', '<=', $max));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (!empty($data['min'])) {
                            $indicators[] = 'Min ' . number_format((float) $data['min'], 2);
                        }
                        if (!empty($data['max'])) {
                            $indicators[] = 'Max ' . number_format((float) $data['max'], 2);
                        }
                        return $indicators;
                    }),
            ])
            ->headerActions([
                ImportAction::make()
                    ->importer(\App\Filament\Imports\OrderImporter::class),
                ExportAction::make()
                    ->exporter(\App\Filament\Exports\OrderExporter::class),
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
                TextEntry::make('id')->label('Order ID'),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('status'),
                TextEntry::make('total_amount')->numeric(2)->label('Amount'),
                TextEntry::make('customer.id')
                    ->label('Customer ID')
                    ->url(fn ($record) => route('filament.admin.resources.customers.view', $record->customer_id))
                    ->openUrlInNewTab(),
                TextEntry::make('customer.first_name')->label('Customer First Name'),
                TextEntry::make('customer.email')->label('Customer Email'),
            ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            OrderItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}