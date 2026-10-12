{{--
    Date filter for reports and lists: quick periods (today, this week,
    this month...) or custom dates, and optionally a by day / week /
    month breakdown. Params: $route, $period (null = custom), $from,
    $to (Y-m-d), $group (null = no breakdown picker), $keep (other
    query values to keep, e.g. status).
--}}
@php
    $keep = array_filter($keep ?? [], fn ($value) => $value !== null && $value !== '');
    $quick = [
        'today' => __('Today'),
        'this_week' => __('This week'),
        'this_month' => __('This month'),
        'last_month' => __('Last month'),
        'this_year' => __('This year'),
        'last_year' => __('Last year'),
    ];
    $groups = ['day' => __('Daily'), 'week' => __('Weekly'), 'month' => __('Monthly')];
@endphp

<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach ($quick as $value => $label)
        <a href="{{ route($route, [...$keep, 'period' => $value, 'group' => request('group')]) }}"
           class="btn btn-sm rounded-pill {{ $period === $value ? 'btn-primary' : 'btn-outline-primary' }}">{{ $label }}</a>
    @endforeach
</div>

<form method="GET" class="row g-2 align-items-end">
    @foreach ($keep as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
    <div class="col-6 col-md-3">
        <label class="form-label small mb-1">{{ __('From') }}</label>
        <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm">
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label small mb-1">{{ __('To') }}</label>
        <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm">
    </div>
    @isset($group)
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1">{{ __('Show by') }}</label>
            <select name="group" class="form-select form-select-sm">
                @foreach ($groups as $value => $label)
                    <option value="{{ $value }}" @selected($group === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    @endisset
    <div class="col-6 col-md-2">
        <button class="btn btn-sm btn-primary w-100"><i class="bi bi-funnel me-1"></i>{{ __('Apply') }}</button>
    </div>
</form>
