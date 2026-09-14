@extends('layouts.app')

@section('title', $landlord->name)

@section('page-heading')
    <h1 class="h4 mb-0">{{ $landlord->name }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ $landlord->name }}</h1>
            <p class="text-secondary mb-0 text-capitalize">{{ $landlord->type }} &middot; {{ $landlord->status }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('landlords.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> All Landlords
            </a>
            @can('update', $landlord)
                <a href="{{ route('landlords.edit', $landlord) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            @endcan
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">Contact</h2>
                    <div class="row g-3 small">
                        <div class="col-md-4">
                            <div class="text-secondary">Email</div>
                            <div>{{ $landlord->email ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Phone</div>
                            <div>{{ $landlord->phone ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Secondary Phone</div>
                            <div>{{ $landlord->secondary_phone ?? '—' }}</div>
                        </div>
                        <div class="col-md-8">
                            <div class="text-secondary">Address</div>
                            <div>{{ collect([$landlord->address_line1, $landlord->address_line2, $landlord->city, $landlord->state, $landlord->postal_code, $landlord->country_code])->filter()->implode(', ') ?: '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Tax ID</div>
                            <div>{{ $landlord->tax_id ?? '—' }}</div>
                        </div>
                    </div>
                    @if ($landlord->notes)
                        <hr>
                        <div class="text-secondary small mb-1">Notes</div>
                        <p class="mb-0">{{ $landlord->notes }}</p>
                    @endif
                </div>
            </div>

            @if ($landlord->bank_details)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Bank Details</h2>
                        <div class="row g-3 small">
                            <div class="col-md-4">
                                <div class="text-secondary">Bank</div>
                                <div>{{ $landlord->bankDetail('name') ?? '—' }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-secondary">Account Name</div>
                                <div>{{ $landlord->bankDetail('account_name') ?? '—' }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-secondary">Account Number</div>
                                <div>{{ $landlord->bankDetail('account_number') ?? '—' }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-secondary">Branch</div>
                                <div>{{ $landlord->bankDetail('branch') ?? '—' }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-secondary">SWIFT / Branch Code</div>
                                <div>{{ $landlord->bankDetail('swift_code') ?? '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">Properties</h2>

                    @if ($properties->isEmpty())
                        <div class="text-center py-4">
                            <i class="bi bi-buildings display-6 text-secondary"></i>
                            <p class="mt-2 mb-0 text-secondary">No properties assigned yet.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Type</th>
                                        <th>Buildings</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($properties as $property)
                                        <tr>
                                            <td class="fw-semibold">{{ $property->name }}</td>
                                            <td class="text-capitalize">{{ str_replace('_', ' ', $property->type) }}</td>
                                            <td>{{ $property->buildings_count }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('properties.show', $property) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
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
                        <h2 class="h6 mb-0">Documents</h2>
                        @can('create', \App\Modules\Documents\Models\Document::class)
                            <a href="{{ route('documents.create', ['documentable_type' => 'landlord', 'documentable_id' => $landlord->id]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-plus-lg me-1"></i> Add Document
                            </a>
                        @endcan
                    </div>

                    @if ($documents->isEmpty())
                        <p class="text-secondary small mb-0">No documents attached to this landlord yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th class="text-end">Actions</th>
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
        </div>

        <div class="col-lg-4">
            @can('update', $landlord)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Portal Access</h2>

                        @if (session('portal_password'))
                            <div class="alert alert-warning small">
                                <strong>One-time password:</strong> <code>{{ session('portal_password') }}</code>
                                <div class="mt-1">Share this with the landlord now — it will not be shown again.</div>
                            </div>
                        @endif

                        @if ($landlord->hasPortalAccess())
                            <p class="small text-secondary mb-3"><span class="badge text-bg-success">Enabled</span> {{ $landlord->email }} can sign in to the landlord portal.</p>
                            <div class="d-flex gap-2">
                                <form action="{{ route('landlords.portal-access.enable', $landlord) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Reset Password</button>
                                </form>
                                <form action="{{ route('landlords.portal-access.disable', $landlord) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Disable Access</button>
                                </form>
                            </div>
                        @else
                            <p class="small text-secondary mb-3"><span class="badge text-bg-secondary">Not Enabled</span> This landlord cannot sign in to the portal.</p>
                            <form action="{{ route('landlords.portal-access.enable', $landlord) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary" {{ $landlord->email ? '' : 'disabled' }}>Enable Portal Access</button>
                            </form>
                            @unless ($landlord->email)
                                <div class="form-text">Add an email address to this landlord first.</div>
                            @endunless
                        @endif
                    </div>
                </div>
            @endcan

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">Photo</h2>
                    @if ($landlord->photoUrl())
                        <img src="{{ $landlord->photoUrl() }}" alt="{{ $landlord->name }}" class="img-fluid rounded-3">
                    @else
                        <p class="text-secondary small mb-0">No photo uploaded yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
