@extends('layouts.app')

@section('title', 'Edit Lease')

@section('page-heading')
    <h1 class="h4 mb-0">Edit Lease</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('leases.update', $lease) }}">
                @csrf
                @method('PUT')
                @include('leases::_form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('leases.show', $lease) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
