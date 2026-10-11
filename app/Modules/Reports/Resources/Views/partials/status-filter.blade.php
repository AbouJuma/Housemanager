{{-- Status filter for a report page - submits on change; the export
     buttons carry the same ?status= so files match what's on screen. --}}
<form method="GET" class="d-flex align-items-center gap-2">
    <label for="reportStatus" class="small text-secondary text-nowrap mb-0">{{ __('Status') }}</label>
    <select id="reportStatus" name="status" class="form-select form-select-sm" style="min-width: 170px;" onchange="this.form.submit()">
        <option value="">{{ __('All statuses') }}</option>
        @foreach ($statuses as $option)
            <option value="{{ $option }}" @selected($status === $option)>{{ __(ucfirst(str_replace('_', ' ', $option))) }}</option>
        @endforeach
    </select>
</form>
