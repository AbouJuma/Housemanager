@extends('layouts.app')

@section('title', $user->name)

@section('page-heading')
    <h1 class="h4 mb-0">{{ $user->name }}</h1>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h2 class="h5 mb-0">{{ $user->name }}</h2>
                            <p class="text-secondary mb-0">{{ $user->email }}</p>
                        </div>
                        @can('update', $user)
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-pencil me-1"></i> Edit
                            </a>
                        @endcan
                    </div>

                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-secondary fw-normal">Company</dt>
                        <dd class="col-sm-8">{{ $user->company?->name ?? 'SaaS Owner' }}</dd>
                        <dt class="col-sm-4 text-secondary fw-normal">Roles</dt>
                        <dd class="col-sm-8">
                            @forelse ($user->roleNamesForOwnCompany() as $role)
                                <span class="badge text-bg-secondary">{{ $role }}</span>
                            @empty
                                &mdash;
                            @endforelse
                        </dd>
                        <dt class="col-sm-4 text-secondary fw-normal">Joined</dt>
                        <dd class="col-sm-8">{{ $user->created_at->format(setting('date_format')) }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection
