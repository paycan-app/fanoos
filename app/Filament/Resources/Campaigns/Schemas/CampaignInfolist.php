<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CampaignInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('ID'),
                TextEntry::make('name'),
                TextEntry::make('channel'),
                TextEntry::make('status'),
                TextEntry::make('subject')
                    ->placeholder('-'),
                TextEntry::make('content')
                    ->columnSpanFull(),
                TextEntry::make('filter_type'),
                TextEntry::make('filter_config')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('scheduled_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('sent_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_by')
                    ->numeric(),
                TextEntry::make('total_recipients')
                    ->numeric(),
                TextEntry::make('total_sent')
                    ->numeric(),
                TextEntry::make('total_failed')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
