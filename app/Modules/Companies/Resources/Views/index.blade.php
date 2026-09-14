@extends('layouts.app')

@section('title', 'Companies')

@section('page-heading')
    <h1 class="h4 mb-0">Companies</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">Companies</h1>
            <p class="text-secondary mb-0">{{ $companies->total() }} {{ Str::plural('company', $companies->total()) }}</p>
        </div>
        @can('create', \App\Modules\Companies\Models\Company::class)
            <a href="{{ route('companies.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> New Company
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="input-group" style="max-width: 320px;">
                    <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                    <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search companies…">
                </div>
            </form>

            @if ($companies->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-building display-4 text-secondary"></i>
                    <p class="mt-3 mb-1 fw-semibold">No companies yet</p>
                    <p class="text-secondary">Companies you onboard will show up here.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Status</th>
                                <th>Currency</th>
                                <th>Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($companies as $company)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $company->name }}</div>
                                        <div class="text-secondary small">{{ $company->slug }}</div>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-{{ match ($company->status) {
                                            'active' => 'success',
                                            'trial' => 'info',
                                            'suspended' => 'warning',
                                            default => 'secondary',
                                        } }}">{{ ucfirst($company->status) }}</span>
                                    </td>
                                    <td>{{ $company->currency_code }}</td>
                                    <td>{{ $company->created_at->format(setting('date_format')) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('companies.show', $company) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                        @can('update', $company)
                                            <a href="{{ route('companies.edit', $company) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                        @endcan
                                        @can('delete', $company)
                                            <form action="{{ route('companies.destroy', $company) }}" method="POST" class="d-inline js-delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-danger js-confirm-delete" data-name="{{ $company->name }}" data-text="This can be restored by support, but is hidden from the app immediately.">
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
                    {{ $companies->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
