@extends('landlord-portal::layout')

@section('title', __('My Documents'))

@section('content')
    <h1 class="h4 mb-4">{{ __('My Documents') }}</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($documents->isEmpty())
                <p class="text-secondary small mb-0">{{ __('No documents have been shared with you yet.') }}</p>
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
                                        @if ($item->fileUrl())
                                            <a href="{{ $item->fileUrl() }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i></a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
