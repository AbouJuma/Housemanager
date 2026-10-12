{{-- Edit a tenant's or buyer's details ($kind = tenant|buyer). --}}
@extends('landlord-portal::layout')

@section('title', __('Edit :name', ['name' => $person->name]))

@section('content')
    @php
        $isTenant = $kind === 'tenant';
        $action = $isTenant ? route('landlord-portal.tenants.update', $person) : route('landlord-portal.buyers.update', $person);
        $back = $isTenant ? route('landlord-portal.tenants.index') : route('landlord-portal.buyers.index');
    @endphp

    <h1 class="h4 mb-1">{{ __('Edit :name', ['name' => $person->name]) }}</h1>
    <p class="text-secondary mb-4">{{ $isTenant ? __('Tenant') : __('Buyer') }}</p>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ $action }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Full name') }}</label>
                        <input type="text" name="name" value="{{ old('name', $person->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Username') }}</label>
                        <input type="text" name="username" value="{{ old('username', $person->username) }}" class="form-control @error('username') is-invalid @enderror" autocomplete="off">
                        @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Phone') }}</label>
                        <input type="tel" name="phone" value="{{ old('phone', $person->phone) }}" class="form-control @error('phone') is-invalid @enderror" required>
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Email') }}</label>
                        <input type="email" name="email" value="{{ old('email', $person->email) }}" class="form-control @error('email') is-invalid @enderror">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select name="status" class="form-select">
                            @foreach (['active' => __('Active'), 'inactive' => __('Inactive')] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $person->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($isTenant)
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Emergency contact') }}</label>
                            <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $person->emergency_contact_name) }}" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Emergency contact phone') }}</label>
                            <input type="tel" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $person->emergency_contact_phone) }}" class="form-control">
                        </div>
                    @else
                        <div class="col-md-6">
                            <label class="form-label">{{ __('ID number') }}</label>
                            <input type="text" name="id_number" value="{{ old('id_number', $person->id_number) }}" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">{{ __('Address') }}</label>
                            <input type="text" name="address" value="{{ old('address', $person->address) }}" class="form-control">
                        </div>
                    @endif
                    <div class="col-12">
                        <label class="form-label">{{ __('Notes') }}</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $person->notes) }}</textarea>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                    <a href="{{ $back }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
