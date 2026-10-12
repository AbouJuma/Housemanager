@extends('landlord-portal::layout')

@section('title', $unit ? __('Edit Unit') : __('Add Unit'))

@section('content')
    <h1 class="h4 mb-1">{{ $unit ? __('Edit Unit :number', ['number' => $unit->unit_number]) : __('Add Unit') }}</h1>
    <p class="text-secondary mb-4">{{ $building->property->name }} · {{ $building->name }}</p>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ $unit ? route('landlord-portal.units.update', $unit) : route('landlord-portal.units.store', $building) }}">
                @csrf
                @if ($unit)
                    @method('PUT')
                @endif

                @include('properties::units._form', ['forLandlord' => true])

                <div class="mt-4 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">{{ $unit ? __('Save Changes') : __('Add Unit') }}</button>
                    @unless ($unit)
                        <button type="submit" name="add_another" value="1" class="btn btn-outline-primary">{{ __('Save & add another') }}</button>
                    @endunless
                    <a href="{{ route('landlord-portal.properties.show', $building->property) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
