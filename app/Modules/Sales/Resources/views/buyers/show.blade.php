@extends('layouts.app')

@section('title', 'Buyer - ' . $buyer->name)

@section('page-heading')
    <h1 class="h4 mb-0">{{ $buyer->name }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ $buyer->name }}</h1>
        </div>
        <div class="d-flex gap-2">
            @can('update', $buyer)
                <a href="{{ route('buyers.edit', $buyer) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i> {{ __('Edit') }}
                </a>
            @endcan
            @can('delete', $buyer)
                <form action="{{ route('buyers.destroy', $buyer) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-outline-danger btn-sm js-confirm-delete" data-name="{{ $buyer->name }}">
                        <i class="bi bi-trash me-1"></i> {{ __('Delete') }}
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">Buyer Details</h2>
                    <div class="row g-3 small">
                        <div class="col-md-6">
                            <div class="text-secondary">{{ __('Name') }}</div>
                            <div class="fw-semibold">{{ $buyer->name }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-secondary">{{ __('Email') }}</div>
                            <div>{{ $buyer->email ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-secondary">{{ __('Phone') }}</div>
                            <div>{{ $buyer->phone ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-secondary">{{ __('ID Number') }}</div>
                            <div>{{ $buyer->id_number ?? '—' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="text-secondary">{{ __('Address') }}</div>
                            <div>{{ $buyer->address ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-secondary">{{ __('Status') }}</div>
                            <div>
                                <span class="badge text-bg-{{ $buyer->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($buyer->status) }}</span>
                            </div>
                        </div>
                    </div>
                    @if ($buyer->notes)
                        <p class="mt-3 mb-0">{{ $buyer->notes }}</p>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">Purchase History</h2>
                    @if ($buyer->sales->isEmpty())
                        <p class="text-secondary small mb-0">No purchases recorded.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle small">
                                <thead>
                                    <tr>
                                        <th>{{ __('Unit') }}</th>
                                        <th>{{ __('Sale Date') }}</th>
                                        <th>{{ __('Total Price') }}</th>
                                        <th>{{ __('Paid') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th class="text-end">{{ __('Invoices') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($buyer->sales as $sale)
                                        <tr>
                                            <td class="fw-semibold">
                                                {{ $sale->unit->unit_number }}
                                                <small class="text-secondary d-block">{{ $sale->unit->building->property->name }} · {{ $sale->unit->building->name }}</small>
                                            </td>
                                            <td>{{ $sale->sale_date->format('M j, Y') }}</td>
                                            <td>{{ number_format($sale->total_price, 2) }}</td>
                                            <td>{{ number_format($sale->total_paid, 2) }}</td>
                                            <td>
                                                <span class="badge text-bg-{{ $sale->status === 'pending' ? 'secondary' : ($sale->status === 'active' ? 'primary' : ($sale->status === 'completed' ? 'success' : ($sale->status === 'cancelled' ? 'danger' : 'secondary'))) }}">{{ ucfirst($sale->status) }}</span>
                                            </td>
                                            <td class="text-end">
                                                @if($sale->invoices->isNotEmpty())
                                                    <a href="{{ route('invoices.index', ['search' => $buyer->name]) }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-file-earmark-text"></i> {{ $sale->invoices->count() }}
                                                    </a>
                                                @else
                                                    <span class="text-secondary">—</span>
                                                @endif
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
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">Summary</h2>
                    <div class="row g-3 small">
                        <div class="col-6">
                            <div class="text-secondary">Total Purchases</div>
                            <div class="fw-semibold">{{ $buyer->sales->count() }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-secondary">Total Spent</div>
                            <div class="fw-semibold">{{ number_format($buyer->sales->sum('total_price'), 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @can('update', $buyer)
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 mb-3">{{ __('Portal Access') }}</h2>

                        @if (session('portal_password'))
                            <div class="alert alert-warning small">
                                <strong>One-time password:</strong> <code>{{ session('portal_password') }}</code>
                                <div class="mt-1">Share this with the buyer now — it will not be shown again.</div>
                            </div>
                        @endif

                        @if ($buyer->hasPortalAccess())
                            <p class="small text-secondary mb-3"><span class="badge text-bg-success">{{ __('Enabled') }}</span> {{ $buyer->email }} can sign in to the mobile app.</p>
                            <div class="d-flex gap-2">
                                <form action="{{ route('buyers.portal-access.enable', $buyer) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Reset Password') }}</button>
                                </form>
                                <form action="{{ route('buyers.portal-access.disable', $buyer) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Disable Access') }}</button>
                                </form>
                            </div>
                        @else
                            <p class="small text-secondary mb-3"><span class="badge text-bg-secondary">{{ __('Not Enabled') }}</span> This buyer cannot sign in to the mobile app.</p>
                            <form action="{{ route('buyers.portal-access.enable', $buyer) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary" {{ $buyer->email ? '' : 'disabled' }}>{{ __('Enable Portal Access') }}</button>
                            </form>
                            @unless ($buyer->email)
                                <div class="form-text">Add an email address to this buyer first.</div>
                            @endunless
                        @endif
                    </div>
                </div>
            @endcan
        </div>
    </div>
@endsection
