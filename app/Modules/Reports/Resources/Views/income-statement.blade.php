@extends('layouts.app')

@section('title', 'Income Statement')

@section('page-heading')
    <h1 class="h4 mb-0">Income Statement</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form method="GET" class="d-flex gap-2 align-items-center">
            <input type="date" name="start_date" value="{{ $start_date->toDateString() }}" class="form-control form-control-sm">
            <span class="text-secondary">to</span>
            <input type="date" name="end_date" value="{{ $end_date->toDateString() }}" class="form-control form-control-sm">
            <button type="submit" class="btn btn-sm btn-outline-primary">Apply</button>
        </form>
        <div class="d-flex gap-2">
            @php($exportParams = ['start_date' => $start_date->toDateString(), 'end_date' => $end_date->toDateString()])
            <a href="{{ route('reports.income-statement.export', ['format' => 'pdf', ...$exportParams]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</a>
            <a href="{{ route('reports.income-statement.export', ['format' => 'xlsx', ...$exportParams]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a>
            <a href="{{ route('reports.income-statement.export', ['format' => 'csv', ...$exportParams]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-csv me-1"></i> CSV</a>
        </div>
    </div>

    <p class="text-secondary small">{{ $start_date->toFormattedDateString() }} &ndash; {{ $end_date->toFormattedDateString() }}</p>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">Income</h2>
            <table class="table align-middle">
                <tbody>
                    @forelse ($income as $account)
                        <tr>
                            <td>{{ $account['code'] }} {{ $account['name'] }}</td>
                            <td class="text-end">{{ number_format($account['amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-secondary small">No income posted in this period.</td></tr>
                    @endforelse
                    <tr class="fw-semibold border-top">
                        <td>Total Income</td>
                        <td class="text-end">{{ number_format($total_income, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <h2 class="h6 mb-3 mt-4">Expenses</h2>
            <table class="table align-middle">
                <tbody>
                    @forelse ($expenses as $account)
                        <tr>
                            <td>{{ $account['code'] }} {{ $account['name'] }}</td>
                            <td class="text-end">{{ number_format($account['amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-secondary small">No expenses posted in this period.</td></tr>
                    @endforelse
                    <tr class="fw-semibold border-top">
                        <td>Total Expenses</td>
                        <td class="text-end">{{ number_format($total_expenses, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <hr>
            <div class="d-flex justify-content-between fs-5 fw-semibold">
                <span>Net Income</span>
                <span class="{{ $net_income < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($net_income, 2) }}</span>
            </div>
        </div>
    </div>
@endsection
