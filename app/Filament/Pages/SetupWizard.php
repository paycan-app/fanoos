<?php

namespace App\Filament\Pages;

use App\Services\RfmService;
use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
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
        'analysis_date' => null,
    ];

    public array $segmentStats = [];

    public array $previousSegmentStats = [];

    public array $insights = [];

    public array $segmentDefinitions = [];

    public array $metricDefinitions = [];

    public array $marimekkoData = [];

    public array $transitionsData = [];

    public ?string $currentAnalysisDate = null;

    public ?string $previousAnalysisDate = null;

    public function mount(): void
    {
        $settings = app(GeneralSettings::class);
        $this->rfm['rfm_enable'] = $settings->rfm_enable;
        $this->rfm['rfm_bins'] = $settings->rfm_bins;
        $this->rfm['rfm_segments'] = $settings->rfm_segments;
        $this->rfm['rfm_timeframe_days'] = $settings->rfm_timeframe_days;
        $this->rfm['analysis_date'] = now()->toDateString();

        // Load segment definitions and metric definitions
        $rfmService = app(RfmService::class);
        $this->segmentDefinitions = $rfmService->getSegmentDefinitions();
        $this->metricDefinitions = $rfmService->getMetricDefinitions();
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
                        Section::make('Import Data')
                            ->description('Download example CSVs and import your files in order.')
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
                                    \Filament\Schemas\Components\Text::make('Customers: '.\App\Models\Customer::count())->badge(true),
                                    \Filament\Schemas\Components\Text::make('Products: '.\App\Models\Product::count())->badge(true),
                                    \Filament\Schemas\Components\Text::make('Orders: '.\App\Models\Order::count())->badge(true),
                                    \Filament\Schemas\Components\Text::make('Order Items: '.\App\Models\OrderItem::count())->badge(true),
                                ]),
                            ])
                            ->footerActionsAlignment(\Filament\Support\Enums\Alignment::Center)
                            ->footerActions([
                                ImportAction::make('import_customers')
                                    ->label('1. Import Customers')
                                    ->icon(\Filament\Support\Icons\Heroicon::ArrowUpTray)
                                    ->color('success')
                                    ->importer(\App\Filament\Imports\CustomerImporter::class)
                                    ->modalDescription(static fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString('
                                        <div class="text-sm space-y-2">
                                            <p>Please upload a CSV file matching the expected columns.</p>
                                            <p>Download example: <a href="/examples/customers_example.csv" download class="text-primary-600 hover:underline">customers_example.csv</a></p>
                                        </div>
                                    '))
                                    ->after(fn () => $this->forceRender()),
                                ImportAction::make('import_products')
                                    ->label('2. Import Products')
                                    ->icon(\Filament\Support\Icons\Heroicon::ArrowUpTray)
                                    ->color('warning')
                                    ->importer(\App\Filament\Imports\ProductImporter::class)
                                    ->modalDescription(static fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString('
                                        <div class="text-sm space-y-2">
                                            <p>Please upload a CSV file matching the expected columns.</p>
                                            <p>Download example: <a href="/examples/products_example.csv" download class="text-primary-600 hover:underline">products_example.csv</a></p>
                                        </div>
                                    '))
                                    ->after(fn () => $this->forceRender()),
                                ImportAction::make('import_orders')
                                    ->label('3. Import Orders')
                                    ->icon(\Filament\Support\Icons\Heroicon::ArrowUpTray)
                                    ->color('primary')
                                    ->importer(\App\Filament\Imports\OrderImporter::class)
                                    ->modalDescription(static fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString('
                                        <div class="text-sm space-y-2">
                                            <p>Please upload a CSV file matching the expected columns.</p>
                                            <p>Download example: <a href="/examples/orders_example.csv" download class="text-primary-600 hover:underline">orders_example.csv</a></p>
                                        </div>
                                    '))
                                    ->after(fn () => $this->forceRender()),
                                ImportAction::make('import_order_items')
                                    ->label('4. Import Order Items')
                                    ->icon(\Filament\Support\Icons\Heroicon::ArrowUpTray)
                                    ->color('gray')
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

                    Step::make('RFM Settings')->schema([
                        Section::make('RFM Configuration')
                            ->description('Configure your RFM analysis parameters to segment customers based on their purchasing behavior.')
                            ->icon(Heroicon::Cog)
                            ->schema([
                                \Filament\Schemas\Components\Form::make()->schema([
                                    Grid::make(1)->schema([
                                        Toggle::make('rfm.rfm_enable')
                                            ->label('Enable RFM Segmentation')
                                            ->helperText('Activate customer segmentation based on Recency, Frequency, and Monetary analysis.')
                                            ->default(true)
                                            ->inline(false),
                                    ]),

                                    Grid::make(2)->schema([
                                        Radio::make('rfm.rfm_segments')
                                            ->label('Segmentation Level')
                                            ->helperText('Choose the granularity of customer segments.')
                                            ->options([
                                                3 => '3 Segments - Simple segmentation (High, Medium, Low Value)',
                                                5 => '5 Segments - Standard segmentation (Champions, Loyal, Potential, At Risk, Need Attention)',
                                                11 => '11 Segments - Advanced segmentation (Detailed customer journey)',
                                            ])
                                            ->descriptions([
                                                3 => 'Best for quick insights and simple reporting',
                                                5 => 'Recommended for most businesses',
                                                11 => 'Deep analysis for advanced marketing strategies',
                                            ])
                                            ->default(5)
                                            ->required()
                                            ->inline(false),

                                        Radio::make('rfm.rfm_timeframe_days')
                                            ->label('Analysis Timeframe')
                                            ->helperText('Historical period to analyze customer behavior.')
                                            ->options([
                                                90 => '90 Days (Quarter)',
                                                180 => '6 Months',
                                                365 => '1 Year',
                                                730 => '2 Years',
                                                1825 => '5 Years',
                                            ])
                                            ->descriptions([
                                                90 => 'Recent activity focus',
                                                180 => 'Short-term trends',
                                                365 => 'Annual performance',
                                                730 => 'Long-term patterns',
                                                1825 => 'Complete history',
                                            ])
                                            ->default(365)
                                            ->required()
                                            ->inline(false),
                                    ]),

                                    Grid::make(2)->schema([
                                        TextInput::make('rfm.rfm_bins')
                                            ->label('RFM Score Bins')
                                            ->helperText('Number of quantile bins for scoring (2-9). Higher values provide finer granularity.')
                                            ->numeric()
                                            ->minValue(2)
                                            ->maxValue(9)
                                            ->default(5)
                                            ->required()
                                            ->suffix('bins'),

                                        DatePicker::make('rfm.analysis_date')
                                            ->label('Analysis Date')
                                            ->helperText('Calculate RFM as of this date. Use past dates for historical analysis.')
                                            ->default(now())
                                            ->maxDate(now())
                                            ->native(false)
                                            ->displayFormat('Y-m-d')
                                            ->required(),
                                    ]),
                                ]),
                            ]),
                        Section::make('Apply Settings')
                            ->description('Save your configuration before calculating customer segments.')
                            ->icon(Heroicon::CheckCircle)
                            ->schema([
                                \Filament\Schemas\Components\View::make('filament.pages.setup-wizard-save-settings'),
                            ]),
                    ]),

                    Step::make('Calculate & Review')->schema([
                        Section::make('Customer Segmentation Results')
                            ->description('Calculate RFM segments and visualize customer distribution across segments.')
                            ->icon(Heroicon::ChartBar)
                            ->schema([
                                \Filament\Schemas\Components\View::make('filament.pages.setup-wizard-run-calc'),
                                \Filament\Schemas\Components\View::make('filament.pages.rfm-results')
                                    ->viewData(fn () => [
                                        'stats' => $this->segmentStats,
                                        'previousStats' => $this->previousSegmentStats,
                                        'insights' => $this->insights,
                                        'segmentDefinitions' => $this->segmentDefinitions,
                                        'metricDefinitions' => $this->metricDefinitions,
                                        'marimekkoData' => $this->marimekkoData,
                                        'transitionsData' => $this->transitionsData,
                                        'currentAnalysisDate' => $this->currentAnalysisDate,
                                        'previousAnalysisDate' => $this->previousAnalysisDate,
                                    ]),
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
        if (!isset($this->previousSegmentStats['message']) && !empty($this->previousSegmentStats)) {
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
        if (!empty($this->previousSegmentStats) && !isset($this->previousSegmentStats['message'])) {
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

        // Force component re-render to update charts
        $this->dispatch('$refresh');
    }
}
