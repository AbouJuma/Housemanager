{{-- Session flashes and validation errors, handed to window.showToast()
     (resources/js/app.js) once the page loads. Field-level messages still
     render inline under each input; the toast is the at-a-glance summary. --}}
@php
    $toasts = collect([
        ['success', session('success')],
        ['error', session('error')],
        ['info', session('status')],
    ])->filter(fn ($toast) => filled($toast[1]) && is_string($toast[1]))->values();

    if ($errors->any()) {
        $count = $errors->count();
        $toasts->push(['error', $count === 1
            ? $errors->first()
            : __('Please fix :count fields: :first', ['count' => $count, 'first' => $errors->first()])]);
    }
@endphp
@if ($toasts->isNotEmpty())
    <script>
        window.__flashToasts = @json($toasts->map(fn ($toast) => ['type' => $toast[0], 'message' => $toast[1]]));
    </script>
@endif
