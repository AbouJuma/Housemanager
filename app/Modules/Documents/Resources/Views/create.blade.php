@extends('layouts.app')

@section('title', 'New Document')

@section('page-heading')
    <h1 class="h4 mb-0">New Document</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                @csrf

                @if ($documentableLabel)
                    <input type="hidden" name="documentable_type" value="{{ $documentableType }}">
                    <input type="hidden" name="documentable_id" value="{{ $documentableId }}">
                    <div class="alert alert-info small">
                        <i class="bi bi-paperclip me-1"></i> Attaching to: <strong>{{ $documentableLabel }}</strong>
                    </div>
                @else
                    <div class="alert alert-secondary small">
                        <i class="bi bi-info-circle me-1"></i> This document won't be attached to a specific record — it'll be a company-wide document. To attach one to a property, landlord, tenant, lease, or vendor, use the "Add Document" button on that record's own page instead.
                    </div>
                @endif

                @include('documents::_form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Upload Document</button>
                    <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
