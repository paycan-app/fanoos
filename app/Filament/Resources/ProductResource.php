<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\OrderItemsRelationManager;
use App\Models\Product;
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
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource {
    protected static ?string $model = Product::class;
    protected static string | UnitEnum | null $navigationGroup = 'Data';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Products';
    protected static ?int $navigationSort = 30;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('category')->toggleable(),
                Tables\Columns\TextColumn::make('subcategory')->toggleable(),
                Tables\Columns\TextColumn::make('brand')->toggleable(),
                Tables\Columns\TextColumn::make('sku')->toggleable(),
                Tables\Columns\TextColumn::make('price')->numeric(2)->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(fn () => \App\Models\Product::query()
                        ->whereNotNull('category')
                        ->distinct()
                        ->orderBy('category')
                        ->pluck('category', 'category')
                        ->all()),
                SelectFilter::make('subcategory')
                    ->options(fn () => \App\Models\Product::query()
                        ->whereNotNull('subcategory')
                        ->distinct()
                        ->orderBy('subcategory')
                        ->pluck('subcategory', 'subcategory')
                        ->all()),
                SelectFilter::make('brand')
                    ->options(fn () => \App\Models\Product::query()
                        ->whereNotNull('brand')
                        ->distinct()
                        ->orderBy('brand')
                        ->pluck('brand', 'brand')
                        ->all()),
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
                    ->importer(\App\Filament\Imports\ProductImporter::class),
                ExportAction::make()
                    ->exporter(\App\Filament\Exports\ProductExporter::class),
            ])
            ->defaultSort('title')
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Grid::make(2)->schema([
                TextEntry::make('id'),
                TextEntry::make('title'),
                TextEntry::make('category'),
                TextEntry::make('subcategory'),
                TextEntry::make('brand'),
                TextEntry::make('sku'),
                TextEntry::make('price')->numeric(2),
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
            'index' => Pages\ListProducts::route('/'),
            'view' => Pages\ViewProduct::route('/{record}'),
        ];
    }
}