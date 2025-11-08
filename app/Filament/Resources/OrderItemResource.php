<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderItemResource\Pages;
use App\Models\OrderItem;
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

class OrderItemResource extends Resource {
    protected static ?string $model = OrderItem::class;
    protected static string | UnitEnum | null $navigationGroup = 'Data';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Order Items';
    protected static ?int $navigationSort = 40;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->searchable()->label('Item ID')->toggleable(),
                Tables\Columns\TextColumn::make('order_id')->searchable()->label('Order')->toggleable(),
                Tables\Columns\TextColumn::make('product_id')->searchable()->label('Product')->toggleable(),
                Tables\Columns\TextColumn::make('quantity')->numeric()->toggleable(),
                Tables\Columns\TextColumn::make('unit_price')->numeric(2)->label('Unit Price')->toggleable(),
                Tables\Columns\TextColumn::make('price')->numeric(2)->label('Price')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('order')
                    ->relationship('order', 'id')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('product')
                    ->relationship('product', 'title')
                    ->searchable()
                    ->preload(),
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
                        if (!empty($data['from'])) {
                            $indicators[] = 'From ' . \Carbon\Carbon::parse($data['from'])->toFormattedDateString();
                        }
                        if (!empty($data['until'])) {
                            $indicators[] = 'Until ' . \Carbon\Carbon::parse($data['until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
                Filter::make('quantity')
                    ->form([
                        TextInput::make('min')->label('Min')->numeric(),
                        TextInput::make('max')->label('Max')->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['min'] ?? null, fn (Builder $q, $min) => $q->where('quantity', '>=', $min))
                            ->when($data['max'] ?? null, fn (Builder $q, $max) => $q->where('quantity', '<=', $max));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (!empty($data['min'])) {
                            $indicators[] = 'Min ' . number_format((float) $data['min'], 0);
                        }
                        if (!empty($data['max'])) {
                            $indicators[] = 'Max ' . number_format((float) $data['max'], 0);
                        }
                        return $indicators;
                    }),
                Filter::make('price')
                    ->form([
                        TextInput::make('min')->label('Min')->numeric(),
                        TextInput::make('max')->label('Max')->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['min'] ?? null, fn (Builder $q, $min) => $q->where('price', '>=', $min))
                            ->when($data['max'] ?? null, fn (Builder $q, $max) => $q->where('price', '<=', $max));
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
                    ->importer(\App\Filament\Imports\OrderItemImporter::class),
                ExportAction::make()
                    ->exporter(\App\Filament\Exports\OrderItemExporter::class),
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
                TextEntry::make('id')->label('Item ID'),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('order.id')
                    ->label('Order ID')
                    ->url(fn ($record) => route('filament.admin.resources.orders.view', $record->order_id))
                    ->openUrlInNewTab(),
                TextEntry::make('product.id')
                    ->label('Product ID')
                    ->url(fn ($record) => route('filament.admin.resources.products.view', $record->product_id))
                    ->openUrlInNewTab(),
                TextEntry::make('quantity')->numeric(),
                TextEntry::make('unit_price')->numeric(2)->label('Unit Price'),
                TextEntry::make('price')->numeric(2)->label('Price'),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrderItems::route('/'),
            'view' => Pages\ViewOrderItem::route('/{record}'),
        ];
    }
}