@extends('layouts.app')

@section('title', $tenant->name)

@section('page-heading')
    <h1 class="h4 mb-0">{{ $tenant->name }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ $tenant->name }}</h1>
            <p class="text-secondary mb-0 text-capitalize">
                {{ $tenant->type }} &middot;
                <span class="{{ $tenant->isBlacklisted() ? 'text-danger fw-semibold' : '' }}">{{ $tenant->status }}</span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('tenants.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> All Tenants
            </a>
            @can('update', $tenant)
                <a href="{{ route('tenants.edit', $tenant) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i> {{ __('Edit') }}
                </a>
            @endcan
        </div>
    </div>

    @if ($tenant->isBlacklisted())
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-1"></i> This tenant is blacklisted.
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('Contact') }}</h2>
                    <div class="row g-3 small">
                        <div class="col-md-4">
                            <div class="text-secondary">{{ __('Email') }}</div>
                            <div>{{ $tenant->email ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">{{ __('Phone') }}</div>
                            <div>{{ $tenant->phone ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">{{ __('Secondary Phone') }}</div>
                            <div>{{ $tenant->secondary_phone ?? '—' }}</div>
                        </div>
                        <div class="col-md-8">
                            <div class="text-secondary">{{ __('Address') }}</div>
                            <div>{{ collect([$tenant->address_line1, $tenant->address_line2, $tenant->city, $tenant->state, $tenant->postal_code, $tenant->country_code])->filter()->implode(', ') ?: '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">{{ __('Date of Birth') }}</div>
                            <div>{{ $tenant->date_of_birth?->format(setting('date_format')) ?? '—' }}</div>
                        </div>
                    </div>
                    @if ($tenant->notes)
                        <hr>
                        <div class="text-secondary small mb-1">{{ __('Notes') }}</div>
                        <p class="mb-0">{{ $tenant->notes }}</p>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">Identification</h2>
                    <div class="row g-3 small">
                        <div class="col-md-4">
                            <div class="text-secondary">{{ __('Type') }}</div>
                            <div>{{ $tenant->identification_type ? ucfirst(str_replace('_', ' ', $tenant->identification_type)) : '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">{{ __('Number') }}</div>
                            <div>{{ $tenant->identification_number ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('Emergency Contact') }}</h2>
                    <div class="row g-3 small">
                        <div class="col-md-6">
                            <div class="text-secondary">{{ __('Name') }}</div>
                            <div>{{ $tenant->emergency_contact_name ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-secondary">{{ __('Phone') }}</div>
                            <div>{{ $tenant->emergency_contact_phone ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('Leases') }}</h2>

                    @if ($leases->isEmpty())
                        <div class="text-center py-4">
                            <i class="bi bi-file-earmark-text display-6 text-secondary"></i>
                            <p class="mt-2 mb-0 text-secondary">No leases yet.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>{{ __('Unit') }}</th>
                                        <th>{{ __('Dates') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th class="text-end">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($leases as $lease)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $lease->unit->unit_number }}</div>
                                                <div class="small text-secondary">{{ $lease->unit->building->property->name }}</div>
                                            </td>
                                            <td class="small">{{ $lease->start_date->format('M j, Y') }} &ndash; {{ $lease->end_date->format('M j, Y') }}</td>
                                            <td>
                                                <span class="badge text-bg-{{ $lease->status === 'active' ? 'success' : ($lease->status === 'expired' ? 'secondary' : 'danger') }} text-capitalize">{{ $lease->status }}</span>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('leases.show', $lease) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 mb-0">{{ __('Documents') }}</h2>
                        @can('create', \App\Modules\Documents\Models\Document::class)
                            <a href="{{ route('documents.create', ['documentable_type' => 'tenant', 'documentable_id' => $tenant->id]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-plus-lg me-1"></i> {{ __('Add Document') }}
                            </a>
                        @endcan
                    </div>

                    @if ($documents->isEmpty())
                        <p class="text-secondary small mb-0">No documents attached to this tenant yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>{{ __('Title') }}</th>
                                        <th>{{ __('Category') }}</th>
                                        <th class="text-end">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($documents as $item)
                                        <tr>
                                            <td class="fw-semibold">{{ $item->title }}</td>
                                            <td class="text-capitalize">{{ str_replace('_', ' ', $item->category) }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('documents.show', $item) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('Visitors') }}</h2>

                    @if ($visitors->isEmpty())
                        <p class="text-secondary small mb-0">No visitors have come to see this tenant yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>{{ __('Visitor') }}</th>
                                        <th>{{ __('Check In') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th class="text-end">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($visitors as $item)
                                        <tr>
                                            <td class="fw-semibold">{{ $item->visitor_name }}</td>
                                            <td class="small">{{ $item->check_in_at->format('M j, g:i A') }}</td>
                                            <td>
                                                @if ($item->isCheckedIn())
                                                    <span class="badge text-bg-success">{{ __('Checked In') }}</span>
                                                @else
                                                    <span class="badge text-bg-secondary">{{ __('Checked Out') }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('visitors.show', $item) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            @can('update', $tenant)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h6 mb-3">{{ __('Portal Access') }}</h2>

                        @if (session('portal_password'))
                            <div class="alert alert-warning small">
                                <strong>One-time password:</strong> <code>{{ session('portal_password') }}</code>
                                <div class="mt-1">Share this with the tenant now — it will not be shown again.</div>
                            </div>
                        @endif

                        @if ($tenant->hasPortalAccess())
                            <p class="small text-secondary mb-3"><span class="badge text-bg-success">{{ __('Enabled') }}</span> {{ $tenant->email }} can sign in to the tenant portal.</p>
                            <div class="d-flex gap-2">
                                <form action="{{ route('tenants.portal-access.enable', $tenant) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Reset Password') }}</button>
                                </form>
                                <form action="{{ route('tenants.portal-access.disable', $tenant) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Disable Access') }}</button>
                                </form>
                            </div>
                        @else
                            <p class="small text-secondary mb-3"><span class="badge text-bg-secondary">{{ __('Not Enabled') }}</span> This tenant cannot sign in to the portal.</p>
                            <form action="{{ route('tenants.portal-access.enable', $tenant) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary" {{ $tenant->email ? '' : 'disabled' }}>{{ __('Enable Portal Access') }}</button>
                            </form>
                            @unless ($tenant->email)
                                <div class="form-text">Add an email address to this tenant first.</div>
                            @endunless
                        @endif
                    </div>
                </div>
            @endcan

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('Photo') }}</h2>
                    @if ($tenant->photoUrl())
                        <img src="{{ $tenant->photoUrl() }}" alt="{{ $tenant->name }}" class="img-fluid rounded-3">
                    @else
                        <p class="text-secondary small mb-0">{{ __('No photo uploaded yet.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
