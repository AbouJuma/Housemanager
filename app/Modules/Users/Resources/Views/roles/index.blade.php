@extends('layouts.app')

@section('title', __('Roles'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Roles') }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ __('Roles') }}</h1>
            <p class="text-secondary mb-0">{{ $roles->total() }} {{ Str::plural('role', $roles->total()) }}</p>
        </div>
        @can('create', Spatie\Permission\Models\Role::class)
            <a href="{{ route('roles.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> {{ __('New Role') }}
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="input-group" style="max-width: 320px;">
                    <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                    <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search roles…">
                </div>
            </form>

            @if ($roles->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-shield-lock display-4 text-secondary"></i>
                    <p class="mt-3 mb-1 fw-semibold">No roles yet</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Permissions</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $role)
                                <tr>
                                    <td class="fw-semibold">{{ $role->name }}</td>
                                    <td>{{ $role->permissions->count() }}</td>
                                    <td class="text-end">
                                        @can('update', $role)
                                            <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                        @endcan
                                        @can('delete', $role)
                                            <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline js-delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-danger js-confirm-delete" data-name="{{ $role->name }}">
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
                    {{ $roles->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
