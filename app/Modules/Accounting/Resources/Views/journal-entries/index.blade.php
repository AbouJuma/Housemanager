@extends('layouts.app')

@section('title', 'Journal Entries')

@section('page-heading')
    <h1 class="h4 mb-0">Journal Entries</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">Journal Entries</h1>
            <p class="text-secondary mb-0">{{ $entries->total() }} {{ Str::plural('entry', $entries->total()) }}</p>
        </div>
        @can('create', \App\Modules\Accounting\Models\JournalEntry::class)
            <a href="{{ route('journal-entries.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> New Journal Entry
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-auto">
                    <div class="input-group" style="max-width: 320px;">
                        <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                        <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by description or reference…">
                    </div>
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        @foreach (['posted', 'voided'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if ($entries->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-journals display-4 text-secondary"></i>
                    <p class="mt-3 mb-1 fw-semibold">No journal entries yet</p>
                    <p class="text-secondary">Entries you post — manually or automatically — will show up here.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Reference</th>
                                <th class="text-end">Total</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($entries as $entry)
                                <tr>
                                    <td class="small">{{ $entry->entry_date->format('M j, Y') }}</td>
                                    <td>{{ $entry->description }}</td>
                                    <td class="small text-secondary">{{ $entry->reference ?? '—' }}</td>
                                    <td class="text-end">{{ number_format($entry->totalDebits(), 2) }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ $entry->status === 'posted' ? 'success' : 'secondary' }} text-capitalize">{{ $entry->status }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('journal-entries.show', $entry) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $entries->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
