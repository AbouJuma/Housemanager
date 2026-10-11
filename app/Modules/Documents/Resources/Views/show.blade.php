@extends('layouts.app')

@section('title', $document->title)

@section('page-heading')
    <h1 class="h4 mb-0">{{ $document->title }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ $document->title }}</h1>
            <p class="text-secondary mb-0">
                <span class="badge text-bg-secondary text-capitalize">{{ str_replace('_', ' ', $document->category) }}</span>
                @if ($document->isExpired())
                    <span class="badge text-bg-danger">{{ __('Expired') }}</span>
                @elseif ($document->isExpiringSoon())
                    <span class="badge text-bg-warning">{{ __('Expiring Soon') }}</span>
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> {{ __('All Documents') }}
            </a>
            @can('update', $document)
                <a href="{{ route('documents.edit', $document) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i> {{ __('Edit') }}
                </a>
            @endcan
            @can('delete', $document)
                <form action="{{ route('documents.destroy', $document) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-outline-danger btn-sm js-confirm-delete" data-name="this document">
                        <i class="bi bi-trash me-1"></i> {{ __('Delete') }}
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('Details') }}</h2>
                    <div class="row g-3 small">
                        <div class="col-md-4">
                            <div class="text-secondary">{{ __('Attached To') }}</div>
                            <div>{{ $documentableLabel ?? 'Company-wide' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">{{ __('Expiry Date') }}</div>
                            <div>{{ $document->expiry_date?->toFormattedDateString() ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">{{ __('Uploaded By') }}</div>
                            <div>{{ $document->uploadedBy?->name ?? '—' }}</div>
                        </div>
                    </div>
                    @if ($document->description)
                        <hr>
                        <div class="text-secondary small mb-1">{{ __('Description') }}</div>
                        <p class="mb-0">{{ $document->description }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('File') }}</h2>
                    @if ($document->fileUrl())
                        <p class="small text-secondary mb-2">{{ $document->fileName() }}</p>
                        <a href="{{ $document->fileUrl() }}" target="_blank" rel="noopener" class="btn btn-sm btn-primary">
                            <i class="bi bi-download me-1"></i> {{ __('Download') }}
                        </a>
                    @else
                        <p class="text-secondary small mb-0">{{ __('No file attached.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
