@extends('layouts.app')

@section('title', 'New Tenant')

@section('page-heading')
    <h1 class="h4 mb-0">New Tenant</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('tenants.store') }}">
                @csrf
                @include('tenants::_form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Tenant</button>
                    <a href="{{ route('tenants.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
