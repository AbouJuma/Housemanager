@extends('layouts.app')

@section('title', 'Users')

@section('page-heading')
    <h1 class="h4 mb-0">Users</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">Users</h1>
            <p class="text-secondary mb-0">{{ $users->total() }} {{ Str::plural('user', $users->total()) }}</p>
        </div>
        @can('create', App\Models\User::class)
            <a href="{{ route('users.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> New User
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="input-group" style="max-width: 320px;">
                    <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                    <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search users…">
                </div>
            </form>

            @if ($users->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-people display-4 text-secondary"></i>
                    <p class="mt-3 mb-1 fw-semibold">No users yet</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>User</th>
                                @if (auth()->user()->isSaasOwner())
                                    <th>Company</th>
                                @endif
                                <th>Roles</th>
                                <th>Joined</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $user->name }}</div>
                                        <div class="text-secondary small">{{ $user->email }}</div>
                                    </td>
                                    @if (auth()->user()->isSaasOwner())
                                        <td>{{ $user->company?->name ?? 'SaaS Owner' }}</td>
                                    @endif
                                    <td>
                                        @forelse ($user->roleNamesForOwnCompany() as $role)
                                            <span class="badge text-bg-secondary">{{ $role }}</span>
                                        @empty
                                            <span class="text-secondary small">&mdash;</span>
                                        @endforelse
                                    </td>
                                    <td>{{ $user->created_at->format(setting('date_format')) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                        @can('update', $user)
                                            <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                        @endcan
                                        @can('delete', $user)
                                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline js-delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-danger js-confirm-delete" data-name="{{ $user->name }}">
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
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
