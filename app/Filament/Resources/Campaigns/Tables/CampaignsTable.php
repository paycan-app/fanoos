<?php

namespace App\Filament\Resources\Campaigns\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->subject),

                TextColumn::make('channel')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'email' => 'info',
                        'sms' => 'success',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'email' => 'heroicon-o-envelope',
                        'sms' => 'heroicon-o-device-phone-mobile',
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'scheduled' => 'warning',
                        'sending' => 'info',
                        'sent' => 'success',
                        'failed' => 'danger',
                    }),

                TextColumn::make('total_recipients')
                    ->label('Recipients')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('total_sent')
                    ->label('Sent')
                    ->numeric()
                    ->sortable()
                    ->color('success'),

                TextColumn::make('open_rate')
                    ->label('Open Rate')
                    ->suffix('%')
                    ->sortable(query: function ($query, string $direction) {
                        return $query; // Can't sort on accessor
                    })
                    ->visible(fn () => true),

                TextColumn::make('scheduled_at')
                    ->label('Scheduled')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->options([
                        'email' => 'Email',
                        'sms' => 'SMS',
                    ]),

                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'scheduled' => 'Scheduled',
                        'sending' => 'Sending',
                        'sent' => 'Sent',
                        'failed' => 'Failed',
                    ])
                    ->multiple(),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
