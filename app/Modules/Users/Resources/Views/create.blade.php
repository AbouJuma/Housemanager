@extends('layouts.app')

@section('title', 'New User')

@section('page-heading')
    <h1 class="h4 mb-0">New User</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('users.store') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Name</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required autofocus>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone <span class="text-secondary fw-normal">(optional)</span></label>
                        <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" placeholder="+255700000000">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="password" class="form-label">Password</label>
                        <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if (auth()->user()->isSaasOwner())
                        <div class="col-md-6">
                            <label for="company_id" class="form-label">Company <span class="text-secondary fw-normal">(leave blank for another SaaS-owner account)</span></label>
                            <select id="company_id" name="company_id" class="form-select @error('company_id') is-invalid @enderror">
                                <option value="">&mdash; None (SaaS owner) &mdash;</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>{{ $company->name }}</option>
                                @endforeach
                            </select>
                            @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif
                </div>

                @if ($roles->isNotEmpty())
                    <fieldset class="mt-4">
                        <legend class="h6 mb-3">Roles</legend>
                        <div class="row g-2">
                            @foreach ($roles as $role)
                                <div class="col-md-4">
                                    <div class="form-check border rounded p-2 h-100">
                                        <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->name }}"
                                               id="role-{{ $role->id }}" @checked(in_array($role->name, old('roles', [])))>
                                        <label class="form-check-label" for="role-{{ $role->id }}">{{ $role->name }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create User</button>
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
