@extends('layouts.app')

@section('title', __('Documents'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Documents') }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ __('Documents') }}</h1>
            <p class="text-secondary mb-0">{{ $documents->total() }} {{ Str::plural('document', $documents->total()) }}</p>
        </div>
        @can('create', \App\Modules\Documents\Models\Document::class)
            <a href="{{ route('documents.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> {{ __('New Document') }}
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-auto">
                    <div class="input-group" style="max-width: 320px;">
                        <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                        <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by title…">
                    </div>
                </div>
                <div class="col-auto">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">{{ __('All categories') }}</option>
                        @foreach (['contract', 'lease_agreement', 'id_document', 'certificate', 'insurance', 'deed', 'invoice', 'license', 'other'] as $category)
                            <option value="{{ $category }}" @selected(request('category') === $category)>{{ ucfirst(str_replace('_', ' ', $category)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="documentable_type" class="form-select" onchange="this.form.submit()">
                        <option value="">{{ __('Attached to anything') }}</option>
                        @foreach (['property' => 'Properties', 'landlord' => 'Landlords', 'tenant' => 'Tenants', 'lease' => 'Leases', 'vendor' => 'Vendors'] as $key => $label)
                            <option value="{{ $key }}" @selected(request('documentable_type') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto d-flex align-items-center">
                    <div class="form-check">
                        <input type="checkbox" name="expiring_soon" value="1" id="expiringSoonOnly" class="form-check-input" onchange="this.form.submit()" @checked(request()->boolean('expiring_soon'))>
                        <label class="form-check-label" for="expiringSoonOnly">Expiring within 30 days</label>
                    </div>
                </div>
            </form>

            @if ($documents->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-file-earmark-text display-4 text-secondary"></i>
                    <p class="mt-3 mb-1 fw-semibold">{{ __('No documents yet') }}</p>
                    <p class="text-secondary">{{ __('Documents you upload will show up here.') }}</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Title') }}</th>
                                <th>{{ __('Category') }}</th>
                                <th>{{ __('Attached To') }}</th>
                                <th>{{ __('Expiry') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($documents as $item)
                                <tr>
                                    <td class="fw-semibold">{{ $item->title }}</td>
                                    <td class="text-capitalize">{{ str_replace('_', ' ', $item->category) }}</td>
                                    <td class="small text-secondary text-capitalize">{{ $item->documentableTypeKey() ?? 'Company-wide' }}</td>
                                    <td>
                                        @if ($item->expiry_date)
                                            {{ $item->expiry_date->toFormattedDateString() }}
                                            @if ($item->isExpired())
                                                <span class="badge text-bg-danger ms-1">{{ __('Expired') }}</span>
                                            @elseif ($item->isExpiringSoon())
                                                <span class="badge text-bg-warning ms-1">{{ __('Expiring Soon') }}</span>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('documents.show', $item) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $documents->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
