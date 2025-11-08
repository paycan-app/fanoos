<table class="min-w-full text-sm">
    <thead>
        <tr class="border-b">
            <th class="text-left py-2 pr-4">Segment</th>
            <th class="text-right py-2 pr-4">Customers</th>
            <th class="text-right py-2 pr-4">Avg Monetary</th>
            <th class="text-right py-2 pr-4">Avg Frequency</th>
            <th class="text-right py-2">Avg Recency (days)</th>
        </tr>
    </thead>
    <tbody>
    @forelse(($stats ?? []) as $row)
        <tr class="border-b">
            <td class="py-2 pr-4">{{ $row['segment'] }}</td>
            <td class="py-2 pr-4 text-right">{{ number_format($row['customers']) }}</td>
            <td class="py-2 pr-4 text-right">{{ number_format($row['avg_monetary'], 2) }}</td>
            <td class="py-2 pr-4 text-right">{{ number_format($row['avg_frequency'], 0) }}</td>
            <td class="py-2 text-right">{{ number_format($row['avg_recency'], 0) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class="py-4 text-neutral-500">No stats yet. Click “Calculate Segments”.</td>
        </tr>
    @endforelse
    </tbody>
</table>