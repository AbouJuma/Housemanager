@extends('layouts.app')

@section('title', $account->name)

@section('page-heading')
    <h1 class="h4 mb-0">{{ $account->code }} · {{ $account->name }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ $account->code }} · {{ $account->name }}</h1>
            <p class="text-secondary mb-0 text-capitalize">
                {{ $account->type }} &middot;
                <span class="badge text-bg-{{ $account->is_active ? 'success' : 'secondary' }}">{{ $account->is_active ? 'Active' : 'Inactive' }}</span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> All Accounts
            </a>
            @can('update', $account)
                <a href="{{ route('accounts.edit', $account) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            @endcan
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-secondary small">Current Balance</div>
                    <div class="fs-3 fw-semibold">{{ number_format($account->balance(), 2) }}</div>
                    @if ($account->description)
                        <hr>
                        <div class="text-secondary small mb-1">Description</div>
                        <p class="mb-0 small">{{ $account->description }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">Recent Activity</h2>

                    @if ($recentLines->isEmpty())
                        <div class="text-center py-4">
                            <i class="bi bi-journals display-6 text-secondary"></i>
                            <p class="mt-2 mb-0 text-secondary">No journal entries yet.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th class="text-end">Debit</th>
                                        <th class="text-end">Credit</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentLines as $line)
                                        <tr>
                                            <td class="small">{{ $line->journalEntry->entry_date->format('M j, Y') }}</td>
                                            <td>{{ $line->journalEntry->description }}</td>
                                            <td class="text-end">{{ $line->debit > 0 ? number_format($line->debit, 2) : '—' }}</td>
                                            <td class="text-end">{{ $line->credit > 0 ? number_format($line->credit, 2) : '—' }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('journal-entries.show', $line->journalEntry) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
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
    </div>
@endsection
