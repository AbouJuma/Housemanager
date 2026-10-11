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
    <h1>{{ __('Occupancy') }}</h1>
    <p>Generated {{ now()->toFormattedDateString() }}</p>
    <table>
        <thead>
            <tr>
                <th>{{ __('Property') }}</th>
                <th class="text-end">{{ __('Total Units') }}</th>
                <th class="text-end">{{ __('Occupied') }}</th>
                <th class="text-end">{{ __('Vacant') }}</th>
                <th class="text-end">{{ __('Occupancy Rate') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['property'] }}</td>
                    <td class="text-end">{{ $row['total_units'] }}</td>
                    <td class="text-end">{{ $row['occupied'] }}</td>
                    <td class="text-end">{{ $row['vacant'] }}</td>
                    <td class="text-end">{{ $row['occupancy_rate'] }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
