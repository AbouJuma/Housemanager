@extends('layouts.app')

@section('title', 'Edit ' . $document->title)

@section('page-heading')
    <h1 class="h4 mb-0">Edit {{ $document->title }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($documentableLabel)
                <div class="alert alert-info small">
                    <i class="bi bi-paperclip me-1"></i> Attached to: <strong>{{ $documentableLabel }}</strong>
                </div>
            @endif

            <form method="POST" action="{{ route('documents.update', $document) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('documents::_form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('documents.show', $document) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
