@extends('layouts.app')

@section('title', 'New Inventory Item')

@section('page-heading')
    <h1 class="h4 mb-0">New Inventory Item</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('inventory-items.store') }}">
                @csrf
                @include('inventory::_form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Add Item</button>
                    <a href="{{ route('inventory-items.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
