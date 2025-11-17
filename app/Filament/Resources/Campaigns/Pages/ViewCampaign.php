<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Filament\Resources\Campaigns\CampaignResource;
use App\Services\CampaignService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ViewCampaign extends ViewRecord
{
    protected static string $resource = CampaignResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Campaign Details')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Campaign Name'),

                                TextEntry::make('channel')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'email' => 'info',
                                        'sms' => 'success',
                                    }),

                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'draft' => 'gray',
                                        'scheduled' => 'warning',
                                        'sending' => 'info',
                                        'sent' => 'success',
                                        'failed' => 'danger',
                                    }),
                            ]),

                        TextEntry::make('subject')
                            ->visible(fn ($record) => $record->channel === 'email'),

                        TextEntry::make('content')
                            ->label('Message Content')
                            ->html()
                            ->columnSpanFull(),
                    ]),

                Section::make('Campaign Statistics')
                    ->visible(fn ($record) => $record->status === 'sent' || $record->status === 'sending')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('total_recipients')
                                    ->label('Total Recipients')
                                    ->numeric(),

                                TextEntry::make('total_sent')
                                    ->label('Sent')
                                    ->numeric()
                                    ->color('success'),

                                TextEntry::make('open_rate')
                                    ->suffix('%')
                                    ->label('Open Rate'),

                                TextEntry::make('click_rate')
                                    ->suffix('%')
                                    ->label('Click Rate'),
                            ]),
                    ]),

                Section::make('Recipients')
                    ->schema([
                        TextEntry::make('filter_type')
                            ->label('Filter Type')
                            ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),

                        TextEntry::make('filter_config')
                            ->label('Filter Details')
                            ->formatStateUsing(function ($state, $record) {
                                if ($record->filter_type === 'all') {
                                    return 'All customers';
                                }

                                if ($record->filter_type === 'segment' && isset($state['segments'])) {
                                    return implode(', ', $state['segments']);
                                }

                                if ($record->filter_type === 'individual' && isset($state['customer_ids'])) {
                                    return 'Individual customer';
                                }

                                return json_encode($state);
                            }),
                    ]),

                Section::make('Timing')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('scheduled_at')
                                    ->dateTime()
                                    ->placeholder('—'),

                                TextEntry::make('sent_at')
                                    ->dateTime()
                                    ->placeholder('—'),

                                TextEntry::make('created_at')
                                    ->dateTime(),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send')
                ->label('Send Campaign')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->visible(fn ($record) => $record->status === 'draft' && ! $record->scheduled_at)
                ->requiresConfirmation()
                ->modalHeading('Send Campaign Now')
                ->modalDescription(fn ($record) => 'This will send the campaign to '.$record->getFilteredCustomers()->count().' recipients immediately.')
                ->action(function ($record) {
                    $campaignService = app(CampaignService::class);
                    $campaignService->processCampaign($record);

                    Notification::make()
                        ->success()
                        ->title('Campaign queued for sending')
                        ->body('The campaign is now being sent to recipients.')
                        ->send();
                })
                ->color('success'),

            EditAction::make()
                ->visible(fn ($record) => $record->status === 'draft'),
        ];
    }
}
