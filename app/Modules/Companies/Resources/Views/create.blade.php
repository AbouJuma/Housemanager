@extends('layouts.app')

@section('title', __('New Company'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('New Company') }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('companies.store') }}">
                @csrf
                @include('companies::_form')

                {{-- The company's first user: gets the Admin role and is sent
                     their username, password and login link. --}}
                <hr class="my-4">
                <h2 class="h6 mb-1">{{ __('Company admin') }}</h2>
                <p class="text-secondary small mb-3">{{ __('This person manages the company. They get the Admin role and their login details by email (and SMS if a phone is given).') }}</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Full name') }}</label>
                        <input type="text" name="admin_name" value="{{ old('admin_name') }}" class="form-control @error('admin_name') is-invalid @enderror" required>
                        @error('admin_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Email') }}</label>
                        <input type="email" name="admin_email" value="{{ old('admin_email') }}" class="form-control @error('admin_email') is-invalid @enderror" required>
                        @error('admin_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Username') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
                        <input type="text" name="admin_username" value="{{ old('admin_username') }}" class="form-control @error('admin_username') is-invalid @enderror" autocomplete="off">
                        <div class="form-text">{{ __('Leave blank to create one from the name.') }}</div>
                        @error('admin_username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Phone') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
                        <input type="tel" name="admin_phone" value="{{ old('admin_phone') }}" class="form-control @error('admin_phone') is-invalid @enderror">
                        @error('admin_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Password') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
                        <input type="password" name="admin_password" class="form-control @error('admin_password') is-invalid @enderror" autocomplete="new-password">
                        <div class="form-text">{{ __('Leave blank to use their phone number (or a random one).') }}</div>
                        @error('admin_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Create Company') }}</button>
                    <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
