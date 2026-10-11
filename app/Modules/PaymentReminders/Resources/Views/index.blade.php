@extends('layouts.app')

@section('title', __('Payment Reminder Rules'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Payment Reminder Rules') }}</h1>
@endsection

@section('content')
    <p class="text-secondary">
        Configure when rent and installment payment reminders go out. Add as many rules as you need - each one fires
        a reminder a number of days before (or after) the due date, optionally repeating.
    </p>

    @foreach ([
        'rent' => ['label' => 'Rent (tenants)', 'rules' => $rentRules],
        'sale_installment' => ['label' => 'Sale installments (buyers)', 'rules' => $saleInstallmentRules],
    ] as $context => $group)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h2 class="h6 mb-0">{{ $group['label'] }}</h2>
            </div>
            <div class="card-body">
                @if ($group['rules']->isEmpty())
                    <p class="text-secondary mb-3">No reminder rules configured for this context yet.</p>
                @else
                    <div class="table-responsive mb-3">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Timing</th>
                                    <th>Repeats</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($group['rules'] as $rule)
                                    <tr>
                                        <td>
                                            @if ($rule->offset_days > 0)
                                                {{ $rule->offset_days }} {{ Str::plural('day', $rule->offset_days) }} before due date
                                            @elseif ($rule->offset_days === 0)
                                                On the due date
                                            @else
                                                {{ abs($rule->offset_days) }} {{ Str::plural('day', abs($rule->offset_days)) }} after due date (overdue)
                                            @endif
                                        </td>
                                        <td>
                                            {{ $rule->repeat_every_days ? "Every {$rule->repeat_every_days} days" : 'Once' }}
                                        </td>
                                        <td>
                                            <span class="badge text-bg-{{ $rule->is_active ? 'success' : 'secondary' }}">
                                                {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <form action="{{ route('reminder-rules.update', $rule) }}" method="POST" class="d-inline-flex align-items-center gap-2">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="offset_days" value="{{ $rule->offset_days }}">
                                                <input type="hidden" name="repeat_every_days" value="{{ $rule->repeat_every_days }}">
                                                <input type="hidden" name="is_active" value="{{ $rule->is_active ? 0 : 1 }}">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                    {{ $rule->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                            <form action="{{ route('reminder-rules.destroy', $rule) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-danger js-confirm-delete" data-name="this reminder rule">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <form action="{{ route('reminder-rules.store') }}" method="POST" class="row g-2 align-items-end">
                    @csrf
                    <input type="hidden" name="context" value="{{ $context }}">
                    <div class="col-auto">
                        <label class="form-label small mb-1">Offset (days)</label>
                        <input type="number" name="offset_days" class="form-control form-control-sm" style="width: 110px;" placeholder="e.g. 3" required>
                        <div class="form-text">Positive = before due, 0 = on due date, negative = overdue</div>
                    </div>
                    <div class="col-auto">
                        <label class="form-label small mb-1">Repeat every (days)</label>
                        <input type="number" name="repeat_every_days" class="form-control form-control-sm" style="width: 110px;" placeholder="Leave blank = once">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg me-1"></i> Add rule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    <p class="text-secondary small">
        A reminder automatically stops once a tenant's lease is no longer active or their invoice is fully paid
        (rent), or once a sale is completed or an installment is fully paid (sale installments) - this isn't
        configurable here since it's always the right behavior.
    </p>
@endsection
