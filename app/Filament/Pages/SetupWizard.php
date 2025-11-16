<?php

// Imports at top of file

namespace App\Filament\Pages;

use App\Services\RfmService;
use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
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

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1000;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrench;

    public array $rfm = [
        // removed: 'rfm_enable' => true,
        'rfm_bins' => 5,
        'rfm_segments' => 5,
        'rfm_timeframe_days' => 365,
        'analysis_date' => null,
    ];

    // removed: public bool $showAdvanced = false;

    public array $segmentStats = [];

    public array $previousSegmentStats = [];

    public array $insights = [];

    public array $segmentDefinitions = [];

    public array $metricDefinitions = [];

    public array $marimekkoData = [];

    public array $transitionsData = [];

    public ?string $currentAnalysisDate = null;

    public ?string $previousAnalysisDate = null;

    public bool $calculationInProgress = false;

    public bool $calculationComplete = false;

    public ?string $about_business = null;

    public ?string $calculationError = null;

    public function mount(): void
    {
        $settings = app(GeneralSettings::class);
        // Removed: $this->rfm['rfm_enable'] = $settings->rfm_enable;
        $this->rfm['rfm_bins'] = $settings->rfm_bins;
        $this->rfm['rfm_segments'] = $settings->rfm_segments;
        $this->rfm['rfm_timeframe_days'] = $settings->rfm_timeframe_days;
        $this->rfm['analysis_date'] = now()->toDateString();
        $this->about_business = $settings->about_business ?? '';

        // Load segment definitions and metric definitions
        $rfmService = app(RfmService::class);
        $this->segmentDefinitions = $rfmService->getSegmentDefinitions();
        $this->metricDefinitions = $rfmService->getMetricDefinitions();
    }

    public bool $showAdvanced = false;

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make()
                ->startOnStep(1)
                ->skippable(false)
                ->key('setup_wizard')
                ->persistStepInQueryString('setup_step')
                ->steps([
                    Step::make('Import CSVs')
                    ->description('Import your CSV files based on examples.')
                    ->schema([
                        Section::make()
                           // ->description('Download example CSVs and import your files in order.')
                            ->schema([
                                \Filament\Schemas\Components\Html::make('
                                    <div class="space-y-2 text-sm">
                                        <p class="font-medium text-neutral-700 dark:text-neutral-300">Example CSV templates:</p>
                                        <ul class="list-disc list-inside space-y-1 text-neutral-600 dark:text-neutral-400">
                                            <li><a href="/examples/customers_example.csv" download class="text-primary-600 hover:underline">customers_example.csv</a></li>
                                            <li><a href="/examples/products_example.csv" download class="text-primary-600 hover:underline">products_example.csv</a></li>
                                            <li><a href="/examples/orders_example.csv" download class="text-primary-600 hover:underline">orders_example.csv</a></li>
                                            <li><a href="/examples/order_items_example.csv" download class="text-primary-600 hover:underline">order_items_example.csv</a></li>
                                        </ul>
                                        <p class="text-xs text-neutral-500 mt-2">Import customers and products first, then orders and order items.</p>
                                    </div>
                                '),
                                Grid::make(4)->schema([
                                    \Filament\Schemas\Components\Text::make('Orders: '.\App\Models\Order::count())->badge(true)->color('warning'),
                                    \Filament\Schemas\Components\Text::make('Customers: '.\App\Models\Customer::count())->badge(true)->color('warning'),
                                    \Filament\Schemas\Components\Text::make('Products: '.\App\Models\Product::count())->badge(true)->color('warning'),

                                    \Filament\Schemas\Components\Text::make('Order Items: '.\App\Models\OrderItem::count())->badge(true)->color('warning'),
                                ]),
                            ])
                            ->footerActionsAlignment(\Filament\Support\Enums\Alignment::Center)
                            ->footerActions([
                                ImportAction::make('import_orders')
                                    ->label('1. Import Orders *')
                                    ->icon(\Filament\Support\Icons\Heroicon::ArrowUpTray)
                                    ->color('success')
                                    ->importer(\App\Filament\Imports\OrderImporter::class)
                                    ->modalDescription(static fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString('
                                        <div class="text-sm space-y-2">
                                            <p>Please upload a CSV file matching the expected columns.</p>
                                            <p>Download example: <a href="/examples/orders_example.csv" download class="text-primary-600 hover:underline">orders_example.csv</a></p>
                                        </div>
                                    '))
                                    ->after(fn () => $this->forceRender()),

                                ImportAction::make('import_customers')
                                    ->label('2. Import Customers')
                                    ->icon(\Filament\Support\Icons\Heroicon::ArrowUpTray)
                                    ->color('info')
                                    ->importer(\App\Filament\Imports\CustomerImporter::class)
                                    ->modalDescription(static fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString('
                                        <div class="text-sm space-y-2">
                                            <p>Please upload a CSV file matching the expected columns.</p>
                                            <p>Download example: <a href="/examples/customers_example.csv" download class="text-primary-600 hover:underline">customers_example.csv</a></p>
                                        </div>
                                    '))
                                    ->after(fn () => $this->forceRender()),
                                ImportAction::make('import_products')
                                    ->label('3. Import Products')
                                    ->icon(\Filament\Support\Icons\Heroicon::ArrowUpTray)
                                    ->color('info')
                                    ->importer(\App\Filament\Imports\ProductImporter::class)
                                    ->modalDescription(static fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString('
                                        <div class="text-sm space-y-2">
                                            <p>Please upload a CSV file matching the expected columns.</p>
                                            <p>Download example: <a href="/examples/products_example.csv" download class="text-primary-600 hover:underline">products_example.csv</a></p>
                                        </div>
                                    '))
                                    ->after(fn () => $this->forceRender()),
                                
                                ImportAction::make('import_order_items')
                                    ->label('4. Import Order Items')
                                    ->icon(\Filament\Support\Icons\Heroicon::ArrowUpTray)
                                    ->color('info')
                                    ->importer(\App\Filament\Imports\OrderItemImporter::class)
                                    ->modalDescription(static fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString('
                                        <div class="text-sm space-y-2">
                                            <p>Please upload a CSV file matching the expected columns.</p>
                                            <p>Download example: <a href="/examples/order_items_example.csv" download class="text-primary-600 hover:underline">order_items_example.csv</a></p>
                                        </div>
                                    '))
                                    ->after(fn () => $this->forceRender()),
                            ]),
                    ]),

                    Step::make('RFM Settings')
                    ->description('Choose timeframe, segments, and analysis date.')
                        ->schema([
                            Section::make()
                             //   ->description('Choose timeframe, segments, bins, and analysis date.')
                               // ->icon(Heroicon::Cog)
                                ->schema([
                                    \Filament\Schemas\Components\Form::make()->schema([
                                        Grid::make(2)->schema([

                                             \Filament\Schemas\Components\View::make('filament.forms.rfm-segments-boxes')
                                            ->viewData(fn () => [
                                                'modelPath' => 'rfm.rfm_segments',
                                                'options' => [
                                                    3 => ['label' => '3 Segments', 'desc' => 'High / Medium / Low value'],
                                                    5 => ['label' => '5 Segments', 'desc' => 'Champions, Loyal, Potential, etc.'],
                                                    11 => ['label' => '11 Segments', 'desc' => 'Detailed customer journey'],
                                                ],
                                            ]),

                                            // ToggleButtons::make('rfm.rfm_segments')
                                            //     ->label('Segmentation Level')
                                            //     ->inline()
                                            //     ->options([
                                            //         3 => '3 — Simple',
                                            //         5 => '5 — Recommended',
                                            //         11 => '11 — Advanced',
                                            //     ])
                                            //     ->required(),
                                        ]),


                                            

                                        Grid::make(2)->schema([
                                            Select::make('rfm.rfm_timeframe_days')
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
                                                ->helperText('The usual period an averge customer may buy again.')
                                                ->required(),

                                                
                                       

                                        ]),

                                        Textarea::make('about_business (Optional)')
                                            ->maxWidth(300)
                                            ->label('About Your Business')
                                            ->rows(3)
                                            ->helperText('A short description of your business and what you sell')
                                            ->maxLength(500),
                                    ]),
                                ]),

                            \Filament\Schemas\Components\View::make('filament.pages.setup-wizard-advanced-settings-toggle'),

                            Section::make('Advanced RFM Settings')
                                ->description('Choose bins, and an analysis date.')
                                ->icon(Heroicon::Cog)
                                ->hidden(fn () => ! $this->showAdvanced)
                                ->schema([
                                    \Filament\Schemas\Components\Form::make()->schema([
                                        Grid::make(1)->schema([
                                            Grid::make(2)->schema([                                  
                                                TextInput::make('rfm.rfm_bins')
                                                    ->label('RFM Score Bins')
                                                    ->numeric()
                                                    ->minValue(2)
                                                    ->maxValue(9)
                                                    ->default(5)
                                                    ->required()
                                                    ->suffix('bins'),
                                            ]),
                                            Grid::make(2)->schema([
                                                DatePicker::make('rfm.analysis_date')
                                                    ->label('Analysis Date')
                                                    ->default(now())
                                                    ->maxDate(now())
                                                    ->native(false)
                                                    ->displayFormat('Y-m-d')
                                                    ->required(),
                                            ]),
                                        ]),
                                    ]),
                                ]),
                        ]),

                    Step::make('Calculate & Review')
                        ->description('RFM analysis results and segment visualization.')
                        //->icon(Heroicon::ChartBar)
                        ->schema([
                            \Filament\Schemas\Components\View::make('filament.pages.setup-wizard-step-3-results'),
                        ]),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
           
        ];
    }

    public function saveRfmSettings(): void
    {
        $settings = app(GeneralSettings::class);
        // Removed: $settings->rfm_enable = (bool) ($this->rfm['rfm_enable'] ?? true);
        $settings->rfm_bins = (int) ($this->rfm['rfm_bins'] ?? 5);
        $settings->rfm_segments = (int) ($this->rfm['rfm_segments'] ?? 5);
        $settings->rfm_timeframe_days = (int) ($this->rfm['rfm_timeframe_days'] ?? 365);
        $settings->about_business = (string) ($this->about_business ?? '');
        $settings->save();

        Notification::make()
            ->title('RFM settings saved.')
            ->success()
            ->send();
    }

    public function startCalculation(): void
    {
        // Reset state
        $this->calculationInProgress = true;
        $this->calculationComplete = false;
        $this->calculationError = null;

        try {
            $this->calculateSegments();
            $this->calculationComplete = true;
        } catch (\Exception $e) {
            $this->calculationError = $e->getMessage();
            Notification::make()
                ->title('Calculation failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->calculationInProgress = false;
        }
    }

    public function calculateSegments(): void
    {
        $rfmService = app(RfmService::class);
        $analysisDate = $this->rfm['analysis_date'] ? \Carbon\Carbon::parse($this->rfm['analysis_date']) : now();
        $timeframeDays = (int) ($this->rfm['rfm_timeframe_days'] ?? 365);

        // Calculate current period segments
        $this->segmentStats = $rfmService->calculateSegments(
            timeframeDays: $timeframeDays,
            asOfDate: $analysisDate
        );

        if (isset($this->segmentStats['message'])) {
            Notification::make()
                ->title($this->segmentStats['message'])
                ->warning()
                ->send();

            return;
        }

        $this->currentAnalysisDate = $analysisDate->format('Y-m-d');

        // Calculate previous period for comparison
        $previousDate = $analysisDate->copy()->subDays($timeframeDays);
        $this->previousAnalysisDate = $previousDate->format('Y-m-d');

        $this->previousSegmentStats = $rfmService->calculateSegments(
            timeframeDays: $timeframeDays,
            asOfDate: $previousDate
        );

        // Generate insights by comparing periods
        if (! isset($this->previousSegmentStats['message']) && ! empty($this->previousSegmentStats)) {
            $this->insights = $rfmService->getInsights(
                $this->segmentStats,
                $this->previousSegmentStats,
                $analysisDate,
                $previousDate
            );
        } else {
            $this->insights = [];
        }

        // Build Marimekko data for monetary distribution
        $this->marimekkoData = $rfmService->buildMarimekkoByMonetary(
            timeframeDays: $timeframeDays,
            asOfDate: $analysisDate
        );

        // Build transitions matrix if we have both periods
        if (! empty($this->previousSegmentStats) && ! isset($this->previousSegmentStats['message'])) {
            $this->transitionsData = $rfmService->buildTransitionsMatrixForAsOfDates(
                $previousDate,
                $analysisDate
            );
        } else {
            $this->transitionsData = ['labels' => [], 'matrix' => [], 'total' => 0];
        }

        // Reload segment definitions in case settings changed
        $this->segmentDefinitions = $rfmService->getSegmentDefinitions();

        // Count total customers updated
        $totalCustomers = collect($this->segmentStats)->sum('customers');

        Notification::make()
            ->title('Customer segments calculated successfully!')
            ->body("Analysis complete: {$totalCustomers} customers segmented. Customers table updated with new segments.")
            ->success()
            ->send();

        // Dispatch JavaScript event to update charts with new data
        $this->dispatch('rfm-data-updated', [
            'stats' => $this->segmentStats,
            'totalCustomers' => $totalCustomers,
        ]);

        // Force component re-render to update charts
        $this->dispatch('$refresh');
    }
}
