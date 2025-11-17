<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('channel')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                TextInput::make('subject'),
                Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('filter_type')
                    ->required()
                    ->default('all'),
                Textarea::make('filter_config')
                    ->columnSpanFull(),
                DateTimePicker::make('scheduled_at'),
                DateTimePicker::make('sent_at'),
                TextInput::make('created_by')
                    ->required()
                    ->numeric(),
                TextInput::make('total_recipients')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_sent')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_failed')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
