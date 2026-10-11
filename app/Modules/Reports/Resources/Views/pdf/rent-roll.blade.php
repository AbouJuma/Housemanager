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
    </style>
</head>
<body>
    <h1>{{ __('Rent Roll') }}</h1>
    <p>Generated {{ now()->toFormattedDateString() }}</p>
    <table>
        <thead>
            <tr>
                <th>{{ __('Property') }}</th>
                <th>{{ __('Building') }}</th>
                <th>{{ __('Unit') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Tenant') }}</th>
                <th>{{ __('Lease Start') }}</th>
                <th>{{ __('Lease End') }}</th>
                <th class="text-end">{{ __('Rent') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['property'] }}</td>
                    <td>{{ $row['building'] }}</td>
                    <td>{{ $row['unit'] }}</td>
                    <td>{{ ucfirst($row['status']) }}</td>
                    <td>{{ $row['tenant'] ?? '-' }}</td>
                    <td>{{ $row['lease_start'] ?? '-' }}</td>
                    <td>{{ $row['lease_end'] ?? '-' }}</td>
                    <td class="text-end">{{ number_format($row['rent_amount'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
