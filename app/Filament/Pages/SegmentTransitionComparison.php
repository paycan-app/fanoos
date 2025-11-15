<?php

namespace App\Filament\Pages;

use App\Services\RfmService;
use App\Settings\RfmSettingsContract;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SegmentTransitionComparison extends Page
{
    protected static ?string $navigationLabel = 'Transition Comparison';

    protected static ?string $title = 'Segment Transition Comparison';

    protected static UnitEnum|string|null $navigationGroup = 'Customers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    public ?string $baselineDate = null;

    public ?string $comparisonDate = null;

    public array $snapshotA = [];

    public array $snapshotB = [];

    public array $changes = [];

    public array $transitionMatrix = [
        'labels' => [],
        'matrix' => [],
        'total' => 0,
    ];

    public array $sankeyData = [];

    public ?string $message = null;

    public function mount(): void
    {
        $this->baselineDate = now()->subYear()->toDateString();
        $this->comparisonDate = now()->toDateString();
        $this->sankeyData = $this->emptySankeyPayload();

        $this->generateComparison();
    }

    public function content(Schema $schema): Schema
    {
        $settings = app(RfmSettingsContract::class);

        return $schema->components([
            Section::make('Choose Periods')
                ->description('Select two anchor dates. Each snapshot uses your configured '.$settings->getRfmTimeframeDays().'-day timeframe.')
                ->icon(Heroicon::CalendarDays)
                ->schema([
                    Form::make()->schema([
                        DatePicker::make('baselineDate')
                            ->label('Baseline date')
                            ->required()
                            ->native(false)
                            ->maxDate(fn () => $this->comparisonDate ? Carbon::parse($this->comparisonDate) : null),
                        DatePicker::make('comparisonDate')
                            ->label('Comparison date')
                            ->required()
                            ->native(false)
                            ->minDate(fn () => $this->baselineDate ? Carbon::parse($this->baselineDate) : null),
                    ]),
                ])
                ->footerActions([
                    Action::make('runComparison')
                        ->label('Analyze transitions')
                        ->icon(Heroicon::OutlinedPresentationChartLine)
                        ->color('primary')
                        ->action(fn () => $this->generateComparison()),
                ]),
            Section::make('Results & Insights')
                ->description('RFM summaries, deltas, and flow visualizations')
                ->icon(Heroicon::ChartBarSquare)
                ->schema([
                    \Filament\Schemas\Components\View::make('filament.pages.segment-transition-comparison')
                        ->viewData(fn () => [
                            'snapshotA' => $this->snapshotA,
                            'snapshotB' => $this->snapshotB,
                            'changes' => $this->changes,
                            'transitionMatrix' => $this->transitionMatrix,
                            'sankeyData' => $this->sankeyData,
                            'message' => $this->message,
                        ]),
                ]),
        ]);
    }

    public function generateComparison(): void
    {
        if (! $this->baselineDate || ! $this->comparisonDate) {
            $this->message = 'Select both baseline and comparison dates.';

            return;
        }

        $baseline = Carbon::parse($this->baselineDate)->endOfDay();
        $comparison = Carbon::parse($this->comparisonDate)->endOfDay();

        if ($baseline->gte($comparison)) {
            $this->message = 'Baseline date must be earlier than the comparison date.';

            return;
        }

        $service = app(RfmService::class);

        $this->snapshotA = $service->buildSegmentSnapshotForAsOfDate($baseline);
        $this->snapshotB = $service->buildSegmentSnapshotForAsOfDate($comparison);

        if (($this->snapshotA['total_customers'] ?? 0) === 0 && ($this->snapshotB['total_customers'] ?? 0) === 0) {
            $this->message = 'No customer activity detected for the selected windows.';
            $this->transitionMatrix = [
                'labels' => [],
                'matrix' => [],
                'total' => 0,
            ];
            $this->sankeyData = $this->emptySankeyPayload();
            $this->changes = [];

            return;
        }

        $this->message = null;
        $this->changes = $this->calculateChangeInsights($this->snapshotA, $this->snapshotB);

        $matrix = $service->buildTransitionsMatrixForAsOfDates($baseline, $comparison);
        $this->transitionMatrix = $matrix;
        $this->sankeyData = $this->buildSankeyPayload($matrix, $service->getSegmentDefinitions());
    }

    protected function calculateChangeInsights(array $baseline, array $comparison): array
    {
        $segmentDeltas = $this->buildSegmentDeltaList($baseline['segments'] ?? [], $comparison['segments'] ?? []);

        $currency = $comparison['currency'] ?? $baseline['currency'] ?? config('app.currency', 'USD');

        $totals = [
            'customers' => [
                'baseline' => $baseline['total_customers'] ?? 0,
                'comparison' => $comparison['total_customers'] ?? 0,
                'delta' => ($comparison['total_customers'] ?? 0) - ($baseline['total_customers'] ?? 0),
            ],
            'active_customers' => [
                'baseline' => $baseline['active_customers'] ?? 0,
                'comparison' => $comparison['active_customers'] ?? 0,
                'delta' => ($comparison['active_customers'] ?? 0) - ($baseline['active_customers'] ?? 0),
            ],
            'monetary' => [
                'baseline' => $baseline['metrics']['total_monetary'] ?? 0.0,
                'comparison' => $comparison['metrics']['total_monetary'] ?? 0.0,
                'currency' => $currency,
                'delta' => ($comparison['metrics']['total_monetary'] ?? 0.0) - ($baseline['metrics']['total_monetary'] ?? 0.0),
            ],
            'frequency' => [
                'baseline' => $baseline['metrics']['total_frequency'] ?? 0,
                'comparison' => $comparison['metrics']['total_frequency'] ?? 0,
                'delta' => ($comparison['metrics']['total_frequency'] ?? 0) - ($baseline['metrics']['total_frequency'] ?? 0),
            ],
        ];

        $averages = [
            'avg_recency' => [
                'baseline' => $baseline['metrics']['avg_recency'] ?? null,
                'comparison' => $comparison['metrics']['avg_recency'] ?? null,
                'delta' => (($comparison['metrics']['avg_recency'] ?? 0) - ($baseline['metrics']['avg_recency'] ?? 0)),
            ],
            'avg_frequency' => [
                'baseline' => $baseline['metrics']['avg_frequency'] ?? 0.0,
                'comparison' => $comparison['metrics']['avg_frequency'] ?? 0.0,
                'delta' => ($comparison['metrics']['avg_frequency'] ?? 0.0) - ($baseline['metrics']['avg_frequency'] ?? 0.0),
            ],
            'avg_monetary' => [
                'baseline' => $baseline['metrics']['avg_monetary'] ?? 0.0,
                'comparison' => $comparison['metrics']['avg_monetary'] ?? 0.0,
                'delta' => ($comparison['metrics']['avg_monetary'] ?? 0.0) - ($baseline['metrics']['avg_monetary'] ?? 0.0),
            ],
        ];

        $topGainers = array_values(array_filter($segmentDeltas, fn ($row) => $row['delta'] > 0));
        usort($topGainers, fn ($a, $b) => $b['delta'] <=> $a['delta']);

        $topLosers = array_values(array_filter($segmentDeltas, fn ($row) => $row['delta'] < 0));
        usort($topLosers, fn ($a, $b) => $a['delta'] <=> $b['delta']);

        $insights = $this->buildInsightsNarrative($totals, $topGainers, $topLosers);

        return [
            'totals' => $totals,
            'averages' => $averages,
            'segment_deltas' => $segmentDeltas,
            'top_gainers' => array_slice($topGainers, 0, 3),
            'top_losers' => array_slice($topLosers, 0, 3),
            'insights' => $insights,
        ];
    }

    protected function buildSegmentDeltaList(array $baselineSegments, array $comparisonSegments): array
    {
        $baselineIndex = [];
        foreach ($baselineSegments as $segment) {
            $baselineIndex[$segment['segment']] = $segment;
        }

        $comparisonIndex = [];
        foreach ($comparisonSegments as $segment) {
            $comparisonIndex[$segment['segment']] = $segment;
        }

        $segmentNames = array_values(array_unique(array_merge(array_keys($baselineIndex), array_keys($comparisonIndex))));
        sort($segmentNames);

        $deltas = [];
        foreach ($segmentNames as $segment) {
            $baselineCustomers = $baselineIndex[$segment]['customers'] ?? 0;
            $comparisonCustomers = $comparisonIndex[$segment]['customers'] ?? 0;
            $baselineShare = $baselineIndex[$segment]['share'] ?? 0.0;
            $comparisonShare = $comparisonIndex[$segment]['share'] ?? 0.0;
            $delta = $comparisonCustomers - $baselineCustomers;

            $deltas[] = [
                'segment' => $segment,
                'baseline' => $baselineCustomers,
                'comparison' => $comparisonCustomers,
                'delta' => $delta,
                'direction' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat'),
                'share_delta' => round($comparisonShare - $baselineShare, 2),
                'baseline_share' => $baselineShare,
                'comparison_share' => $comparisonShare,
            ];
        }

        usort(
            $deltas,
            fn ($a, $b) => abs($b['delta']) <=> abs($a['delta'])
        );

        return $deltas;
    }

    protected function buildInsightsNarrative(array $totals, array $topGainers, array $topLosers): array
    {
        $insights = [];
        $customerDelta = $totals['customers']['delta'] ?? 0;
        if ($customerDelta > 0) {
            $insights[] = 'Customer coverage grew by '.number_format($customerDelta).' over the comparison period.';
        } elseif ($customerDelta < 0) {
            $insights[] = 'Customer coverage fell by '.number_format(abs($customerDelta)).', indicating higher attrition.';
        } else {
            $insights[] = 'Customer coverage stayed flat between the two snapshots.';
        }

        $monetaryDelta = $totals['monetary']['delta'] ?? 0.0;
        $currency = $totals['monetary']['currency'] ?? config('app.currency', 'USD');
        if ($monetaryDelta > 0) {
            $insights[] = 'Aggregate revenue potential increased by '.$this->formatDeltaCurrency($monetaryDelta, $currency).'.';
        } elseif ($monetaryDelta < 0) {
            $insights[] = 'Aggregate revenue potential dropped by '.$this->formatDeltaCurrency($monetaryDelta, $currency).'.';
        }

        if (! empty($topGainers)) {
            $top = $topGainers[0];
            $insights[] = "{$top['segment']} gained ".number_format($top['delta']).' customers.';
        }

        if (! empty($topLosers)) {
            $top = $topLosers[0];
            $insights[] = "{$top['segment']} lost ".number_format(abs($top['delta'])).' customers.';
        }

        return $insights;
    }

    protected function buildSankeyPayload(array $matrixPayload, array $definitions): array
    {
        $labels = $matrixPayload['labels'] ?? [];
        $matrix = $matrixPayload['matrix'] ?? [];

        if (empty($labels) || empty($matrix)) {
            return $this->emptySankeyPayload();
        }

        $colorLookup = [];
        foreach ($definitions as $segment => $definition) {
            $colorLookup[$segment] = $this->mapDefinitionColor($definition['color'] ?? null);
        }

        $nodeLabels = [];
        $nodeColors = [];
        $count = count($labels);

        foreach ($labels as $label) {
            $nodeLabels[] = 'Period A · '.$label;
            $nodeColors[] = $colorLookup[$label] ?? $this->mapDefinitionColor();
        }

        foreach ($labels as $label) {
            $nodeLabels[] = 'Period B · '.$label;
            $nodeColors[] = $colorLookup[$label] ?? $this->mapDefinitionColor();
        }

        $sources = [];
        $targets = [];
        $values = [];
        $linkLabels = [];

        foreach ($matrix as $i => $row) {
            foreach ($row as $j => $value) {
                if ($value <= 0) {
                    continue;
                }

                $sources[] = $i;
                $targets[] = $count + $j;
                $values[] = $value;
                $linkLabels[] = $labels[$i].' → '.$labels[$j].' ('.number_format($value).')';
            }
        }

        if (empty($sources)) {
            return $this->emptySankeyPayload();
        }

        return [
            'node' => [
                'labels' => $nodeLabels,
                'colors' => $nodeColors,
            ],
            'link' => [
                'source' => $sources,
                'target' => $targets,
                'value' => $values,
                'label' => $linkLabels,
            ],
        ];
    }

    protected function emptySankeyPayload(): array
    {
        return [
            'node' => [
                'labels' => [],
                'colors' => [],
            ],
            'link' => [
                'source' => [],
                'target' => [],
                'value' => [],
                'label' => [],
            ],
        ];
    }

    protected function mapDefinitionColor(?string $color = null): string
    {
        return match ($color) {
            'green' => '#22c55e',
            'blue' => '#3b82f6',
            'cyan' => '#06b6d4',
            'orange' => '#f97316',
            'yellow' => '#facc15',
            'purple' => '#a855f7',
            'indigo' => '#6366f1',
            'red' => '#ef4444',
            'red-light' => '#f87171',
            default => '#94a3b8',
        };
    }

    protected function formatDeltaCurrency(float $value, string $currency): string
    {
        $symbol = $value >= 0 ? '+' : '-';

        return $symbol.$currency.' '.number_format(abs($value), 2);
    }
}
