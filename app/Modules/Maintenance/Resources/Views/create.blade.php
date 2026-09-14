@extends('layouts.app')

@section('title', 'New Maintenance Request')

@section('page-heading')
    <h1 class="h4 mb-0">New Maintenance Request</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('maintenance-requests.store') }}">
                @csrf
                @include('maintenance::_form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Log Request</button>
                    <a href="{{ route('maintenance-requests.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
