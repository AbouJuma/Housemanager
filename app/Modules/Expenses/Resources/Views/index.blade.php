@extends('layouts.app')

@section('title', 'Expenses')

@section('page-heading')
    <h1 class="h4 mb-0">Expenses</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">Expenses</h1>
            <p class="text-secondary mb-0">{{ $expenses->total() }} {{ Str::plural('expense', $expenses->total()) }}</p>
        </div>
        @can('create', \App\Modules\Expenses\Models\Expense::class)
            <a href="{{ route('expenses.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> New Expense
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-auto">
                    <div class="input-group" style="max-width: 320px;">
                        <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                        <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by description, vendor, or reference…">
                    </div>
                </div>
                <div class="col-auto">
                    <select name="property_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All properties</option>
                        @foreach ($properties as $property)
                            <option value="{{ $property->id }}" @selected((string) request('property_id') === (string) $property->id)>{{ $property->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="payment_status" class="form-select" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        @foreach (['unpaid', 'paid'] as $status)
                            <option value="{{ $status }}" @selected(request('payment_status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if ($expenses->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-cash-stack display-4 text-secondary"></i>
                    <p class="mt-3 mb-1 fw-semibold">No expenses yet</p>
                    <p class="text-secondary">Expenses you record will show up here.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Property</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($expenses as $expense)
                                <tr class="{{ $expense->isVoided() ? 'text-decoration-line-through text-secondary' : '' }}">
                                    <td class="small">{{ $expense->expense_date->format('M j, Y') }}</td>
                                    <td>{{ $expense->description }}</td>
                                    <td class="small text-secondary">{{ $expense->account->name }}</td>
                                    <td class="small text-secondary">{{ $expense->property->name ?? '—' }}</td>
                                    <td class="text-end">{{ number_format($expense->amount, 2) }}</td>
                                    <td>
                                        @if ($expense->isVoided())
                                            <span class="badge text-bg-secondary">Voided</span>
                                        @else
                                            <span class="badge text-bg-{{ $expense->isPaid() ? 'success' : 'warning' }} text-capitalize">{{ $expense->payment_status }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('expenses.show', $expense) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $expenses->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
