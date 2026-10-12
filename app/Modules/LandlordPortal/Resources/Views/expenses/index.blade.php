@extends('landlord-portal::layout')

@section('title', __('Expenses'))

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">{{ __('Expenses') }}</h1>
        @if ($canCreate)
            <a href="{{ route('landlord-portal.expenses.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> {{ __('Add Expense') }}</a>
        @endif
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            @include('partials.period-filter', [
                'route' => 'landlord-portal.expenses.index',
                'period' => $period, 'from' => $from, 'to' => $to,
                'keep' => ['status' => $status, 'property' => $propertyId],
            ])
            <div class="d-flex flex-wrap gap-2 mt-3">
                @foreach (['' => __('All'), 'unpaid' => __('Unpaid'), 'paid' => __('Paid')] as $value => $label)
                    <a href="{{ route('landlord-portal.expenses.index', array_filter(['period' => $period, 'from' => $period ? null : $from, 'to' => $period ? null : $to, 'property' => $propertyId, 'status' => $value])) }}"
                       class="btn btn-sm {{ (string) $status === (string) $value ? 'btn-secondary' : 'btn-outline-secondary' }}">{{ $label }}</a>
                @endforeach
                @if ($properties->count() > 1)
                    <form method="GET" class="ms-auto">
                        @foreach (array_filter(['period' => $period, 'from' => $period ? null : $from, 'to' => $period ? null : $to, 'status' => $status]) as $name => $value)
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endforeach
                        <select name="property" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">{{ __('All properties') }}</option>
                            @foreach ($properties as $property)
                                <option value="{{ $property->id }}" @selected($propertyId === $property->id)>{{ $property->name }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-secondary small">{{ __('Total spent') }}</div><div class="h5 mb-0">{{ number_format($total, 2) }}</div>
        </div></div></div>
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-secondary small">{{ __('Still unpaid') }}</div><div class="h5 mb-0 {{ $unpaid > 0 ? 'text-danger' : '' }}">{{ number_format($unpaid, 2) }}</div>
        </div></div></div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($expenses->isEmpty())
                <p class="text-secondary small mb-0">{{ __('No expenses in this period.') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Property') }}</th>
                                <th>{{ __('Category') }}</th>
                                <th class="text-end">{{ __('Amount') }}</th>
                                <th>{{ __('Status') }}</th>
                                @if ($canUpdate)<th></th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($expenses as $expense)
                                <tr class="{{ $expense->status === 'voided' ? 'text-decoration-line-through text-secondary' : '' }}">
                                    <td class="small">{{ $expense->expense_date->format('d M Y') }}</td>
                                    <td>{{ $expense->description }}@if ($expense->vendor_name)<div class="small text-secondary">{{ $expense->vendor_name }}</div>@endif</td>
                                    <td class="small text-secondary">{{ $expense->property->name ?? '—' }}</td>
                                    <td>{{ $expense->account->name ?? '—' }}</td>
                                    <td class="text-end">{{ number_format($expense->amount, 2) }}</td>
                                    <td>
                                        @if ($expense->status === 'voided')
                                            <span class="badge text-bg-secondary">{{ __('Voided') }}</span>
                                        @else
                                            <span class="badge text-bg-{{ $expense->payment_status === 'paid' ? 'success' : 'warning' }}">{{ __(ucfirst($expense->payment_status)) }}</span>
                                        @endif
                                    </td>
                                    @if ($canUpdate)
                                        <td class="text-end">
                                            @if ($expense->status !== 'voided')
                                                <a href="{{ route('landlord-portal.expenses.edit', $expense) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}"><i class="bi bi-pencil"></i></a>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $expenses->links() }}</div>
            @endif
        </div>
    </div>
@endsection
