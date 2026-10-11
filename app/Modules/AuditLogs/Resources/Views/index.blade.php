@extends('layouts.app')

@section('title', __('Audit Logs'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Audit Logs') }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ __('Audit Logs') }}</h1>
            <p class="text-secondary mb-0">{{ $logs->total() }} {{ Str::plural('entry', $logs->total()) }}</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($logNames->isNotEmpty())
                <form method="GET" class="mb-3">
                    <div class="d-flex gap-2" style="max-width: 320px;">
                        <select name="log_name" class="form-select" onchange="this.form.submit()">
                            <option value="">{{ __('All areas') }}</option>
                            @foreach ($logNames as $name)
                                <option value="{{ $name }}" @selected(request('log_name') === $name)>{{ ucfirst($name) }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            @endif

            @if ($logs->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-clock-history display-4 text-secondary"></i>
                    <p class="mt-3 mb-1 fw-semibold">{{ __('No activity yet') }}</p>
                    <p class="text-secondary">{{ __('Changes to companies and users will show up here.') }}</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('When') }}</th>
                                @if (auth()->user()->isSaasOwner())
                                    <th>{{ __('Company') }}</th>
                                @endif
                                <th>{{ __('Area') }}</th>
                                <th>{{ __('Event') }}</th>
                                <th>{{ __('Subject') }}</th>
                                <th>{{ __('By') }}</th>
                                <th>{{ __('Changes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logs as $log)
                                <tr>
                                    <td class="text-nowrap">{{ $log->created_at->format(setting('date_format') . ' g:i A') }}</td>
                                    @if (auth()->user()->isSaasOwner())
                                        <td>{{ $log->company?->name ?? '—' }}</td>
                                    @endif
                                    <td><span class="badge text-bg-secondary">{{ $log->log_name }}</span></td>
                                    <td class="text-capitalize">{{ $log->event ?? $log->description }}</td>
                                    <td>
                                        {{ class_basename($log->subject_type) }}
                                        @if ($log->subject)
                                            <span class="text-secondary small">#{{ $log->subject->id }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $log->causer?->name ?? 'System' }}</td>
                                    <td>
                                        @php($changed = collect($log->properties->get('attributes', []))->keys())
                                        @if ($changed->isNotEmpty())
                                            <span class="text-secondary small">{{ $changed->implode(', ') }}</span>
                                        @else
                                            <span class="text-secondary small">&mdash;</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
