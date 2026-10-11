@extends('layouts.app')

@section('title', $vendor->name)

@section('page-heading')
    <h1 class="h4 mb-0">{{ $vendor->name }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ $vendor->name }}</h1>
            <p class="text-secondary mb-0 text-capitalize">{{ $vendor->type }} &middot; {{ str_replace('_', ' ', $vendor->category) }} &middot; {{ $vendor->status }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('vendors.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> All Vendors
            </a>
            @can('update', $vendor)
                <a href="{{ route('vendors.edit', $vendor) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i> {{ __('Edit') }}
                </a>
            @endcan
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('Contact') }}</h2>
                    <div class="row g-3 small">
                        <div class="col-md-4">
                            <div class="text-secondary">{{ __('Email') }}</div>
                            <div>{{ $vendor->email ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">{{ __('Phone') }}</div>
                            <div>{{ $vendor->phone ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">{{ __('Secondary Phone') }}</div>
                            <div>{{ $vendor->secondary_phone ?? '—' }}</div>
                        </div>
                        <div class="col-md-8">
                            <div class="text-secondary">{{ __('Address') }}</div>
                            <div>{{ collect([$vendor->address_line1, $vendor->address_line2, $vendor->city, $vendor->state, $vendor->postal_code, $vendor->country_code])->filter()->implode(', ') ?: '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">{{ __('Tax ID') }}</div>
                            <div>{{ $vendor->tax_id ?? '—' }}</div>
                        </div>
                    </div>
                    @if ($vendor->notes)
                        <hr>
                        <div class="text-secondary small mb-1">{{ __('Notes') }}</div>
                        <p class="mb-0">{{ $vendor->notes }}</p>
                    @endif
                </div>
            </div>

            @if ($vendor->bank_details)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h6 mb-3">{{ __('Bank Details') }}</h2>
                        <div class="row g-3 small">
                            <div class="col-md-4">
                                <div class="text-secondary">{{ __('Bank') }}</div>
                                <div>{{ $vendor->bankDetail('name') ?? '—' }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-secondary">{{ __('Account Name') }}</div>
                                <div>{{ $vendor->bankDetail('account_name') ?? '—' }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-secondary">{{ __('Account Number') }}</div>
                                <div>{{ $vendor->bankDetail('account_number') ?? '—' }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-secondary">{{ __('Branch') }}</div>
                                <div>{{ $vendor->bankDetail('branch') ?? '—' }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-secondary">{{ __('SWIFT / Branch Code') }}</div>
                                <div>{{ $vendor->bankDetail('swift_code') ?? '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">Recent Expenses</h2>

                    @if ($expenses->isEmpty())
                        <div class="text-center py-4">
                            <i class="bi bi-cash-stack display-6 text-secondary"></i>
                            <p class="mt-2 mb-0 text-secondary">No expenses recorded against this vendor yet.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('Description') }}</th>
                                        <th>{{ __('Category') }}</th>
                                        <th class="text-end">{{ __('Amount') }}</th>
                                        <th class="text-end">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($expenses as $expense)
                                        <tr>
                                            <td class="small">{{ $expense->expense_date->format('M j, Y') }}</td>
                                            <td>{{ $expense->description }}</td>
                                            <td class="small text-secondary">{{ $expense->account->name }}</td>
                                            <td class="text-end">{{ number_format($expense->amount, 2) }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('expenses.show', $expense) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('Maintenance') }}</h2>

                    @if ($maintenanceRequests->isEmpty())
                        <p class="text-secondary small mb-0">No maintenance requests assigned to this vendor yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>{{ __('Title') }}</th>
                                        <th>{{ __('Property') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th class="text-end">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($maintenanceRequests as $item)
                                        <tr>
                                            <td class="fw-semibold">{{ $item->title }}</td>
                                            <td class="small text-secondary">{{ $item->property->name }}</td>
                                            <td><span class="badge text-bg-{{ match($item->status) { 'completed' => 'success', 'cancelled' => 'secondary', 'in_progress' => 'primary', default => 'warning' } }} text-capitalize">{{ str_replace('_', ' ', $item->status) }}</span></td>
                                            <td class="text-end">
                                                <a href="{{ route('maintenance-requests.show', $item) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">Inventory Items Supplied</h2>

                    @if ($inventoryItems->isEmpty())
                        <p class="text-secondary small mb-0">No inventory items list this vendor as a preferred supplier yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Property') }}</th>
                                        <th>{{ __('On Hand') }}</th>
                                        <th class="text-end">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($inventoryItems as $item)
                                        <tr>
                                            <td class="fw-semibold">{{ $item->name }}</td>
                                            <td class="small text-secondary">{{ $item->property->name ?? '—' }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item->quantity_on_hand, 2), '0'), '.') }} {{ $item->unit_of_measure }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('inventory-items.show', $item) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 mb-0">{{ __('Documents') }}</h2>
                        @can('create', \App\Modules\Documents\Models\Document::class)
                            <a href="{{ route('documents.create', ['documentable_type' => 'vendor', 'documentable_id' => $vendor->id]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-plus-lg me-1"></i> {{ __('Add Document') }}
                            </a>
                        @endcan
                    </div>

                    @if ($documents->isEmpty())
                        <p class="text-secondary small mb-0">No documents attached to this vendor yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>{{ __('Title') }}</th>
                                        <th>{{ __('Category') }}</th>
                                        <th class="text-end">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($documents as $item)
                                        <tr>
                                            <td class="fw-semibold">{{ $item->title }}</td>
                                            <td class="text-capitalize">{{ str_replace('_', ' ', $item->category) }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('documents.show', $item) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('Photo') }}</h2>
                    @if ($vendor->photoUrl())
                        <img src="{{ $vendor->photoUrl() }}" alt="{{ $vendor->name }}" class="img-fluid rounded-3">
                    @else
                        <p class="text-secondary small mb-0">{{ __('No photo uploaded yet.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
