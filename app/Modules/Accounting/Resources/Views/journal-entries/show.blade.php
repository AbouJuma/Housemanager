@extends('layouts.app')

@section('title', $entry->description)

@section('page-heading')
    <h1 class="h4 mb-0">Journal Entry</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">Journal Entry</h1>
            <p class="text-secondary mb-0">
                {{ $entry->entry_date->toFormattedDateString() }} &middot;
                <span class="badge text-bg-{{ $entry->status === 'posted' ? 'success' : 'secondary' }} text-capitalize">{{ $entry->status }}</span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('journal-entries.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> All Journal Entries
            </a>
            @can('void', $entry)
                @if (! $entry->isVoided())
                    <form action="{{ route('journal-entries.void', $entry) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="button" class="btn btn-outline-danger btn-sm js-confirm-delete" data-name="this journal entry" data-text="This posts a reversing entry and cannot be undone.">
                            <i class="bi bi-x-lg me-1"></i> Void
                        </button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 small">
                <div class="col-md-6">
                    <div class="text-secondary">Description</div>
                    <div>{{ $entry->description }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary">Reference</div>
                    <div>{{ $entry->reference ?? '—' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary">Posted By</div>
                    <div>{{ $entry->createdBy?->name ?? 'System' }}</div>
                </div>
                @if ($entry->source instanceof \App\Modules\Invoices\Models\Invoice)
                    <div class="col-md-6">
                        <div class="text-secondary">Source</div>
                        <div><a href="{{ route('invoices.show', $entry->source) }}">Invoice {{ $entry->source->invoice_number }}</a></div>
                    </div>
                @elseif ($entry->source instanceof \App\Modules\Invoices\Models\InvoicePayment)
                    <div class="col-md-6">
                        <div class="text-secondary">Source</div>
                        <div><a href="{{ route('invoices.show', $entry->source->invoice) }}">Payment on Invoice {{ $entry->source->invoice->invoice_number }}</a></div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">Lines</h2>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th>Memo</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entry->lines as $line)
                            <tr>
                                <td><a href="{{ route('accounts.show', $line->account) }}">{{ $line->account->code }} &middot; {{ $line->account->name }}</a></td>
                                <td class="small text-secondary">{{ $line->memo ?? '—' }}</td>
                                <td class="text-end">{{ $line->debit > 0 ? number_format($line->debit, 2) : '—' }}</td>
                                <td class="text-end">{{ $line->credit > 0 ? number_format($line->credit, 2) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2" class="text-end">Total</th>
                            <th class="text-end">{{ number_format($entry->lines->sum('debit'), 2) }}</th>
                            <th class="text-end">{{ number_format($entry->lines->sum('credit'), 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
