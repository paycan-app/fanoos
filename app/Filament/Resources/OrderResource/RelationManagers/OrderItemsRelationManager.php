<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'orderItems';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')->searchable()->label('Item ID'),
                Tables\Columns\TextColumn::make('product_id')->searchable()->label('Product'),
                Tables\Columns\TextColumn::make('quantity')->numeric(),
                Tables\Columns\TextColumn::make('unit_price')->numeric(2)->label('Unit Price'),
                Tables\Columns\TextColumn::make('price')->numeric(2)->label('Price'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->headerActions([])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
            ]);
    }
}