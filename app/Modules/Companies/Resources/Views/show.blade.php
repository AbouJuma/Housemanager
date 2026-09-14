@extends('layouts.app')

@section('title', $company->name)

@section('page-heading')
    <h1 class="h4 mb-0">{{ $company->name }}</h1>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="badge text-bg-{{ match ($company->status) {
                                'active' => 'success',
                                'trial' => 'info',
                                'suspended' => 'warning',
                                default => 'secondary',
                            } }} mb-2">{{ ucfirst($company->status) }}</span>
                            <h2 class="h5 mb-0">{{ $company->name }}</h2>
                            <p class="text-secondary mb-0">{{ $company->slug }}</p>
                        </div>
                        @can('update', $company)
                            <a href="{{ route('companies.edit', $company) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-pencil me-1"></i> Edit
                            </a>
                        @endcan
                    </div>

                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-secondary fw-normal">Email</dt>
                        <dd class="col-sm-8">{{ $company->email ?: '—' }}</dd>
                        <dt class="col-sm-4 text-secondary fw-normal">Phone</dt>
                        <dd class="col-sm-8">{{ $company->phone ?: '—' }}</dd>
                        <dt class="col-sm-4 text-secondary fw-normal">Website</dt>
                        <dd class="col-sm-8">{{ $company->website ?: '—' }}</dd>
                        <dt class="col-sm-4 text-secondary fw-normal">Address</dt>
                        <dd class="col-sm-8">
                            {{ collect([$company->address_line1, $company->address_line2, $company->city, $company->state, $company->postal_code, $company->country_code])->filter()->implode(', ') ?: '—' }}
                        </dd>
                        <dt class="col-sm-4 text-secondary fw-normal">Timezone</dt>
                        <dd class="col-sm-8">{{ $company->timezone }}</dd>
                        <dt class="col-sm-4 text-secondary fw-normal">Locale</dt>
                        <dd class="col-sm-8">{{ $company->locale }}</dd>
                        <dt class="col-sm-4 text-secondary fw-normal">Currency</dt>
                        <dd class="col-sm-8">{{ $company->currency_code }}</dd>
                        @if ($company->status === 'trial' && $company->trial_ends_at)
                            <dt class="col-sm-4 text-secondary fw-normal">Trial ends</dt>
                            <dd class="col-sm-8">{{ $company->trial_ends_at->format(setting('date_format')) }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            @can('update', $company)
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h3 class="h6 mb-3">Branding</h3>

                        <div class="d-flex align-items-center gap-3 mb-3">
                            @if ($company->logoUrl())
                                <img src="{{ $company->logoUrl() }}" alt="{{ $company->name }} logo" class="rounded" style="width: 56px; height: 56px; object-fit: cover;">
                            @else
                                <div class="rounded bg-body-secondary d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                                    <i class="bi bi-building text-secondary"></i>
                                </div>
                            @endif
                            <div>
                                <div class="fw-semibold">{{ $company->name }}</div>
                                <div class="text-secondary small">Logo</div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('companies.branding.update', $company) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label class="form-label">Logo</label>
                                <input type="file" name="logo" accept="image/*" class="form-control @error('logo') is-invalid @enderror">
                                @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label">Primary Color</label>
                                    <input type="color" name="primary_color" value="{{ old('primary_color', $company->primary_color ?? '#0d6efd') }}" class="form-control form-control-color w-100 @error('primary_color') is-invalid @enderror">
                                    @error('primary_color') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Secondary Color</label>
                                    <input type="color" name="secondary_color" value="{{ old('secondary_color', $company->secondary_color ?? '#6c757d') }}" class="form-control form-control-color w-100 @error('secondary_color') is-invalid @enderror">
                                    @error('secondary_color') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Save Branding</button>
                        </form>
                    </div>
                </div>
            @endcan
        </div>
    </div>

    @can('viewAny', \App\Modules\Subscriptions\Models\Subscription::class)
        <div class="row g-4 mt-0">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h2 class="h6 mb-0">Subscription</h2>
                            @if ($company->status === 'suspended')
                                <form method="POST" action="{{ route('companies.subscription.reactivate', $company) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">Reactivate Company</button>
                                </form>
                            @endif
                        </div>

                        @if ($subscription)
                            <dl class="row mb-3">
                                <dt class="col-sm-4 text-secondary fw-normal">Plan</dt>
                                <dd class="col-sm-8">{{ $subscription->plan->name }}</dd>
                                <dt class="col-sm-4 text-secondary fw-normal">Status</dt>
                                <dd class="col-sm-8">
                                    <span class="badge text-bg-{{ match ($subscription->status) {
                                        'active' => 'success',
                                        'trialing' => 'info',
                                        'past_due' => 'warning',
                                        default => 'secondary',
                                    } }}">{{ ucfirst(str_replace('_', ' ', $subscription->status)) }}</span>
                                </dd>
                                <dt class="col-sm-4 text-secondary fw-normal">Current Period</dt>
                                <dd class="col-sm-8">{{ $subscription->current_period_start->format('M j, Y') }} &ndash; {{ $subscription->current_period_end->format('M j, Y') }}</dd>
                            </dl>

                            <div class="d-flex flex-wrap gap-2 mb-4">
                                @if ($subscription->status === 'trialing')
                                    <form method="POST" action="{{ route('subscriptions.extend-trial', $subscription) }}" class="d-flex gap-1">
                                        @csrf
                                        <input type="number" name="days" min="1" max="365" value="14" class="form-control form-control-sm" style="width: 80px;" required>
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Extend Trial</button>
                                    </form>
                                @endif
                                @if (in_array($subscription->status, ['active', 'past_due']))
                                    <form method="POST" action="{{ route('subscriptions.renew', $subscription) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Renew</button>
                                    </form>
                                @endif
                                @if ($subscription->status !== 'canceled')
                                    <form method="POST" action="{{ route('subscriptions.cancel', $subscription) }}" onsubmit="return confirm('Cancel this subscription?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Cancel Subscription</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('subscriptions.invoices.generate', $subscription) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Generate Invoice</button>
                                </form>
                            </div>
                        @else
                            <p class="text-secondary small mb-3">This company has no subscription yet.</p>
                        @endif

                        <h3 class="h6 mb-2">Change Plan</h3>
                        <form method="POST" action="{{ route('companies.subscription.change-plan', $company) }}" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-sm-6">
                                <label class="form-label small">Plan</label>
                                <select name="subscription_plan_id" class="form-select form-select-sm @error('subscription_plan_id') is-invalid @enderror" required>
                                    <option value="">Select a plan&hellip;</option>
                                    @foreach ($subscriptionPlans as $plan)
                                        <option value="{{ $plan->id }}" {{ $subscription && $subscription->subscription_plan_id === $plan->id ? 'selected' : '' }}>
                                            {{ $plan->name }} ({{ ucfirst($plan->billing_cycle) }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('subscription_plan_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label small">Status</label>
                                <select name="status" class="form-select form-select-sm @error('status') is-invalid @enderror">
                                    <option value="trialing">Trialing</option>
                                    <option value="active">Active</option>
                                </select>
                                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-sm-2">
                                <button type="submit" class="btn btn-sm btn-primary w-100">Save</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Billing History</h2>

                        @if ($subscriptionInvoices->isEmpty())
                            <p class="text-secondary small mb-0">No invoices generated yet.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Amount</th>
                                            <th>Issue Date</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($subscriptionInvoices as $invoice)
                                            <tr>
                                                <td class="fw-semibold">{{ $invoice->invoice_number }}</td>
                                                <td>{{ number_format($invoice->amount, 2) }}</td>
                                                <td class="small">{{ $invoice->issue_date->format('M j, Y') }}</td>
                                                <td class="small">{{ $invoice->due_date->format('M j, Y') }}</td>
                                                <td>
                                                    <span class="badge text-bg-{{ match ($invoice->status) {
                                                        'paid' => 'success',
                                                        'void' => 'secondary',
                                                        default => $invoice->isOverdue() ? 'danger' : 'warning',
                                                    } }}">{{ ucfirst($invoice->status) }}</span>
                                                </td>
                                                <td class="text-end">
                                                    @if ($invoice->status === 'unpaid')
                                                        <form method="POST" action="{{ route('subscription-invoices.mark-paid', $invoice) }}" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-success">Mark Paid</button>
                                                        </form>
                                                        <form method="POST" action="{{ route('subscription-invoices.void', $invoice) }}" class="d-inline" onsubmit="return confirm('Void this invoice?');">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">Void</button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endcan
@endsection
