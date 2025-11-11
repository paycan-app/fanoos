<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Services\RfmService;
use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class Segments extends Page implements \Filament\Tables\Contracts\HasTable
{
    use \Filament\Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationLabel = 'Customer Segments';

    protected static ?string $title = 'Customer Segments';

    protected static UnitEnum|string|null $navigationGroup = 'Customers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    public ?string $analysisDate = null;

    public function mount(): void
    {
        $this->analysisDate = now()->toDateString();
        // Recalculate segments using saved settings to ensure data is fresh
        app(RfmService::class)->calculateSegments();
    }

    public function content(Schema $schema): Schema
    {
        $settings = app(GeneralSettings::class);
        $totalCustomers = Customer::count();
        $segmentedCustomers = Customer::whereNotNull('segment')->count();
        $segmentCount = Customer::whereNotNull('segment')->distinct('segment')->count('segment');

        return $schema->components([
            // Summary Statistics Section
            Section::make('Segmentation Overview')
                ->description('Current RFM analysis configuration and statistics')
                ->icon(Heroicon::InformationCircle)
                ->schema([
                    Grid::make(4)->schema([
                        \Filament\Schemas\Components\Text::make('Total Customers: '.number_format($totalCustomers))
                            ->badge(true)
                            ->color('success'),
                        \Filament\Schemas\Components\Text::make('Segmented: '.number_format($segmentedCustomers))
                            ->badge(true)
                            ->color('primary'),
                        \Filament\Schemas\Components\Text::make('Active Segments: '.$segmentCount)
                            ->badge(true)
                            ->color('info'),
                        \Filament\Schemas\Components\Text::make('Coverage: '.($totalCustomers > 0 ? round(($segmentedCustomers / $totalCustomers) * 100, 1) : 0).'%')
                            ->badge(true)
                            ->color('warning'),
                    ]),
                ]),

            // Current Settings Section
            Section::make('RFM Configuration')
                ->description('Active analysis parameters')
                ->icon(Heroicon::Cog)
                ->collapsible()
                ->schema([
                    Grid::make(3)->schema([
                        \Filament\Schemas\Components\Text::make('Segmentation Level: '.$settings->rfm_segments.' segments')
                            ->badge(true)
                            ->color('primary'),
                        \Filament\Schemas\Components\Text::make('Score Bins: '.$settings->rfm_bins.' quantiles')
                            ->badge(true)
                            ->color('info'),
                        \Filament\Schemas\Components\Text::make('Timeframe: '.$settings->rfm_timeframe_days.' days')
                            ->badge(true)
                            ->color('success'),
                        // Removed status display based on rfm_enable
                        // \Filament\Schemas\Components\Text::make('Status: '.($settings->rfm_enable ? 'Enabled' : 'Disabled'))
                        //     ->badge(true)
                        //     ->color($settings->rfm_enable ? 'success' : 'danger'),
                    ]),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recalculate')
                ->label('Recalculate Segments')
                ->icon(Heroicon::ArrowPath)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Recalculate Customer Segments')
                ->modalDescription('This will recalculate RFM scores and reassign all customers to segments based on current settings.')
                ->modalSubmitActionLabel('Recalculate')
                ->form([
                    DatePicker::make('analysis_date')
                        ->label('Analysis Date')
                        ->helperText('Calculate RFM as of this date for historical analysis')
                        ->default(now())
                        ->maxDate(now())
                        ->native(false)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $analysisDate = $data['analysis_date'] ? \Carbon\Carbon::parse($data['analysis_date']) : null;
                    app(RfmService::class)->calculateSegments(asOfDate: $analysisDate);

                    Notification::make()
                        ->title('Segments recalculated successfully!')
                        ->body('Analysis date: '.($analysisDate?->format('Y-m-d') ?? 'Today'))
                        ->success()
                        ->send();

                    $this->redirect(static::getUrl());
                }),
        ];
    }

    public function table(Table $table): Table
    {
        $totalCustomers = Customer::whereNotNull('segment')->count();

        return $table
            ->query(
                Customer::query()
                    ->selectRaw('
                        segment,
                        COUNT(*) as customers,
                        ROUND(AVG(CASE
                            WHEN JSON_LENGTH(labels) > 0 THEN 1
                            ELSE 0
                        END) * 100, 1) as labeled_percentage
                    ')
                    ->whereNotNull('segment')
                    ->groupBy('segment')
            )
            ->columns([
                Tables\Columns\TextColumn::make('segment')
                    ->label('Segment')
                    ->badge()
                    ->sortable()
                    ->searchable()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'Champion') => 'warning',
                        str_contains($state, 'Loyal') => 'success',
                        str_contains($state, 'Potential') || str_contains($state, 'Promising') => 'info',
                        str_contains($state, 'At Risk') || str_contains($state, 'Sleep') => 'danger',
                        str_contains($state, 'Lost') || str_contains($state, 'Hibernating') => 'danger',
                        str_contains($state, 'New') => 'primary',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('customers')
                    ->label('Customer Count')
                    ->sortable()
                    ->numeric()
                    ->description(fn ($record) => $totalCustomers > 0
                        ? round(($record->customers / $totalCustomers) * 100, 1).'% of total'
                        : '0%'
                    ),

                Tables\Columns\TextColumn::make('percentage')
                    ->label('% of Total')
                    ->state(fn ($record) => $totalCustomers > 0
                        ? round(($record->customers / $totalCustomers) * 100, 1)
                        : 0
                    )
                    ->suffix('%')
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('customers', $direction)),
            ])
            ->defaultSort('customers', 'desc')
            ->recordAction(null)
            ->recordUrl(null)
            ->emptyStateHeading('No Segments Found')
            ->emptyStateDescription('Run the RFM calculation to segment your customers')
            ->emptyStateIcon(Heroicon::ChartBarSquare);
    }
}
