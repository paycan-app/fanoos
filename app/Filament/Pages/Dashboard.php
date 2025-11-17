<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BusinessPulseStats;
use App\Filament\Widgets\RevenueOrdersTrend;
use App\Filament\Widgets\TopProductsTable;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
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
            Flex::make([
                Flex::make([
                    'default' => 1,
                    'lg' => 3,
                ])->schema([
                    ToggleButtons::make('range_preset')
                        ->options([
                            '7' => '7d',
                            '30' => '30d',
                            'ytd' => 'YTD',
                            'custom' => 'Custom',
                        ])
                        ->default('30')
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            $this->applyPreset($state, $set);
                        })
                        ->inline()
                        ->grouped()
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 3,
                        ]),
                    DatePicker::make('range_start')
                        ->label('Start date')
                        ->displayFormat('M j, Y')
                        ->native(false)
                        ->closeOnDateSelection()
                        ->default(now()->subDays(29))
                        ->maxDate(now())
                        ->prefixIcon(Heroicon::CalendarDays)
                        ->visible(fn ($get): bool => $get('range_preset') === 'custom')
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 1,
                        ]),
                    DatePicker::make('range_end')
                        ->label('End date')
                        ->displayFormat('M j, Y')
                        ->native(false)
                        ->closeOnDateSelection()
                        ->default(now())
                        ->maxDate(now())
                        ->prefixIcon(Heroicon::CalendarDays)
                        ->visible(fn ($get): bool => $get('range_preset') === 'custom')
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 1,
                        ]),
                ])
                    ->grow(),
                Flex::make([
                    'default' => 1,
                    'lg' => 3,
                ])->schema([
                    Select::make('comparison_mode')
                        ->options([
                            'previous_period' => 'Previous period',
                            'previous_year' => 'Previous year',
                            'custom' => 'Custom range',
                        ])
                        ->default('previous_period')
                        ->live()
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 3,
                        ]),
                    DatePicker::make('comparison_start')
                        ->label('Start')
                        ->displayFormat('M j, Y')
                        ->native(false)
                        ->closeOnDateSelection()
                        ->prefixIcon(Heroicon::CalendarDays)
                        ->visible(fn ($get): bool => $get('comparison_mode') === 'custom')
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 1,
                        ]),
                    DatePicker::make('comparison_end')
                        ->label('End')
                        ->displayFormat('M j, Y')
                        ->native(false)
                        ->closeOnDateSelection()
                        ->prefixIcon(Heroicon::CalendarDays)
                        ->visible(fn ($get): bool => $get('comparison_mode') === 'custom')
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 1,
                        ]),
                ])
                    ->grow(false)
                    ->extraAttributes([
                        'class' => 'lg:w-1/2 lg:ml-auto',
                    ]),
            ])->from('lg'),
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
