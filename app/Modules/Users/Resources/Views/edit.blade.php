@extends('layouts.app')

@section('title', 'Edit ' . $user->name)

@section('page-heading')
    <h1 class="h4 mb-0">Edit {{ $user->name }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('users.update', $user) }}">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">{{ __('Name') }}</label>
                        <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required autofocus>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                        <x-username-field type="staff" :value="$user->username" :ignore="$user->id" />

                    <div class="col-md-6">
                        <label for="email" class="form-label">{{ __('Email') }}</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label">{{ __('Phone') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
                        <input id="phone" type="tel" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control @error('phone') is-invalid @enderror" placeholder="+255700000000">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="password" class="form-label">New Password <span class="text-secondary fw-normal">(leave blank to keep current)</span></label>
                        <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                @if ($roles->isNotEmpty())
                    <fieldset class="mt-4">
                        <legend class="h6">{{ __('Roles') }}</legend>
                        <div class="row g-2">
                            @foreach ($roles as $role)
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->name }}"
                                               id="role-{{ $role->id }}" @checked($user->roleNamesForOwnCompany()->contains($role->name))>
                                        <label class="form-check-label" for="role-{{ $role->id }}">{{ $role->name }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                    <a href="{{ route('users.show', $user) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
