<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1a1a1a; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        h2 { font-size: 13px; margin-top: 18px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 5px 8px; border-bottom: 1px solid #ddd; }
        .text-end { text-align: right; }
        .total-row { font-weight: bold; border-top: 2px solid #333; }
        .net { font-size: 14px; font-weight: bold; margin-top: 16px; }
    </style>
</head>
<body>
    <h1>Income Statement</h1>
    <p>{{ $start_date->toFormattedDateString() }} &ndash; {{ $end_date->toFormattedDateString() }}</p>

    <h2>Income</h2>
    <table>
        <tbody>
            @foreach ($income as $account)
                <tr>
                    <td>{{ $account['code'] }} {{ $account['name'] }}</td>
                    <td class="text-end">{{ number_format($account['amount'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td>Total Income</td>
                <td class="text-end">{{ number_format($total_income, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Expenses</h2>
    <table>
        <tbody>
            @foreach ($expenses as $account)
                <tr>
                    <td>{{ $account['code'] }} {{ $account['name'] }}</td>
                    <td class="text-end">{{ number_format($account['amount'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td>Total Expenses</td>
                <td class="text-end">{{ number_format($total_expenses, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="net">
        <tr>
            <td>Net Income</td>
            <td class="text-end">{{ number_format($net_income, 2) }}</td>
        </tr>
    </table>
</body>
</html>
