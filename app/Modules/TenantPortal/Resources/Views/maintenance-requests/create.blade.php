@extends('tenant-portal::layout')

@section('title', __('Report an Issue'))

@section('content')
    <h1 class="h4 mb-4">{{ __('Report an Issue') }}</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($leases->isEmpty())
                <p class="text-secondary small mb-0">You don't have an active lease on file, so a request can't be filed right now. Please contact your property manager directly.</p>
            @else
                <form method="POST" action="{{ route('portal.maintenance-requests.store') }}">
                    @csrf
                    <div class="row g-3">
                        @if ($leases->count() > 1)
                            <div class="col-12">
                                <label class="form-label">Which unit is this about?</label>
                                <select name="lease_id" class="form-select @error('lease_id') is-invalid @enderror" required>
                                    @foreach ($leases as $lease)
                                        <option value="{{ $lease->id }}" @selected(old('lease_id') == $lease->id)>{{ $lease->unit->unit_number }} &middot; {{ $lease->unit->building->property->name }}</option>
                                    @endforeach
                                </select>
                                @error('lease_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @else
                            <input type="hidden" name="lease_id" value="{{ $leases->first()->id }}">
                        @endif

                        <div class="col-12">
                            <label class="form-label">{{ __('Title') }}</label>
                            <input type="text" name="title" value="{{ old('title') }}" class="form-control @error('title') is-invalid @enderror" required>
                            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Description') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
                            <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Category') }}</label>
                            <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                                @foreach (['plumbing', 'electrical', 'hvac', 'cleaning', 'landscaping', 'pest_control', 'general_contractor', 'utility_provider', 'security', 'other'] as $category)
                                    <option value="{{ $category }}" @selected(old('category') === $category)>{{ ucfirst(str_replace('_', ' ', $category)) }}</option>
                                @endforeach
                            </select>
                            @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Priority') }}</label>
                            <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
                                @foreach (['low', 'medium', 'high', 'urgent'] as $priority)
                                    <option value="{{ $priority }}" @selected(old('priority', 'medium') === $priority)>{{ ucfirst($priority) }}</option>
                                @endforeach
                            </select>
                            @error('priority') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                        <a href="{{ route('portal.maintenance-requests.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection
