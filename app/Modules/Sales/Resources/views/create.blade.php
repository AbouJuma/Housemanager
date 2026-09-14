@extends('layouts.app')

@section('title', 'New Sale')

@section('page-heading')
    <h1 class="h4 mb-0">New Sale</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('sales.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Unit <span class="text-danger">*</span></label>
                        <select name="unit_id" class="form-select @error('unit_id') is-invalid @enderror" required>
                            <option value="">&mdash; Select a unit &mdash;</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected($selectedUnitUuid === $unit->uuid)>{{ $unit->unit_number }} · {{ $unit->building->property->name }} · {{ $unit->building->name }}</option>
                            @endforeach
                        </select>
                        @error('unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Buyer</label>
                        <div class="d-flex gap-2">
                            <select name="new_buyer_id" id="buyerSelect" class="form-select @error('new_buyer_id') is-invalid @enderror flex-grow-1">
                                <option value="">Select a buyer (optional)</option>
                                @foreach ($buyers as $buyer)
                                    <option value="{{ $buyer->id }}" @selected(old('new_buyer_id') === $buyer->id)>{{ $buyer->name }} ({{ $buyer->phone }})</option>
                                @endforeach
                            </select>
                            @can('create', \App\Modules\Sales\Models\Buyer::class)
                                <button type="button" class="btn btn-outline-secondary flex-shrink-0" data-bs-toggle="modal" data-bs-target="#buyerModal">
                                    <i class="bi bi-plus-lg"></i> Add New
                                </button>
                            @endcan
                        </div>
                        @error('new_buyer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sale Date <span class="text-danger">*</span></label>
                        <input type="date" name="sale_date" value="{{ old('sale_date', now()->toDateString()) }}" class="form-control @error('sale_date') is-invalid @enderror" required>
                        @error('sale_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Total Price <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="total_price" value="{{ old('total_price') }}" class="form-control @error('total_price') is-invalid @enderror" required>
                        @error('total_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Down Payment <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="down_payment" value="{{ old('down_payment', 0) }}" class="form-control @error('down_payment') is-invalid @enderror" required>
                        @error('down_payment') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Installment Period (Months) <span class="text-danger">*</span></label>
                        <input type="number" min="1" max="360" name="installment_period" value="{{ old('installment_period') }}" class="form-control @error('installment_period') is-invalid @enderror" required>
                        @error('installment_period') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Installment Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="installment_amount" value="{{ old('installment_amount') }}" class="form-control @error('installment_amount') is-invalid @enderror" required>
                        @error('installment_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Record Sale</button>
                    <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Buyer Modal -->
    <div class="modal fade" id="buyerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Buyer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="buyerForm">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" rows="2" class="form-control"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ID Number</label>
                                <input type="text" name="id_number" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="2" class="form-control"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveBuyer">Save Buyer</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Select2 for buyer dropdown (basic version)
            $('#buyerSelect').select2({
                placeholder: 'Select a buyer (optional)',
                allowClear: true,
                width: '100%'
            });

            // Buyer modal handling
            const buyerModal = new bootstrap.Modal(document.getElementById('buyerModal'));
            const buyerForm = document.getElementById('buyerForm');
            const saveBuyerBtn = document.getElementById('saveBuyer');

            document.querySelector('[data-bs-toggle="modal"][data-bs-target="#buyerModal"]').addEventListener('click', function(e) {
                e.preventDefault();
                buyerModal.show();
            });

            saveBuyerBtn.addEventListener('click', function() {
                const formData = new FormData(buyerForm);
                formData.append('_token', '{{ csrf_token() }}');

                fetch('{{ route('buyers.store') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Add new buyer to Select2
                        const newOption = new Option(data.buyer.name + ' (' + data.buyer.phone + ')', data.buyer.id, true, true);
                        $('#buyerSelect').append(newOption).trigger('change');
                        buyerModal.hide();
                        buyerForm.reset();
                    } else {
                        alert('Error creating buyer: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    alert('Error: ' + error);
                });
            });
        });
    </script>
    @endpush
@endsection
