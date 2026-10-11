@extends('layouts.app')

@section('title', __('New Role'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('New Role') }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('roles.store') }}">
                @csrf

                <div class="mb-4" style="max-width: 360px;">
                    <label for="name" class="form-label">{{ __('Role Name') }}</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required autofocus>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- One-click starting points; the boxes below stay editable. --}}
                <div class="mb-4" id="role-presets">
                    <div class="form-label">{{ __('Start from a common role') }}</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach (\App\Modules\Users\Support\RolePresets::available() as $key => $preset)
                            <button type="button" class="btn btn-sm btn-outline-primary" data-role-preset="{{ $key }}"
                                    data-label="{{ __($preset['label']) }}" data-permissions='@json($preset['permissions'])'
                                    title="{{ __($preset['hint']) }}">
                                {{ __($preset['label']) }}
                            </button>
                        @endforeach
                    </div>
                    <div class="form-text">{{ __('Staff see in the mobile app only what their role\'s permissions allow.') }}</div>
                </div>

                @include('users::roles._permissions', ['assigned' => old('permissions', [])])

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Role</button>
                    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-role-preset]').forEach((button) => {
            button.addEventListener('click', () => {
                const wanted = JSON.parse(button.dataset.permissions);
                document.querySelectorAll('input[name="permissions[]"]').forEach((box) => {
                    box.checked = wanted.includes(box.value);
                });
                const name = document.getElementById('name');
                if (!name.value.trim()) name.value = button.dataset.label;
                document.querySelectorAll('[data-role-preset]').forEach((b) => b.classList.toggle('active', b === button));
            });
        });
    </script>
@endpush
