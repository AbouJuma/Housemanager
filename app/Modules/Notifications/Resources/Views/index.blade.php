@extends('layouts.app')

@section('title', __('Notifications'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Notifications') }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ __('Notifications') }}</h1>
            <p class="text-secondary mb-0">{{ $notifications->total() }} {{ Str::plural('notification', $notifications->total()) }}</p>
        </div>
        @if ($notifications->isNotEmpty())
            <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm">Mark all read</button>
            </form>
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($notifications->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-bell display-4 text-secondary"></i>
                    <p class="mt-3 mb-1 fw-semibold">No notifications yet</p>
                    <p class="text-secondary">You'll see updates about your account and company here.</p>
                </div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($notifications as $notification)
                        <div class="list-group-item d-flex justify-content-between align-items-start gap-3 {{ $notification->read_at ? '' : 'bg-primary-subtle bg-opacity-25' }}">
                            <div>
                                <div class="fw-semibold">{{ $notification->data['title'] ?? 'Notification' }}</div>
                                <div class="text-secondary small">{{ $notification->data['message'] ?? '' }}</div>
                                <div class="text-secondary small">{{ $notification->created_at->diffForHumans() }}</div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                @if (! empty($notification->data['url']))
                                    <a href="{{ $notification->data['url'] }}" class="btn btn-sm btn-outline-secondary">View</a>
                                @endif
                                @unless ($notification->read_at)
                                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Mark read</button>
                                    </form>
                                @endunless
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-3">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
