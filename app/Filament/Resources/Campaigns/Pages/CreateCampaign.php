<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Filament\Resources\Campaigns\CampaignResource;
use App\Models\Customer;
use App\Services\CampaignService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class CreateCampaign extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = CampaignResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Wizard::make([
                    Step::make('Basic Information')
                        ->description('Campaign details and content')
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->label('Campaign Name')
                                ->placeholder('e.g., Holiday Sale 2025'),

                            Radio::make('channel')
                                ->required()
                                ->options([
                                    'email' => 'Email',
                                    'sms' => 'SMS',
                                ])
                                ->descriptions([
                                    'email' => 'Send via email using Mailgun',
                                    'sms' => 'Send via SMS using Twilio',
                                ])
                                ->live()
                                ->default('email'),

                            TextInput::make('subject')
                                ->required(fn ($get) => $get('channel') === 'email')
                                ->hidden(fn ($get) => $get('channel') !== 'email')
                                ->maxLength(255)
                                ->label('Email Subject')
                                ->placeholder('e.g., Exclusive Offer Just For You!'),

                            RichEditor::make('content')
                                ->label('Message Content')
                                ->visible(fn ($get) => $get('channel') === 'email')
                                ->toolbarButtons([
                                    'bold',
                                    'italic',
                                    'link',
                                    'bulletList',
                                    'orderedList',
                                ])
                                ->placeholder('Write your email message here. Use {{first_name}}, {{last_name}}, {{segment}}, etc.')
                                ->helperText(new HtmlString('Available variables: <code>{{first_name}}</code>, <code>{{last_name}}</code>, <code>{{email}}</code>, <code>{{segment}}</code>, <code>{{monetary}}</code>, <code>{{frequency}}</code>'))
                                ->required(fn ($get) => $get('channel') === 'email'),

                            Textarea::make('content')
                                ->required(fn ($get) => $get('channel') === 'sms')
                                ->label('SMS Message')
                                ->visible(fn ($get) => $get('channel') === 'sms')
                                ->rows(4)
                                ->maxLength(160)
                                ->placeholder('Write your SMS message here (max 160 characters)')
                                ->helperText(fn (?string $state) => (160 - strlen($state ?? '')).' characters remaining')
                                ->live(onBlur: true),
                        ]),

                    Step::make('Recipients')
                        ->description('Select who receives this campaign')
                        ->schema([
                            Radio::make('filter_type')
                                ->required()
                                ->options([
                                    'all' => 'All Customers',
                                    'segment' => 'By RFM Segment',
                                    'custom' => 'Custom Filters',
                                    'individual' => 'Individual Customer',
                                ])
                                ->descriptions([
                                    'all' => 'Send to every customer in the database',
                                    'segment' => 'Target specific RFM segments',
                                    'custom' => 'Create advanced filter combinations',
                                    'individual' => 'Send to a specific customer',
                                ])
                                ->live()
                                ->default('all'),

                            CheckboxList::make('filter_config.segments')
                                ->label('Select Segments')
                                ->visible(fn ($get) => $get('filter_type') === 'segment')
                                ->options(fn () => app(CampaignService::class)->getAvailableSegments())
                                ->required(fn ($get) => $get('filter_type') === 'segment')
                                ->columns(2)
                                ->live(),

                            Select::make('filter_config.customer_ids')
                                ->label('Select Customer')
                                ->visible(fn ($get) => $get('filter_type') === 'individual')
                                ->searchable()
                                ->getSearchResultsUsing(function (string $search) {
                                    return Customer::query()
                                        ->where(function ($query) use ($search) {
                                            $query->where('first_name', 'like', "%{$search}%")
                                                ->orWhere('last_name', 'like', "%{$search}%")
                                                ->orWhere('email', 'like', "%{$search}%");
                                        })
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn ($customer) => [
                                            $customer->id => $customer->first_name.' '.$customer->last_name.' ('.$customer->email.')',
                                        ]);
                                })
                                ->getOptionLabelUsing(function (?string $value): ?string {
                                    if ($value === null) {
                                        return null;
                                    }

                                    $customer = Customer::find($value);

                                    return $customer ? $customer->first_name.' '.$customer->last_name.' ('.$customer->email.')' : null;
                                })
                                ->required(fn ($get) => $get('filter_type') === 'individual'),

                            Section::make('Custom Filters')
                                ->visible(fn ($get) => $get('filter_type') === 'custom')
                                ->schema([
                                    CheckboxList::make('filter_config.segments')
                                        ->label('RFM Segments')
                                        ->options(fn () => app(CampaignService::class)->getAvailableSegments())
                                        ->columns(2)
                                        ->live(),

                                    CheckboxList::make('filter_config.countries')
                                        ->label('Countries')
                                        ->options(fn () => array_combine(
                                            app(CampaignService::class)->getAvailableCountries(),
                                            app(CampaignService::class)->getAvailableCountries()
                                        ))
                                        ->columns(2)
                                        ->searchable()
                                        ->live(),

                                    CheckboxList::make('filter_config.labels')
                                        ->label('Customer Labels')
                                        ->options(fn () => array_combine(
                                            app(CampaignService::class)->getAvailableLabels(),
                                            app(CampaignService::class)->getAvailableLabels()
                                        ))
                                        ->columns(2)
                                        ->live(),

                                    DateTimePicker::make('filter_config.created_after')
                                        ->label('Customer Created After')
                                        ->live(),

                                    DateTimePicker::make('filter_config.created_before')
                                        ->label('Customer Created Before')
                                        ->live(),
                                ]),

                            Placeholder::make('recipient_count')
                                ->label('Estimated Recipients')
                                ->content(function ($get) {
                                    $filterType = $get('filter_type');
                                    $filterConfig = $get('filter_config') ?? [];

                                    if ($filterType === 'all') {
                                        return Customer::count().' customers';
                                    }

                                    if ($filterType === 'segment' && isset($filterConfig['segments'])) {
                                        return Customer::whereIn('segment', $filterConfig['segments'])->count().' customers';
                                    }

                                    if ($filterType === 'individual' && isset($filterConfig['customer_ids'])) {
                                        return '1 customer';
                                    }

                                    if ($filterType === 'custom') {
                                        $query = Customer::query();

                                        if (! empty($filterConfig['segments'])) {
                                            $query->whereIn('segment', $filterConfig['segments']);
                                        }

                                        if (! empty($filterConfig['countries'])) {
                                            $query->whereIn('country', $filterConfig['countries']);
                                        }

                                        if (! empty($filterConfig['labels'])) {
                                            $query->where(function ($q) use ($filterConfig) {
                                                foreach ($filterConfig['labels'] as $label) {
                                                    $q->orWhereJsonContains('labels', $label);
                                                }
                                            });
                                        }

                                        if (isset($filterConfig['created_after'])) {
                                            $query->where('created_at', '>=', $filterConfig['created_after']);
                                        }

                                        if (isset($filterConfig['created_before'])) {
                                            $query->where('created_at', '<=', $filterConfig['created_before']);
                                        }

                                        return $query->count().' customers';
                                    }

                                    return '0 customers';
                                }),
                        ]),

                    Step::make('Review Recipients')
                        ->description('Review the list of customers')
                        ->schema([
                            ViewField::make('recipients_table')
                                ->label('')
                                ->view('filament.forms.components.recipients-table')
                                ->viewData(fn ($get) => [
                                    'filterType' => $get('filter_type'),
                                    'filterConfig' => $get('filter_config') ?? [],
                                ]),
                        ]),

                    Step::make('Schedule & Send')
                        ->description('Test and launch your campaign')
                        ->schema([
                            Toggle::make('schedule_later')
                                ->label('Schedule for Later')
                                ->live()
                                ->default(false),

                            DateTimePicker::make('scheduled_at')
                                ->label('Schedule Date & Time')
                                ->visible(fn ($get) => $get('schedule_later'))
                                ->required(fn ($get) => $get('schedule_later'))
                                ->minDate(now())
                                ->native(false),

                            Section::make('Test Message')
                                ->description(fn ($get) => 'Send a test '.($get('channel') === 'email' ? 'email' : 'SMS').' before launching the campaign')
                                ->schema([
                                    // fix chpreview messqage by removing ../../channel and use 'channel'
                                    TextInput::make('test_recipient')
                                        ->label(fn ($get) => $get('channel') === 'email' ? 'Test Email Address' : 'Test Phone Number')
                                        ->placeholder(fn ($get) => $get('channel') === 'email' ? 'test@example.com' : '+1234567890')
                                        ->helperText(fn ($get) => 'Enter '.($get('channel') === 'email' ? 'an email address' : 'a phone number (E.164 format)').' to receive a test message')
                                        ->rule(fn ($get) => $get('channel') === 'email' ? 'email' : 'regex:/^\+[1-9]\d{1,14}$/'),
                                ])
                                ->collapsible(),

                            Hidden::make('created_by')
                                ->default(fn () => Auth::id()),

                            Placeholder::make('final_summary')
                                ->label('Campaign Summary')
                                ->content(function ($get) {
                                    $channel = $get('channel');
                                    $name = $get('name');
                                    $filterType = $get('filter_type');

                                    return new HtmlString("
                                        <div class='space-y-2'>
                                            <div><strong>Name:</strong> {$name}</div>
                                            <div><strong>Channel:</strong> ".ucfirst($channel).'</div>
                                            <div><strong>Filter:</strong> '.ucfirst(str_replace('_', ' ', $filterType)).'</div>
                                        </div>
                                    ');
                                }),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    protected function getFormActions(): array
    {
        $actions = [
            Action::make('send_test')
                ->label('Send Test Message')
                ->icon(\Filament\Support\Icons\Heroicon::OutlinedPaperAirplane)
                ->action(function (array $data) {
                    $testRecipient = $data['test_recipient'] ?? null;
                    $channel = $data['channel'] ?? 'email';

                    if (! $testRecipient) {
                        Notification::make()
                            ->warning()
                            ->title('No test recipient')
                            ->body('Please enter a test '.($channel === 'email' ? 'email address' : 'phone number').'.')
                            ->send();

                        return;
                    }

                    // Validate recipient format based on channel
                    if ($channel === 'email' && ! filter_var($testRecipient, FILTER_VALIDATE_EMAIL)) {
                        Notification::make()
                            ->warning()
                            ->title('Invalid email address')
                            ->body('Please enter a valid email address.')
                            ->send();

                        return;
                    }

                    if ($channel === 'sms' && ! preg_match('/^\+[1-9]\d{1,14}$/', $testRecipient)) {
                        Notification::make()
                            ->warning()
                            ->title('Invalid phone number')
                            ->body('Please enter a valid phone number in E.164 format (e.g., +1234567890).')
                            ->send();

                        return;
                    }

                    // Validate content exists
                    $content = $data['content'] ?? '';
                    if (! is_string($content)) {
                        $content = is_array($content) ? '' : (string) $content;
                    }

                    if ($channel === 'email') {
                        $stripped = strip_tags($content);
                        Log::info($stripped, $content);
                        $trimmed = trim($stripped);
                        if (empty($trimmed)) {
                            Notification::make()
                                ->warning()
                                ->title('Missing email content')
                                ->body('Please enter email message content before sending a test.')
                                ->send();

                            return;
                        }
                    } else {
                        if (empty(trim($content))) {
                            Notification::make()
                                ->warning()
                                ->title('Missing SMS content')
                                ->body('Please enter SMS message content before sending a test.')
                                ->send();

                            return;
                        }
                    }

                    // Create temporary campaign for testing
                    $tempCampaign = new \App\Models\Campaign($data);
                    $campaignService = app(CampaignService::class);

                    try {
                        $success = $campaignService->sendTestMessage($tempCampaign, $testRecipient);

                        if ($success) {
                            Notification::make()
                                ->success()
                                ->title('Test message sent!')
                                ->body("Check {$testRecipient} for the test message.")
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Failed to send test message')
                                ->body('Please check your '.($channel === 'email' ? 'email' : 'SMS').' configuration.')
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Failed to send test message')
                            ->body('Error: '.$e->getMessage())
                            ->send();
                    }
                })
                ->color('gray'),
        ];

        // Add launch button before parent actions
        $actions[] = Action::make('launch_now')
            ->label('Launch Campaign Now')
            ->icon(\Filament\Support\Icons\Heroicon::OutlinedPaperAirplane)
            ->requiresConfirmation()
            ->modalHeading('Launch Campaign')
            ->modalDescription(function ($get) {
                $filterType = $get('filter_type');
                $filterConfig = $get('filter_config') ?? [];
                $count = 0;

                if ($filterType === 'all') {
                    $count = \App\Models\Customer::count();
                } elseif ($filterType === 'segment' && isset($filterConfig['segments'])) {
                    $count = \App\Models\Customer::whereIn('segment', $filterConfig['segments'])->count();
                } elseif ($filterType === 'individual' && isset($filterConfig['customer_ids'])) {
                    $count = 1;
                } elseif ($filterType === 'custom') {
                    $query = \App\Models\Customer::query();
                    if (! empty($filterConfig['segments'])) {
                        $query->whereIn('segment', $filterConfig['segments']);
                    }
                    if (! empty($filterConfig['countries'])) {
                        $query->whereIn('country', $filterConfig['countries']);
                    }
                    if (! empty($filterConfig['labels'])) {
                        $query->where(function ($q) use ($filterConfig) {
                            foreach ($filterConfig['labels'] as $label) {
                                $q->orWhereJsonContains('labels', $label);
                            }
                        });
                    }
                    if (isset($filterConfig['created_after'])) {
                        $query->where('created_at', '>=', $filterConfig['created_after']);
                    }
                    if (isset($filterConfig['created_before'])) {
                        $query->where('created_at', '<=', $filterConfig['created_before']);
                    }
                    $count = $query->count();
                }

                return "This will send the campaign to {$count} recipients immediately.";
            })
            ->action(function (array $data) {
                // Validate content before launching
                $channel = $data['channel'] ?? 'email';
                $content = $data['content'] ?? '';

                if (! is_string($content)) {
                    $content = is_array($content) ? '' : (string) $content;
                }

                if ($channel === 'email') {
                    $stripped = strip_tags($content);
                    $trimmed = trim($stripped);
                    if (empty($trimmed)) {
                        Notification::make()
                            ->danger()
                            ->title('Validation Error')
                            ->body('Email message content is required.')
                            ->send();

                        return;
                    }
                } else {
                    if (empty(trim($content))) {
                        Notification::make()
                            ->danger()
                            ->title('Validation Error')
                            ->body('SMS message content is required.')
                            ->send();

                        return;
                    }
                }

                // Create the campaign
                $campaign = $this->mutateFormDataBeforeCreate($data);
                $campaign['status'] = 'draft';
                $campaign['created_by'] = Auth::id();

                $record = $this->handleRecordCreation($campaign);

                // Immediately process the campaign
                $campaignService = app(CampaignService::class);
                $campaignService->processCampaign($record);

                Notification::make()
                    ->success()
                    ->title('Campaign launched!')
                    ->body('The campaign is now being sent to recipients.')
                    ->send();

                $this->redirect($this->getResource()::getUrl('view', ['record' => $record]));
            })
            ->color('success')
            ->visible(fn ($get) => ! ($get('schedule_later') ?? false));

        // Add parent actions (like "Create" button) at the end
        $actions = array_merge($actions, parent::getFormActions());

        return $actions;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validate email content is not empty HTML
        if (($data['channel'] ?? null) === 'email') {
            $content = $data['content'] ?? '';
            $stripped = strip_tags($content);
            $trimmed = trim($stripped);

            if (empty($trimmed)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'data.content' => 'The email message content cannot be empty.',
                ]);
            }
        }

        $data['status'] = $data['schedule_later'] ?? false ? 'scheduled' : 'draft';
        unset($data['schedule_later'], $data['test_recipient']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! $this->record->scheduled_at) {
            Notification::make()
                ->success()
                ->title('Campaign created')
                ->body('Campaign saved as draft. You can send it from the view page.')
                ->send();
        } else {
            Notification::make()
                ->success()
                ->title('Campaign scheduled')
                ->body('Campaign will be sent at '.$this->record->scheduled_at->format('M d, Y g:i A'))
                ->send();
        }
    }
}
