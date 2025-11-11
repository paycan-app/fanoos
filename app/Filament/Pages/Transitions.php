<?php

namespace App\Filament\Pages;

use App\Services\RfmService;
use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Transitions extends Page
{
    protected static ?string $navigationLabel = 'Transitions';
    protected static ?string $title = 'Transitions';
    protected static UnitEnum|string|null $navigationGroup = 'Customers';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    public array $labels = [];
    public array $matrix = [];
    public int $total = 0;
    public ?string $message = null;

    public ?string $asOfDateA = null;
    public ?string $asOfDateB = null;

    public function mount(): void
    {
        // Removed: rfm_enable gate check and message
        // $settings = app(GeneralSettings::class);
        // if (! $settings->rfm_enable) {
        //     $this->message = 'RFM is disabled in settings.';
        //     return;
        // }

        $this->asOfDateA = now()->subYear()->toDateString();
        $this->asOfDateB = now()->toDateString();

        $this->analyze();
    }

    public function content(Schema $schema): Schema
    {
        $settings = app(GeneralSettings::class);

        return $schema->components([
            Section::make('Compare Two Periods')
                ->description('Pick two reference dates. Each period uses your saved timeframe setting.')
                ->schema([
                    Form::make()->schema([
                        DatePicker::make('asOfDateA')
                            ->label('Period A as of')
                            ->required(),
                        DatePicker::make('asOfDateB')
                            ->label('Period B as of')
                            ->required(),
                    ]),
                ])
                ->footerActions([
                    Action::make('analyze_transitions')
                        ->label('Analyze Transitions')
                        ->icon(\Filament\Support\Icons\Heroicon::ChartBar)
                        ->color('primary')
                        ->action(fn () => $this->analyze()),
                ]),
            Section::make('Results')
                ->description('Heatmap and matrix are shown below.')
                ->schema([
                    \Filament\Schemas\Components\View::make('filament.pages.transitions')
                        ->viewData(fn () => [
                            'labels' => $this->labels,
                            'matrix' => $this->matrix,
                            'total' => $this->total,
                            'message' => $this->message,
                        ]),
                ]),
        ]);
    }

    public function analyze(): void
    {
        $service = app(RfmService::class);
        $result = $service->buildTransitionsMatrixForAsOfDates($this->asOfDateA, $this->asOfDateB);

        $this->labels = $result['labels'];
        $this->matrix = $result['matrix'];
        $this->total = $result['total'];
    }
}