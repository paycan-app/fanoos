@php
    use App\Models\Customer;

    $filterType = $filterType ?? 'all';
    $filterConfig = $filterConfig ?? [];

    // Build the query based on filter type
    $query = Customer::query();

    if ($filterType === 'segment' && !empty($filterConfig['segments'])) {
        $query->whereIn('segment', $filterConfig['segments']);
    } elseif ($filterType === 'individual' && !empty($filterConfig['customer_ids'])) {
        $query->where('id', $filterConfig['customer_ids']);
    } elseif ($filterType === 'custom') {
        if (!empty($filterConfig['segments'])) {
            $query->whereIn('segment', $filterConfig['segments']);
        }

        if (!empty($filterConfig['countries'])) {
            $query->whereIn('country', $filterConfig['countries']);
        }

        if (!empty($filterConfig['labels'])) {
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
    }

    $totalRecipients = $query->count();
    $recipients = $query->select('id', 'first_name', 'last_name', 'email', 'phone', 'segment', 'country')
        ->orderBy('first_name')
        ->limit(100)
        ->get();
@endphp

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold">Recipients Preview</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Total: <strong>{{ number_format($totalRecipients) }}</strong> customer{{ $totalRecipients !== 1 ? 's' : '' }}
                @if($totalRecipients > 100)
                    <span class="text-xs">(showing first 100)</span>
                @endif
            </p>
        </div>
    </div>

    @if($recipients->count() > 0)
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Name
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Email
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Phone
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Segment
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Country
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($recipients as $recipient)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $recipient->first_name }} {{ $recipient->last_name }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $recipient->email }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $recipient->phone ?? '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $recipient->segment ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $recipient->country ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No recipients found</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Please adjust your filter settings to select recipients for this campaign.
            </p>
        </div>
    @endif
</div>
