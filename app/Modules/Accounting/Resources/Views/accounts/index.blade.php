@extends('layouts.app')

@section('title', 'Chart of Accounts')

@section('page-heading')
    <h1 class="h4 mb-0">Chart of Accounts</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">Chart of Accounts</h1>
            <p class="text-secondary mb-0">{{ $accounts->total() }} {{ Str::plural('account', $accounts->total()) }}</p>
        </div>
        @can('create', \App\Modules\Accounting\Models\Account::class)
            <a href="{{ route('accounts.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> New Account
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-auto">
                    <div class="input-group" style="max-width: 320px;">
                        <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                        <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by code or name…">
                    </div>
                </div>
                <div class="col-auto">
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="">All types</option>
                        @foreach (['asset', 'liability', 'equity', 'income', 'expense'] as $type)
                            <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if ($accounts->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-journal-text display-4 text-secondary"></i>
                    <p class="mt-3 mb-1 fw-semibold">No accounts yet</p>
                    <p class="text-secondary">Accounts you add will show up here.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th class="text-end">Balance</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($accounts as $account)
                                <tr>
                                    <td class="fw-semibold">{{ $account->code }}</td>
                                    <td>{{ $account->name }}</td>
                                    <td class="text-capitalize">{{ $account->type }}</td>
                                    <td class="text-end">{{ number_format($account->balance(), 2) }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ $account->is_active ? 'success' : 'secondary' }}">{{ $account->is_active ? 'Active' : 'Inactive' }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('accounts.show', $account) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                        @can('update', $account)
                                            <a href="{{ route('accounts.edit', $account) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                        @endcan
                                        @can('delete', $account)
                                            <form action="{{ route('accounts.destroy', $account) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-danger js-confirm-delete" data-name="{{ $account->name }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $accounts->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
