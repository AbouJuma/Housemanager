@extends('layouts.app')

@section('title', __('New Journal Entry'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('New Journal Entry') }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('journal-entries.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Entry Date</label>
                        <input type="date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}" class="form-control @error('entry_date') is-invalid @enderror" required>
                        @error('entry_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Reference') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
                        <input type="text" name="reference" value="{{ old('reference') }}" class="form-control @error('reference') is-invalid @enderror">
                        @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('Description') }}</label>
                        <input type="text" name="description" value="{{ old('description') }}" class="form-control @error('description') is-invalid @enderror" required>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <hr>
                        <h2 class="h6 mb-3">{{ __('Lines') }}</h2>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>{{ __('Account') }}</th>
                                        <th style="width: 140px;">{{ __('Debit') }}</th>
                                        <th style="width: 140px;">{{ __('Credit') }}</th>
                                        <th>{{ __('Memo') }}</th>
                                        <th style="width: 40px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="journalLinesBody">
                                    @for ($i = 0; $i < 2; $i++)
                                        <tr class="journal-line-row">
                                            <td>
                                                <select name="lines[{{ $i }}][account_id]" class="form-select form-select-sm" required>
                                                    <option value="">&mdash; Select an account &mdash;</option>
                                                    @foreach ($accounts as $acc)
                                                        <option value="{{ $acc->id }}">{{ $acc->code }} &middot; {{ $acc->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="number" step="0.01" min="0" name="lines[{{ $i }}][debit]" class="form-control form-control-sm js-line-debit"></td>
                                            <td><input type="number" step="0.01" min="0" name="lines[{{ $i }}][credit]" class="form-control form-control-sm js-line-credit"></td>
                                            <td><input type="text" name="lines[{{ $i }}][memo]" class="form-control form-control-sm"></td>
                                            <td><button type="button" class="btn btn-sm btn-outline-danger js-remove-line"><i class="bi bi-trash"></i></button></td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                        @error('lines') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                        <button type="button" id="addJournalLineBtn" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-plus-lg me-1"></i> Add Line
                        </button>
                        <div class="text-end small mt-2">
                            Debits: <span id="journalTotalDebits" class="fw-semibold">0.00</span>
                            &nbsp;&nbsp;Credits: <span id="journalTotalCredits" class="fw-semibold">0.00</span>
                            &nbsp;&nbsp;<span id="journalBalanceStatus"></span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Post Journal Entry</button>
                    <a href="{{ route('journal-entries.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>

    <template id="journalLineRowTemplate">
        <tr class="journal-line-row">
            <td>
                <select name="lines[__INDEX__][account_id]" class="form-select form-select-sm" required>
                    <option value="">&mdash; Select an account &mdash;</option>
                    @foreach ($accounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->code }} &middot; {{ $acc->name }}</option>
                    @endforeach
                </select>
            </td>
            <td><input type="number" step="0.01" min="0" name="lines[__INDEX__][debit]" class="form-control form-control-sm js-line-debit"></td>
            <td><input type="number" step="0.01" min="0" name="lines[__INDEX__][credit]" class="form-control form-control-sm js-line-credit"></td>
            <td><input type="text" name="lines[__INDEX__][memo]" class="form-control form-control-sm"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger js-remove-line"><i class="bi bi-trash"></i></button></td>
        </tr>
    </template>
@endsection
