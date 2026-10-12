@extends('buyer-portal::layout')

@section('title', __('My Purchases'))

@section('content')
    <h1 class="h4 mb-4">{{ __('My Purchases') }}</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @forelse ($sales as $sale)
                @include('buyer-portal::purchases._card', ['sale' => $sale])
            @empty
                <p class="text-secondary small mb-0">{{ __('No purchases on file yet.') }}</p>
            @endforelse
        </div>
    </div>
@endsection
