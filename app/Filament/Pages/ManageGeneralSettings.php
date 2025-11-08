<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageGeneralSettings extends SettingsPage
{
    protected static string $settings = GeneralSettings::class;

    protected static UnitEnum|string|null $navigationGroup = 'Settings';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;
    protected static ?string $navigationLabel = 'General';
    protected static ?string $title = 'General Settings';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('site_name')
                ->label('Site Name')
                ->required()
                ->maxLength(255),

            TextInput::make('contact_email')
                ->label('Contact Email')
                ->email()
                ->required(),

            Toggle::make('maintenance_mode')
                ->label('Maintenance Mode'),
            Toggle::make('rfm_enable')
                ->label('Enable RFM Segmentation'),
            TextInput::make('rfm_bins')
                ->label('RFM Bins (Quantiles)')
                ->numeric()
                ->minValue(2)
                ->maxValue(9)
                ->default(5),
        ]);
    }
}