@extends('landlord-portal::layout')

@section('title', __('Expenses'))

@section('content')
    <h1 class="h4 mb-4">{{ __('Expenses') }}</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($expenses->isEmpty())
                <p class="text-secondary small mb-0">No expenses recorded against your properties yet.</p>
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
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($expenses as $expense)
                                <tr class="{{ $expense->status === 'voided' ? 'text-decoration-line-through text-secondary' : '' }}">
                                    <td class="small">{{ $expense->expense_date->format('M j, Y') }}</td>
                                    <td>{{ $expense->description }}</td>
                                    <td class="small text-secondary">{{ $expense->property->name ?? '—' }}</td>
                                    <td>{{ $expense->account->name ?? '—' }}</td>
                                    <td class="text-end">{{ number_format($expense->amount, 2) }}</td>
                                    <td><span class="badge text-bg-secondary text-capitalize">{{ $expense->payment_status }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
