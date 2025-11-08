<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ImportAction;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;
use UnitEnum;
use Filament\Notifications\Notification;

class SetupWizard extends Page
{
    protected static ?string $navigationLabel = 'Setup Wizard';
    protected static ?string $title = 'Setup Wizard';
    protected static UnitEnum|string|null $navigationGroup = 'Setup';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrench;

    public array $rfm = [
        'rfm_enable' => true,
        'rfm_bins' => 5,
    ];

    public array $segmentStats = [];

    public function mount(): void
    {
        $settings = app(GeneralSettings::class);
        $this->rfm['rfm_enable'] = $settings->rfm_enable;
        $this->rfm['rfm_bins'] = $settings->rfm_bins;
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
                        Section::make('Import Orders & Order Items')
                            ->description('Upload your CSVs using the buttons below. This uses Filament’s importer with mapping and validation.')
                            ->schema([
                                \Filament\Schemas\Components\Html::make('<p class="text-sm text-neutral-600">Start by importing your CSV files. Use the buttons below to open the import interfaces for Orders and Order Items.</p>'),
                            ])
                            ->footerActionsAlignment(\Filament\Support\Enums\Alignment::Center)
                            ->footerActions([
                                ImportAction::make('import_orders')
                                    ->label('Import Orders CSV')
                                    ->icon(\Filament\Support\Icons\Heroicon::ArrowUpTray)
                                    ->color('primary')
                                    ->importer(\App\Filament\Imports\OrderImporter::class)
                                    ->after(fn () => $this->forceRender()),
                                ImportAction::make('import_order_items')
                                    ->label('Import Order Items CSV')
                                    ->icon(\Filament\Support\Icons\Heroicon::ArrowUpTray)
                                    ->color('gray')
                                    ->importer(\App\Filament\Imports\OrderItemImporter::class)
                                    ->after(fn () => $this->forceRender()),
                            ]),

                        Section::make('Import Progress')
                            ->description('Quick status of imported records.')
                            ->schema([
                                Grid::make(3)->schema([
                                    \Filament\Schemas\Components\Text::make('Orders Imported: ' . \App\Models\Order::count())->badge(true),
                                    \Filament\Schemas\Components\Text::make('Order Items Imported: ' . \App\Models\OrderItem::count())->badge(true),
                                    \Filament\Schemas\Components\Text::make('Customers Detected: ' . \App\Models\Customer::count())->badge(true),
                                ]),
                            ]),
                    ]),

                    Step::make('RFM Settings')->schema([
                        Section::make('Segmentation Configuration')
                            ->description('Tune how segments are calculated. Quantile bins define how R/F/M scores are distributed.')
                            ->schema([
                                \Filament\Schemas\Components\Form::make()->schema([
                                    Toggle::make('rfm.rfm_enable')
                                        ->label('Enable RFM Segmentation')
                                        ->hint('Turn segmentation on/off.'),
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
                            ->description('Classify customers and then review the stats below.')
                            ->schema([
                                \Filament\Schemas\Components\View::make('filament.pages.setup-wizard-run-calc'),
                            ]),
                        Section::make('Customer Segment Stats')
                            ->description('Aggregated insights across segments.')
                            ->schema([
                                \Filament\Schemas\Components\View::make('filament.pages.setup-wizard-segment-stats')
                                    ->viewData(['stats' => fn () => $this->segmentStats]),
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
                    $settings = app(GeneralSettings::class);
                    $settings->rfm_enable = (bool) ($this->rfm['rfm_enable'] ?? true);
                    $settings->rfm_bins = (int) ($this->rfm['rfm_bins'] ?? 5);
                    $settings->save();
                    
                    Notification::make()
                        ->title('RFM settings saved.')
                        ->success()
                        ->send();
                })
                ->color('success'),

            Action::make('calculate_segments')
                ->label('Calculate Segments')
                ->action(fn () => $this->calculateSegments())
                ->color('primary'),
        ];
    }

    public function calculateSegments(): void
    {
        $settings = app(GeneralSettings::class);
        if (! $settings->rfm_enable) {
            $this->segmentStats = ['message' => 'RFM is disabled in settings.'];
            return;
        }

        $bins = max(2, min(9, (int) $settings->rfm_bins));

        $customers = Customer::query()->with('orders')->get();

        $recencies = [];
        $frequencies = [];
        $monetaries = [];

        foreach ($customers as $c) {
            $r = $c->recency;
            $f = $c->frequency;
            $m = $c->monetary;

            // Exclude customers with no orders from scoring but keep for "Lost" segment
            if ($r !== null) {
                $recencies[] = $r;
            }
            $frequencies[] = $f;
            $monetaries[] = $m;
        }

        $rBreaks = $this->quantileBreaks($recencies, $bins);
        $fBreaks = $this->quantileBreaks($frequencies, $bins);
        $mBreaks = $this->quantileBreaks($monetaries, $bins);

        $stats = [];

        foreach ($customers as $c) {
            $rScore = $this->scoreValue($c->recency, $rBreaks, $bins, invert: true);
            $fScore = $this->scoreValue($c->frequency, $fBreaks, $bins);
            $mScore = $this->scoreValue($c->monetary, $mBreaks, $bins);

            $segment = $this->assignSegment($rScore, $fScore, $mScore, $c);

            // persist segment
            $c->segment = $segment;
            $c->save();

            if (! isset($stats[$segment])) {
                $stats[$segment] = [
                    'segment' => $segment,
                    'customers' => 0,
                    'avg_monetary' => 0.0,
                    'avg_frequency' => 0.0,
                    'avg_recency' => 0.0,
                    'sum_monetary' => 0.0,
                    'sum_frequency' => 0,
                    'sum_recency' => 0,
                ];
            }

            $stats[$segment]['customers']++;
            $stats[$segment]['sum_monetary'] += (float) $c->monetary;
            $stats[$segment]['sum_frequency'] += (int) $c->frequency;
            $stats[$segment]['sum_recency'] += (int) ($c->recency ?? 0);
        }

        foreach ($stats as &$row) {
            $count = max(1, $row['customers']);
            $row['avg_monetary'] = round($row['sum_monetary'] / $count, 2);
            $row['avg_frequency'] = round($row['sum_frequency'] / $count, 2);
            $row['avg_recency'] = round($row['sum_recency'] / $count, 2);
            unset($row['sum_monetary'], $row['sum_frequency'], $row['sum_recency']);
        }

        // Sort by customers desc
        $this->segmentStats = collect($stats)->sortByDesc('customers')->values()->toArray();

        Notification::make()
            ->title('Customer segments calculated.')
            ->success()
            ->send();
    }

    protected function quantileBreaks(array $values, int $bins): array
    {
        if (empty($values)) {
            return [];
        }

        sort($values);
        $n = count($values);
        $breaks = [];

        for ($k = 1; $k < $bins; $k++) {
            $pos = (int) round(($k / $bins) * ($n - 1));
            $breaks[] = $values[$pos];
        }

        return $breaks;
    }

    protected function scoreValue(?float $value, array $breaks, int $bins, bool $invert = false): int
    {
        if ($value === null) {
            return 1; // No data -> lowest score
        }

        $score = 1;
        foreach ($breaks as $b) {
            if ($value > $b) {
                $score++;
            } else {
                break;
            }
        }

        $score = min($bins, $score);

        return $invert ? ($bins + 1 - $score) : $score;
    }

    protected function assignSegment(int $r, int $f, int $m, Customer $c): string
    {
        if ($c->recency === null || $f === 0 || $m === 0.0) {
            return 'Lost';
        }

        if ($r >= 4 && $f >= 4 && $m >= 4) {
            return 'Champion';
        }

        if ($f >= 4 && $m >= 4) {
            return 'Loyal';
        }

        if ($r >= 4 && $f >= 3) {
            return 'Potential Loyalist';
        }

        if ($r <= 2 && $f <= 2) {
            return 'At Risk';
        }

        if ($r === 1) {
            return 'Hibernating';
        }

        return 'Need Attention';
    }
}