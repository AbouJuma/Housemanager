@extends('layouts.app')

@section('title', 'New Vendor')

@section('page-heading')
    <h1 class="h4 mb-0">New Vendor</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('vendors.store') }}">
                @csrf
                @include('vendors::_form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Vendor</button>
                    <a href="{{ route('vendors.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
