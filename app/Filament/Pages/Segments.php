<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Services\RfmService;
use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Segments extends Page implements \Filament\Tables\Contracts\HasTable
{
    use \Filament\Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationLabel = 'Segments';
    protected static ?string $title = 'Segments';
    protected static UnitEnum|string|null $navigationGroup = 'Customers';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    public function mount(): void
    {
        // Recalculate segments using saved settings to ensure data is fresh
        app(RfmService::class)->calculateSegments();
    }

    public function content(Schema $schema): Schema
    {
        $settings = app(GeneralSettings::class);

        return $schema->components([
            Section::make('Current RFM Settings')
                ->schema([
                    Grid::make(3)->schema([
                        \Filament\Schemas\Components\Text::make('Segments: ' . $settings->rfm_segments)->badge(true),
                        \Filament\Schemas\Components\Text::make('Bins: ' . $settings->rfm_bins)->badge(true),
                        \Filament\Schemas\Components\Text::make('Timeframe (days): ' . $settings->rfm_timeframe_days)->badge(true),
                    ]),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Customer::query()
                    ->selectRaw('segment, COUNT(*) as customers')
                    ->whereNotNull('segment')
                    ->groupBy('segment')
            )
            ->columns([
                Tables\Columns\TextColumn::make('segment')->badge()->label('Segment')->sortable(),
                Tables\Columns\TextColumn::make('customers')->label('Customers')->sortable(),
            ])
            ->recordActions([])
            ->headerActions([])
            ->emptyStateActions([]);
    }
}