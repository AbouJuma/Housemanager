<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1a1a1a; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { text-align: left; padding: 5px 8px; border-bottom: 1px solid #ddd; }
        th { background: #f2f2f2; }
        .text-end { text-align: right; }
        .total-row { font-weight: bold; border-top: 2px solid #333; }
    </style>
</head>
<body>
    <h1>{{ __('AR Aging') }}</h1>
    <p>Generated {{ now()->toFormattedDateString() }} &middot; Total outstanding: {{ number_format($grand_total, 2) }}</p>
    @php($bucketLabels = ['current' => 'Current', '1_30' => '1-30 Days', '31_60' => '31-60 Days', '61_90' => '61-90 Days', 'over_90' => 'Over 90 Days'])
    <table>
        <thead>
            <tr>
                <th>{{ __('Bucket') }}</th>
                <th>Invoice #</th>
                <th>{{ __('Tenant') }}</th>
                <th>{{ __('Due Date') }}</th>
                <th class="text-end">{{ __('Days Overdue') }}</th>
                <th class="text-end">{{ __('Balance') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($buckets as $key => $invoices)
                @foreach ($invoices as $invoice)
                    <tr>
                        <td>{{ $bucketLabels[$key] }}</td>
                        <td>{{ $invoice['invoice_number'] }}</td>
                        <td>{{ $invoice['tenant'] }}</td>
                        <td>{{ $invoice['due_date'] }}</td>
                        <td class="text-end">{{ $invoice['days_overdue'] }}</td>
                        <td class="text-end">{{ number_format($invoice['balance'], 2) }}</td>
                    </tr>
                @endforeach
            @endforeach
            <tr class="total-row">
                <td colspan="5">Grand Total</td>
                <td class="text-end">{{ number_format($grand_total, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
