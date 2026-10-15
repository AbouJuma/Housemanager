@extends('landlord-portal::layout')

@section('title', __('New Lease'))

@section('content')
    <h1 class="h4 mb-1">{{ __('New Lease') }}</h1>
    <p class="text-secondary mb-4">{{ __('Rent out one of your free units. The rent per period and the end date are worked out for you - you can still change them.') }}</p>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($units->isEmpty())
                <p class="text-secondary mb-0">{{ __('You have no free units right now.') }}</p>
            @else
                <form method="POST" action="{{ route('landlord-portal.leases.store') }}">
                    @csrf
                    @include('leases::_form', ['addTenantUrl' => $addTenantUrl])

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">{{ __('Create Lease') }}</button>
                        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection
