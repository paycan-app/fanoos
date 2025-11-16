<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BusinessPulseStats;
use App\Filament\Widgets\RevenueOrdersTrend;
use App\Filament\Widgets\TopProductsTable;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Set;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Business overview';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static BackedEnum|string|null $navigationIcon = Heroicon::ChartBar;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Analysis window')
                ->description('Every widget on this page follows the window you set here.')
                ->schema([
                    ToggleButtons::make('range_preset')
                        ->label('Quick presets')
                        ->options([
                            '7' => '7d',
                            '30' => '30d',
                            '90' => '90d',
                            'ytd' => 'YTD',
                            'custom' => 'Custom',
                        ])
                        ->default('30')
                        ->helperText('Pick a preset or fine‑tune the dates below.')
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            $this->applyPreset($state, $set);
                        })
                        ->inline()
                        ->grouped(),
                    Grid::make([
                        'md' => 2,
                        'xl' => 3,
                    ])->schema([
                        DatePicker::make('range_start')
                            ->label('Start date')
                            ->displayFormat('M j, Y')
                            ->native(false)
                            ->closeOnDateSelection()
                            ->default(now()->subDays(29))
                            ->maxDate(now())
                            ->prefixIcon(Heroicon::CalendarDays)
                            ->helperText('Auto-filled by your preset, but editable.'),
                        DatePicker::make('range_end')
                            ->label('End date')
                            ->displayFormat('M j, Y')
                            ->native(false)
                            ->closeOnDateSelection()
                            ->default(now())
                            ->maxDate(now())
                            ->prefixIcon(Heroicon::CalendarDays)
                            ->helperText('Widgets compare against data up to this day.'),
                        Select::make('comparison_mode')
                            ->label('Compare against')
                            ->options([
                                'previous_period' => 'Previous period',
                                'previous_year' => 'Same dates last year',
                                'custom' => 'Manual range',
                            ])
                            ->default('previous_period')
                            ->live()
                            ->helperText('Use manual range if you need a specific baseline.'),
                    ]),
                ]),
            Section::make('Custom comparison')
                ->description('Only visible when “Manual range” is selected.')
                ->schema([
                    Grid::make([
                        'md' => 2,
                    ])->schema([
                        DatePicker::make('comparison_start')
                            ->label('Comparison start')
                            ->displayFormat('M j, Y')
                            ->native(false)
                            ->closeOnDateSelection()
                            ->prefixIcon(Heroicon::CalendarDays)
                            ->visible(fn ($get): bool => $get('comparison_mode') === 'custom'),
                        DatePicker::make('comparison_end')
                            ->label('Comparison end')
                            ->displayFormat('M j, Y')
                            ->native(false)
                            ->closeOnDateSelection()
                            ->prefixIcon(Heroicon::CalendarDays)
                            ->visible(fn ($get): bool => $get('comparison_mode') === 'custom'),
                    ]),
                ]),
        ]);
    }

    public function persistsFiltersInSession(): bool
    {
        return false;
    }

    protected function applyPreset(?string $state, Set $set): void
    {
        if ($state === 'custom' || blank($state)) {
            return;
        }

        $now = now();
        $end = $now->copy()->toDateString();

        $start = match ($state) {
            '7' => $now->copy()->subDays(6),
            '90' => $now->copy()->subDays(89),
            'ytd' => $now->copy()->startOfYear(),
            default => $now->copy()->subDays(29),
        };

        $set('range_start', $start->toDateString());
        $set('range_end', $end);
    }

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            BusinessPulseStats::class,
            RevenueOrdersTrend::class,
            TopProductsTable::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            '@2xl' => 2,
            '!@lg' => 1,
        ];
    }
}
