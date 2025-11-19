<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use UnitEnum;

class AiQuery extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'AI Query';

    protected static ?string $title = 'AI Query';

    protected static UnitEnum|string|null $navigationGroup = 'Analytics';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.ai-query';

    public ?string $question = '';

    public ?string $generatedSql = null;

    public ?string $intent = null;

    public ?string $notes = null;

    public array $usedTables = [];

    public bool $hasResults = false;

    public array $queryColumns = [];

    public array $queryResults = [];

    public function table(Table $table): Table
    {
        return $table
            ->records(function (): array {
                return $this->queryResults;
            })
            ->columns($this->getTableColumns())
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    protected function getTableColumns(): array
    {
        if (empty($this->queryColumns)) {
            return [
                TextColumn::make('empty')
                    ->label('No Results')
                    ->placeholder('Submit a question to see results'),
            ];
        }

        return collect($this->queryColumns)
            ->map(fn (string $column) => TextColumn::make($column)
                ->label(str($column)->title()->replace('_', ' ')->toString())
                ->wrap()
            )
            ->toArray();
    }

    public function submitQuery(): void
    {
        $this->validate([
            'question' => 'required|string|min:3',
        ]);

        $schema = $this->buildDatabaseSchema();
        $endpoint = config('services.ai_query.endpoint', 'https://34135-lmu3n.irann8n.com/webhook/question-processor');
        $payload = [
            'question' => $this->question,
            'db_schema' => $schema,
        ];

        Log::info('AI Query Request', [
            'endpoint' => $endpoint,
            'question' => $this->question,
            'payload_size' => strlen(json_encode($payload)),
        ]);

        try {
            $response = Http::timeout(30)->post($endpoint, $payload);

            if (! $response->successful()) {
                Log::error('AI Query API Error', [
                    'status' => $response->status(),
                    'status_text' => $response->reason(),
                    'body' => $response->body(),
                    'headers' => $response->headers(),
                    'endpoint' => $endpoint,
                ]);

                Notification::make()
                    ->title('API Error')
                    ->body('Failed to get response from AI service. Status: '.$response->status().' - Check logs for details.')
                    ->danger()
                    ->send();

                return;
            }

            $data = $response->json();

            Log::info('AI Query Response', [
                'ok' => $data['ok'] ?? false,
                'has_sql' => isset($data['sql']),
                'intent' => $data['intent'] ?? null,
            ]);

            if (! ($data['ok'] ?? false)) {
                Log::warning('AI Query Generation Failed', [
                    'error' => $data['error'] ?? 'Unknown error',
                    'response_data' => $data,
                ]);

                Notification::make()
                    ->title('Query Generation Failed')
                    ->body($data['error'] ?? 'The AI could not generate a valid query.')
                    ->danger()
                    ->send();

                return;
            }

            $this->generatedSql = $data['sql'] ?? null;
            $this->intent = $data['intent'] ?? null;
            $this->notes = $data['notes'] ?? null;
            $this->usedTables = $data['used_tables'] ?? [];

            if ($this->generatedSql) {
                $this->executeQuery();
            }

        } catch (\Exception $e) {
            Log::error('AI Query Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'endpoint' => $endpoint,
                'question' => $this->question,
            ]);

            Notification::make()
                ->title('Error')
                ->body('An error occurred: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function executeQuery(): void
    {
        try {
            // Execute query and get results
            $result = DB::select($this->generatedSql);

            if (! empty($result)) {
                // Convert stdClass objects to arrays
                $this->queryResults = array_map(fn ($row) => (array) $row, $result);
                $this->queryColumns = array_keys($this->queryResults[0]);
                $this->hasResults = true;

                Notification::make()
                    ->title('Query Executed')
                    ->body('Found '.count($this->queryResults).' results.')
                    ->success()
                    ->send();
            } else {
                $this->queryResults = [];
                $this->queryColumns = [];
                $this->hasResults = true;

                Notification::make()
                    ->title('No Results')
                    ->body('The query returned no results.')
                    ->warning()
                    ->send();
            }

            // Reset the table to reload with new data
            $this->resetTable();

        } catch (\Exception $e) {
            $this->hasResults = false;
            $this->queryColumns = [];
            $this->queryResults = [];

            Notification::make()
                ->title('Query Execution Failed')
                ->body('Error: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function buildDatabaseSchema(): array
    {
        $tables = [
            'orders' => ['id', 'customer_id', 'created_at', 'total_amount', 'status', 'meta'],
            'customers' => ['id', 'first_name', 'last_name', 'created_at', 'email', 'phone', 'country', 'state', 'city', 'region', 'birthday', 'gender', 'segment', 'labels', 'channel', 'meta'],
            'products' => ['id', 'title', 'category', 'subcategory', 'price', 'brand', 'sku', 'meta'],
            'order_items' => ['id', 'order_id', 'product_id', 'quantity', 'unit_price', 'price', 'created_at'],
            'campaigns' => ['id', 'name', 'channel', 'status', 'subject', 'content', 'filter_type', 'filter_config', 'scheduled_at', 'sent_at', 'created_by', 'total_recipients', 'total_sent', 'total_failed', 'created_at', 'updated_at'],
            'campaign_sends' => ['id', 'campaign_id', 'customer_id', 'status', 'sent_at', 'error_message', 'external_id', 'created_at', 'updated_at'],
            'campaign_events' => ['id', 'campaign_send_id', 'event_type', 'event_data', 'occurred_at', 'created_at', 'updated_at'],
            'failed_import_rows' => ['id', 'data', 'import_id', 'validation_error', 'created_at', 'updated_at'],
        ];

        return [
            'allowed_tables' => array_keys($tables),
            'tables' => $tables,
        ];
    }

    public function clearResults(): void
    {
        $this->question = '';
        $this->generatedSql = null;
        $this->intent = null;
        $this->notes = null;
        $this->usedTables = [];
        $this->hasResults = false;
        $this->queryColumns = [];
        $this->queryResults = [];
        $this->resetTable();
    }
}
