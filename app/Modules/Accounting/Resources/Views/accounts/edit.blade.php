@extends('layouts.app')

@section('title', 'Edit ' . $account->name)

@section('page-heading')
    <h1 class="h4 mb-0">Edit {{ $account->name }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('accounts.update', $account) }}">
                @csrf
                @method('PUT')
                @include('accounting::accounts._form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                    <a href="{{ route('accounts.show', $account) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
