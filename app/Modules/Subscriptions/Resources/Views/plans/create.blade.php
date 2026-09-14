@extends('layouts.app')

@section('title', 'New Subscription Plan')

@section('page-heading')
    <h1 class="h4 mb-0">New Subscription Plan</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('subscription-plans.store') }}">
                @csrf
                @include('subscriptions::plans._form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Plan</button>
                    <a href="{{ route('subscription-plans.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
