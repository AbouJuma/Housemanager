@php($document = $document ?? null)
@php($categories = ['contract', 'lease_agreement', 'id_document', 'certificate', 'insurance', 'deed', 'invoice', 'license', 'other'])

<div class="row g-3">
    <div class="col-12">
        <label class="form-label">{{ __('Title') }}</label>
        <input type="text" name="title" value="{{ old('title', $document?->title) }}" class="form-control @error('title') is-invalid @enderror" required>
        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Category') }}</label>
        <select name="category" class="form-select @error('category') is-invalid @enderror" required>
            @foreach ($categories as $category)
                <option value="{{ $category }}" @selected(old('category', $document?->category ?? 'other') === $category)>{{ ucfirst(str_replace('_', ' ', $category)) }}</option>
            @endforeach
        </select>
        @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Expiry Date') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
        <input type="date" name="expiry_date" value="{{ old('expiry_date', $document?->expiry_date?->toDateString()) }}" class="form-control @error('expiry_date') is-invalid @enderror">
        <div class="form-text">For anything that needs renewing — a license, certificate, or insurance policy.</div>
        @error('expiry_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">{{ __('Description') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
        <textarea name="description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description', $document?->description) }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">{{ $document ? 'Replace File' : 'File' }} <span class="text-secondary fw-normal">{{ $document ? '(optional — leave blank to keep the current file)' : '' }}</span></label>
        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" {{ $document ? '' : 'required' }}>
        <div class="form-text">PDF, Word, Excel, or image files up to 10MB.</div>
        @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
        @if ($document && $document->fileUrl())
            <div class="mt-2 small">
                Current file: <a href="{{ $document->fileUrl() }}" target="_blank" rel="noopener">{{ $document->fileName() }}</a>
            </div>
        @endif
    </div>
</div>
