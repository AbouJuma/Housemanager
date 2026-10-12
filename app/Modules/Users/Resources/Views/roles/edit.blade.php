@extends('layouts.app')

@section('title', 'Edit ' . $role->name)

@section('page-heading')
    <h1 class="h4 mb-0">Edit {{ $role->name }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('roles.update', $role) }}">
                @csrf
                @method('PUT')

                <div class="mb-4" style="max-width: 360px;">
                    <label for="name" class="form-label">{{ __('Role Name') }}</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $role->name) }}" class="form-control @error('name') is-invalid @enderror" required autofocus @readonly($forLandlords ?? false)>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                @if ($forLandlords ?? false)
                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle me-1"></i>
                        {{ __('This role applies to every landlord of your company. What you tick here is exactly what landlords can see and do in the landlord portal and the mobile app. Changes apply immediately.') }}
                    </div>
                @endif

                @include('users::roles._permissions', ['assigned' => old('permissions', $assigned)])

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
