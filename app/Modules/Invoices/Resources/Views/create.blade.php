@extends('layouts.app')

@section('title', 'New Invoice')

@section('page-heading')
    <h1 class="h4 mb-0">New Invoice</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('invoices.store') }}">
                @csrf
                @include('invoices::_form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Invoice</button>
                    <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
