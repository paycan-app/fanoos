<?php

// Imports at top of file
namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageGeneralSettings extends SettingsPage
{
    protected static string $settings = GeneralSettings::class;

    protected static UnitEnum|string|null $navigationGroup = 'Settings';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;
    protected static ?string $navigationLabel = 'Settings';
    protected static ?string $title = 'Settings';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('General')
                ->schema([
                    TextInput::make('site_name')
                        ->label('Site Name')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('contact_email')
                        ->label('Contact Email')
                        ->email()
                        ->required(),

                    Textarea::make('about_business')
                        ->label('About Business')
                        ->rows(3)
                        ->maxLength(500),
                ]),

            Section::make('RFM Settings')
                ->description('Configure global defaults for RFM analysis.')
                ->schema([
                    \Filament\Schemas\Components\View::make('filament.forms.rfm-segments-boxes')
                    ->viewData(fn () => [
                        'modelPath' => 'data.rfm_segments',
                        'options' => [
                            3 => ['label' => '3 Segments', 'desc' => 'High / Medium / Low value'],
                            5 => ['label' => '5 Segments', 'desc' => 'Champions, Loyal, Potential, etc.'],
                            11 => ['label' => '11 Segments', 'desc' => 'Detailed customer journey'],
                        ],
                    ]),
                    
                    // Helpful for future
                    // ToggleButtons::make('rfm_segments22')
                    //     ->label('Segmentation Level')
                    //     ->options([
                    //         3 => '3 — Simple',
                    //         5 => '5 — Recommended',
                    //         11 => '11 — Advanced',
                    //     ])
                    //     ->inline()
                    //     ->required(),

                    Select::make('rfm_timeframe_days')
                        ->label('Analysis Timeframe')
                        ->options([
                            7 => '7 Days',
                            15 => '15 Days',
                            30 => '30 Days',
                            45 => '45 Days',
                            90 => '90 Days (Quarter)',
                            180 => '6 Months',
                            365 => '1 Year',
                            730 => '2 Years',
                            1825 => '5 Years',
                        ])
                        ->required(),

                    TextInput::make('rfm_bins')
                        ->label('RFM Score Bins')
                        ->helperText('Number of quantile bins for scoring (2–9).')
                        ->numeric()
                        ->minValue(2)
                        ->maxValue(9)
                        ->default(5)
                        ->required()
                        ->suffix('bins'),
                ]),

            Section::make('Danger Zone')
                ->description('Permanently delete all imported data.')
                ->schema([
                    \Filament\Schemas\Components\Html::make('<p class="text-sm text-danger-600">This will delete all customers, orders, order items, and products. This cannot be undone.</p>'),
                ])
                ->footerActionsAlignment(\Filament\Support\Enums\Alignment::Center)
                ->footerActions([
                    Action::make('deleteAllData')
                        ->label('Delete All Data')
                        ->color('danger')
                        ->icon(Heroicon::OutlinedTrash)
                        ->requiresConfirmation()
                        ->modalHeading('Delete All Data')
                        ->modalDescription('Are you sure you want to delete ALL customer, order, product, and order item data? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete everything')
                        ->action(fn () => $this->deleteAllData()),
                ]),
        ]);
    }

    protected function deleteAllData(): void
    {
        try {
            // Delete in FK-safe order
            \App\Models\OrderItem::query()->delete();
            \App\Models\Order::query()->delete();
            \App\Models\Customer::query()->delete();
            \App\Models\Product::query()->delete();

            \Filament\Notifications\Notification::make()
                ->title('All Data Deleted Successfully')
                ->body('All customer, order, product, and order item data has been cleared.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error Deleting Data')
                ->body('An error occurred while trying to delete data: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}