@extends('layouts.app')

@section('title', 'New Role')

@section('page-heading')
    <h1 class="h4 mb-0">New Role</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('roles.store') }}">
                @csrf

                <div class="mb-4" style="max-width: 360px;">
                    <label for="name" class="form-label">Role Name</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required autofocus>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                @include('users::roles._permissions', ['assigned' => old('permissions', [])])

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Role</button>
                    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
