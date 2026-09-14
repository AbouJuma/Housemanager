@extends('layouts.app')

@section('title', 'Settings')

@section('page-heading')
    <h1 class="h4 mb-0">Settings</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('settings.update') }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="date_format" class="form-label">Date Format</label>
                    <input id="date_format" type="text" name="date_format" value="{{ old('date_format', $values['date_format']) }}" class="form-control @error('date_format') is-invalid @enderror" required>
                    <div class="form-text">PHP date() format, e.g. <code>M j, Y</code> &rarr; {{ now()->format($values['date_format']) }}</div>
                    @error('date_format') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
@endsection
