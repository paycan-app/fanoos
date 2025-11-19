<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Ask a Question</x-slot>
        <x-slot name="description">Enter your question in natural language and the AI will generate an SQL query to answer it.</x-slot>

        <form wire:submit="submitQuery">
            <div class="space-y-4">
                <div>
                    <label for="question" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Your Question
                    </label>
                    <textarea
                        wire:model="question"
                        id="question"
                        rows="3"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                        placeholder="e.g., Show me top 10 customers by total spending, or List all orders from last month"
                    ></textarea>
                    @error('question')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-3">
                    <x-filament::button type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submitQuery">
                            <x-filament::icon
                                icon="heroicon-m-sparkles"
                                class="w-4 h-4 mr-2"
                            />
                            Generate & Run Query
                        </span>
                        <span wire:loading wire:target="submitQuery">
                            <x-filament::loading-indicator class="w-4 h-4 mr-2" />
                            Processing...
                        </span>
                    </x-filament::button>

                    @if($this->hasResults)
                        <x-filament::button color="gray" wire:click="clearResults">
                            Clear Results
                        </x-filament::button>
                    @endif
                </div>
            </div>
        </form>
    </x-filament::section>

    @if($this->hasResults)
        <x-filament::section>
            <x-slot name="heading">Query Results</x-slot>

            @if($this->intent || $this->notes)
                <div class="mb-4 space-y-3">
                    @if($this->intent)
                        <div class="flex items-start gap-2">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300"></span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $this->intent }}</span>
                        </div>
                    @endif

                    @if($this->notes)
                        <div class="">
                            <p class="text-sm text-primary-700 dark:text-primary-300">{{ $this->notes }}</p>
                        </div>
                    @endif
                </div>
            @endif

            @if(count($this->queryResults) > 0)
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                @foreach($this->queryColumns as $column)
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ str($column)->title()->replace('_', ' ') }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->queryResults as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    @foreach($this->queryColumns as $column)
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                            @php
                                                $value = $row[$column] ?? '';
                                                if (is_array($value) || is_object($value)) {
                                                    $value = json_encode($value);
                                                }
                                            @endphp
                                            {{ $value }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Showing {{ count($this->queryResults) }} results
                </div>
            @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <p>The query returned no results.</p>
                </div>
            @endif
        </x-filament::section>
    @endif

    @if($this->intent || $this->notes || $this->generatedSql)
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">AI Response</x-slot>

            <div class="space-y-4">
                @if($this->intent)
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Intent</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $this->intent }}</p>
                    </div>
                @endif

                @if($this->notes)
                    <div class="rounded-lg bg-primary-50 dark:bg-primary-500/10 p-4 border border-primary-200 dark:border-primary-500/20">
                        <h4 class="text-sm font-semibold text-primary-800 dark:text-primary-400 mb-1">Notes</h4>
                        <p class="text-sm text-primary-700 dark:text-primary-300">{{ $this->notes }}</p>
                    </div>
                @endif

                @if($this->generatedSql)
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Generated SQL</h4>
                        <pre class="text-xs bg-gray-100 dark:bg-gray-800 p-3 rounded-lg overflow-x-auto"><code class="text-gray-800 dark:text-gray-200">{{ $this->generatedSql }}</code></pre>
                    </div>
                @endif

                @if(!empty($this->usedTables))
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Tables Used</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach($this->usedTables as $table)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                    {{ $table }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
