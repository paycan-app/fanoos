<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\RfmService;
use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SetupWizard extends Page
{
    protected static ?string $navigationLabel = 'Setup Wizard';

    protected static ?string $title = 'Setup Wizard';

    protected static UnitEnum|string|null $navigationGroup = 'Setup';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrench;

    public array $rfm = [
        'rfm_enable' => true,
        'rfm_bins' => 5,
        'rfm_segments' => 5,
        'rfm_timeframe_days' => 365,
    ];

    public array $segmentStats = [];

    public function mount(): void
    {
        $settings = app(GeneralSettings::class);
        $this->rfm['rfm_enable'] = $settings->rfm_enable;
        $this->rfm['rfm_bins'] = $settings->rfm_bins;
        $this->rfm['rfm_segments'] = $settings->rfm_segments;
        $this->rfm['rfm_timeframe_days'] = $settings->rfm_timeframe_days;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make()
                ->startOnStep(1)
                ->skippable(false)
                ->key('setup_wizard')
                ->persistStepInQueryString('setup_step')
                ->steps([
                    Step::make('Import CSVs')->schema([
                        Section::make('Example CSV Files')
                            ->description('Download example files to see the correct format.')
                            ->schema([
                                \Filament\Schemas\Components\Html::make('
                                    <div class="space-y-2 text-sm">
                                        <p class="font-medium text-neutral-700 dark:text-neutral-300">Download example CSV templates:</p>
                                        <ul class="list-disc list-inside space-y-1 text-neutral-600 dark:text-neutral-400">
                                            <li><a href="/examples/customers_example.csv" download class="text-primary-600 hover:underline">customers_example.csv</a></li>
                                            <li><a href="/examples/products_example.csv" download class="text-primary-600 hover:underline">products_example.csv</a></li>
                                            <li><a href="/examples/orders_example.csv" download class="text-primary-600 hover:underline">orders_example.csv</a></li>
                                            <li><a href="/examples/order_items_example.csv" download class="text-primary-600 hover:underline">order_items_example.csv</a></li>
                                        </ul>
                                        <p class="text-xs text-neutral-500 mt-2">💡 Import customers and products first, then orders and order items.</p>
                                    </div>
                                '),
                            ]),

                        Section::make('Import Data')
                            ->description('Upload your CSV files in the correct order.')
                            ->schema([
                                \Filament\Schemas\Components\Html::make('<p class="text-sm text-neutral-600 dark:text-neutral-400">Use the buttons below to import your data files.</p>'),
                            ])
                            ->footerActionsAlignment(\Filament\Support\Enums\Alignment::Center)
                            ->footerActions([
                                ImportAction::make('import_customers')
                                    ->label('1. Import Customers')
                                    ->icon(\Filament\Support\Icons\Heroicon::ArrowUpTray)
                                    ->color('success')
                                    ->importer(\App\Filament\Imports\CustomerImporter::class)
                                    ->after(fn () => $this->forceRender()),
                                ImportAction::make('import_products')
                                    ->label('2. Import Products')
                                    ->icon(\Filament\Support\Icons\Heroicon::ArrowUpTray)
                                    ->color('warning')
                                    ->importer(\App\Filament\Imports\ProductImporter::class)
                                    ->after(fn () => $this->forceRender()),
                                ImportAction::make('import_orders')
                                    ->label('3. Import Orders')
                                    ->icon(\Filament\Support\Icons\Heroicon::ArrowUpTray)
                                    ->color('primary')
                                    ->importer(\App\Filament\Imports\OrderImporter::class)
                                    ->after(fn () => $this->forceRender()),
                                ImportAction::make('import_order_items')
                                    ->label('4. Import Order Items')
                                    ->icon(\Filament\Support\Icons\Heroicon::ArrowUpTray)
                                    ->color('gray')
                                    ->importer(\App\Filament\Imports\OrderItemImporter::class)
                                    ->after(fn () => $this->forceRender()),
                            ]),

                        Section::make('Import Progress')
                            ->description('Quick status of imported records.')
                            ->schema([
                                Grid::make(4)->schema([
                                    \Filament\Schemas\Components\Text::make('Customers: '.\App\Models\Customer::count())->badge(true),
                                    \Filament\Schemas\Components\Text::make('Products: '.\App\Models\Product::count())->badge(true),
                                    \Filament\Schemas\Components\Text::make('Orders: '.\App\Models\Order::count())->badge(true),
                                    \Filament\Schemas\Components\Text::make('Order Items: '.\App\Models\OrderItem::count())->badge(true),
                                ]),
                            ]),
                    ]),

                    Step::make('RFM Settings')->schema([
                        Section::make('Segmentation Configuration')
                            ->description('Configure how customer segments are calculated and analyzed.')
                            ->schema([
                                \Filament\Schemas\Components\Form::make()->schema([
                                    Toggle::make('rfm.rfm_enable')
                                        ->label('Enable RFM Segmentation')
                                        ->hint('Turn segmentation on/off.')
                                        ->default(true),
                                    Grid::make(2)->schema([
                                        Select::make('rfm.rfm_segments')
                                            ->label('Number of Segments')
                                            ->options([
                                                3 => '3 Segments (Simple: High/Medium/Low Value)',
                                                5 => '5 Segments (Standard: Champions, Loyal, Potential, At Risk, Need Attention)',
                                                11 => '11 Segments (Advanced: Detailed Customer Journey)',
                                            ])
                                            ->default(5)
                                            ->required()
                                            ->hint('Choose segment granularity for your analysis.'),
                                        Select::make('rfm.rfm_timeframe_days')
                                            ->label('Analysis Timeframe')
                                            ->options([
                                                90 => 'Last 90 Days (Quarter)',
                                                180 => 'Last 6 Months',
                                                365 => 'Last Year',
                                                730 => 'Last 2 Years',
                                                1825 => 'Last 5 Years (All Time)',
                                            ])
                                            ->default(365)
                                            ->required()
                                            ->hint('Time window for RFM calculations.'),
                                    ]),
                                    TextInput::make('rfm.rfm_bins')
                                        ->label('RFM Bins (Quantiles)')
                                        ->numeric()
                                        ->minValue(2)
                                        ->maxValue(9)
                                        ->default(5)
                                        ->hint('Number of quantile bins for scoring R, F, and M.'),
                                ]),
                            ]),
                        Section::make('Apply Settings')
                            ->description('Save configuration before calculating segments.')
                            ->schema([
                                \Filament\Schemas\Components\View::make('filament.pages.setup-wizard-save-settings'),
                            ]),
                    ]),

                    Step::make('Calculate & Review')->schema([
                        Section::make('Run Segmentation')
                            ->description('Classify customers and analyze segment performance.')
                            ->schema([
                                \Filament\Schemas\Components\View::make('filament.pages.setup-wizard-run-calc'),
                            ]),
                        Section::make('RFM Analysis Results')
                            ->description('Interactive charts and detailed segment statistics.')
                            ->schema([
                                \Filament\Schemas\Components\View::make('filament.pages.rfm-results')
                                    ->viewData(fn () => ['stats' => $this->segmentStats]),
                            ]),
                    ]),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make('import_orders')
                ->label('Import Orders CSV')
                ->importer(\App\Filament\Imports\OrderImporter::class),

            ImportAction::make('import_order_items')
                ->label('Import Order Items CSV')
                ->importer(\App\Filament\Imports\OrderItemImporter::class),

            Action::make('save_rfm_settings')
                ->label('Save RFM Settings')
                ->action(function () {
                    $this->saveRfmSettings();
                })
                ->color('success'),

            Action::make('calculate_segments')
                ->label('Calculate Segments')
                ->action(fn () => $this->calculateSegments())
                ->color('primary'),
        ];
    }

    public function saveRfmSettings(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->rfm_enable = (bool) ($this->rfm['rfm_enable'] ?? true);
        $settings->rfm_bins = (int) ($this->rfm['rfm_bins'] ?? 5);
        $settings->rfm_segments = (int) ($this->rfm['rfm_segments'] ?? 5);
        $settings->rfm_timeframe_days = (int) ($this->rfm['rfm_timeframe_days'] ?? 365);
        $settings->save();

        Notification::make()
            ->title('RFM settings saved.')
            ->success()
            ->send();
    }

    public function calculateSegments(): void
    {
        $rfmService = app(RfmService::class);
        $this->segmentStats = $rfmService->calculateSegments();

        if (isset($this->segmentStats['message'])) {
            Notification::make()
                ->title($this->segmentStats['message'])
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title('Customer segments calculated.')
            ->success()
            ->send();
    }
}
