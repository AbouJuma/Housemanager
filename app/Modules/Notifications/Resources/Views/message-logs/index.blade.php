@extends('layouts.app')

@section('title', __('Message Log'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Message Log') }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                @if ($companies !== null)
                    <div class="col-auto">
                        <label class="form-label small mb-1">{{ __('Company') }}</label>
                        <select name="company_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Select a company…</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected(request('company_id') == $company->id)>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-auto">
                    <label class="form-label small mb-1">Date range</label>
                    <select name="date_preset" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="this_month" @selected(request('date_preset', 'this_month') === 'this_month')>This month</option>
                        <option value="this_year" @selected(request('date_preset') === 'this_year')>This year</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">Recipient type</label>
                    <select name="notifiable_type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($notifiableTypes as $class => $label)
                            <option value="{{ $class }}" @selected(request('notifiable_type') === $class)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">{{ __('Channel') }}</label>
                    <select name="channel" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">{{ __('All') }}</option>
                        <option value="sms" @selected(request('channel') === 'sms')>SMS</option>
                        <option value="mail" @selected(request('channel') === 'mail')>{{ __('Email') }}</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">{{ __('Status') }}</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">{{ __('All') }}</option>
                        <option value="sent" @selected(request('status') === 'sent')>{{ __('Sent') }}</option>
                        <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                    </select>
                </div>
                <div class="col-auto text-secondary small">
                    {{ $startDate->format('M j, Y') }} – {{ $endDate->format('M j, Y') }}
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($companies !== null && ! request()->filled('company_id'))
                <p class="text-secondary text-center py-5 mb-0">Select a company above to view its message log.</p>
            @elseif ($logs->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-envelope display-4 text-secondary"></i>
                    <p class="mt-3 mb-0 fw-semibold">No messages in this range</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Sent') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>Recipient</th>
                                <th>{{ __('Channel') }}</th>
                                <th>Message</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logs as $log)
                                <tr>
                                    <td class="small text-secondary text-nowrap">{{ $log->created_at->format('M j, Y g:i A') }}</td>
                                    <td class="small">{{ $notifiableTypes[$log->notifiable_type] ?? class_basename($log->notifiable_type) }}</td>
                                    <td class="small">{{ $log->recipient }}</td>
                                    <td class="small text-uppercase">{{ $log->channel }}</td>
                                    <td class="small">{{ $log->message_type }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ $log->status === 'sent' ? 'success' : 'danger' }}">{{ ucfirst($log->status) }}</span>
                                        @if ($log->status === 'failed' && $log->error_message)
                                            <div class="small text-danger">{{ Str::limit($log->error_message, 80) }}</div>
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
