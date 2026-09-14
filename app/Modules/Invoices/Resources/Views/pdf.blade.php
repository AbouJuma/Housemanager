<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .invoice-title {
            font-size: 22px;
            font-weight: bold;
            color: #333;
        }
        .invoice-number {
            font-size: 16px;
            color: #666;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .info-label {
            font-weight: bold;
            color: #555;
            font-size: 10px;
            text-transform: uppercase;
        }
        .info-value {
            color: #333;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th {
            background-color: #f5f5f5;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #333;
            font-size: 10px;
            text-transform: uppercase;
        }
        .table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .table .text-right {
            text-align: right;
        }
        .total-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 15px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            width: 250px;
            margin-bottom: 8px;
        }
        .total-label {
            font-weight: bold;
        }
        .total-value {
            font-weight: bold;
        }
        .grand-total {
            font-size: 14px;
            border-top: 2px solid #333;
            padding-top: 8px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
        }
        .status-paid {
            color: #28a745;
            font-weight: bold;
        }
        .status-pending {
            color: #ffc107;
            font-weight: bold;
        }
        .status-overdue {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">#{{ $invoice->invoice_number }}</div>
            </div>
            <div style="text-align: right;">
                <div><strong>Date:</strong> {{ $invoice->issue_date->format('M j, Y') }}</div>
                <div><strong>Due Date:</strong> {{ $invoice->due_date->format('M j, Y') }}</div>
                <div><strong>Status:</strong>
                    @if($invoice->status === 'paid')
                        <span class="status-paid">PAID</span>
                    @elseif($invoice->status === 'sent' && $invoice->due_date->isPast())
                        <span class="status-overdue">OVERDUE</span>
                    @else
                        <span class="status-pending">{{ strtoupper($invoice->status) }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="info-section">
            <div class="info-grid">
                @if($invoice->lease)
                    <div>
                        <div class="info-label">Bill To</div>
                        <div class="info-value"><strong>{{ $invoice->lease->tenant->name }}</strong></div>
                        @if($invoice->lease->tenant->email)
                        <div class="info-value">{{ $invoice->lease->tenant->email }}</div>
                        @endif
                        <div class="info-value">{{ $invoice->lease->tenant->phone }}</div>
                    </div>
                    <div>
                        <div class="info-label">Property</div>
                        <div class="info-value"><strong>Unit {{ $invoice->lease->unit->unit_number }}</strong></div>
                        <div class="info-value">{{ $invoice->lease->unit->building->name }}</div>
                        <div class="info-value">{{ $invoice->lease->unit->building->property->name }}</div>
                    </div>
                @endif

                @if($invoice->sale)
                    <div>
                        <div class="info-label">Bill To</div>
                        <div class="info-value"><strong>{{ $invoice->sale->buyer->name }}</strong></div>
                        @if($invoice->sale->buyer->email)
                        <div class="info-value">{{ $invoice->sale->buyer->email }}</div>
                        @endif
                        <div class="info-value">{{ $invoice->sale->buyer->phone }}</div>
                    </div>
                    <div>
                        <div class="info-label">Property</div>
                        <div class="info-value"><strong>Unit {{ $invoice->sale->unit->unit_number }}</strong></div>
                        <div class="info-value">{{ $invoice->sale->unit->building->name }}</div>
                        <div class="info-value">{{ $invoice->sale->unit->building->property->name }}</div>
                    </div>
                @endif
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Quantity</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-row">
                <div class="total-label">Subtotal:</div>
                <div class="total-value">{{ number_format($invoice->total_amount, 2) }}</div>
            </div>
            <div class="total-row">
                <div class="total-label">Paid:</div>
                <div class="total-value">{{ number_format($invoice->amount_paid, 2) }}</div>
            </div>
            <div class="total-row grand-total">
                <div class="total-label">Balance Due:</div>
                <div class="total-value">{{ number_format($invoice->balanceDue(), 2) }}</div>
            </div>
        </div>

        @if($invoice->notes)
        <div class="info-section" style="margin-top: 20px;">
            <div class="info-label">Notes</div>
            <div class="info-value">{{ $invoice->notes }}</div>
        </div>
        @endif

        <div class="footer">
            <p>Thank you for your business!</p>
            <p>Generated on {{ now()->format('M j, Y g:i A') }}</p>
        </div>
    </div>
</body>
</html>
