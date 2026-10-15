<?php

namespace App\Modules\Leases\Services;

use App\Core\Services\BaseService;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Invoices\Services\InvoiceService;
use App\Modules\Leases\Models\Lease;
use App\Modules\Leases\Notifications\LeaseCreatedLandlordNotice;
use App\Modules\Leases\Notifications\LeaseCreatedTenantNotice;
use App\Modules\Leases\Repositories\LeaseRepositoryInterface;
use App\Modules\RentCollection\Services\RentInvoiceGenerator;
use App\Modules\Settings\Services\PaymentDetails;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @property LeaseRepositoryInterface $repository see App\Modules\Companies\Services\CompanyService
 *  for why this can't be redeclared as a typed constructor property.
 */
class LeaseService extends BaseService
{
    public function __construct(
        LeaseRepositoryInterface $repository,
        protected RentInvoiceGenerator $invoiceGenerator,
        protected InvoiceService $invoiceService,
        protected PaymentDetails $paymentDetails,
    ) {
        parent::__construct($repository);
    }

    public function paginate(
        ?string $search,
        ?string $status,
        int $perPage = 15,
        ?string $from = null,
        ?string $to = null,
    ): LengthAwarePaginator {
        return $this->repository->query()
            ->with(['tenant', 'unit.building.property'])
            ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                $query->whereHas('tenant', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('unit', fn ($q) => $q->where('unit_number', 'like', "%{$search}%"));
            }))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($from, fn ($query) => $query->whereDate('start_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('start_date', '<=', $to))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $attributes): Model
    {
        return DB::transaction(function () use ($attributes) {
            $lease = $this->repository->create($attributes);
            $this->syncUnitStatus($lease);

            $this->sendLeaseCreationMessages($lease);

            // The first invoice exists immediately, not whenever
            // GenerateRentInvoices' daily lead-time window happens to
            // open - a lease starting a month out shouldn't leave the
            // tenant with nothing to owe until days before move-in.
            $invoice = $this->invoiceGenerator->generateNextInvoiceForLease($lease, ignoreLeadTime: true);

            if ($invoice) {
                $this->applyDepositToInvoice($lease, $invoice);
            }

            return $lease;
        });
    }

    public function update(Model $model, array $attributes): Model
    {
        // terminated_at is derived, not user-supplied - the moment a
        // lease's status actually transitions to terminated, not
        // whatever the form happened to submit (same discipline as
        // Companies' trial_ends_at being system-set, not form input).
        if (($attributes['status'] ?? $model->status) === 'terminated' && $model->status !== 'terminated') {
            $attributes['terminated_at'] = now()->toDateString();
        }

        return DB::transaction(function () use ($model, $attributes) {
            $lease = $this->repository->update($model, $attributes);
            $this->syncUnitStatus($lease);

            return $lease;
        });
    }

    /**
     * Keeps units.status in sync with its leases - a unit becomes
     * "occupied" the moment a lease against it goes active, and reverts
     * to "vacant" the moment that lease stops being active, unless
     * another active lease on the same unit already accounts for it
     * (shouldn't happen given creation requires a vacant unit, but this
     * guards the invariant regardless of entry point).
     */
    protected function syncUnitStatus(Lease $lease): void
    {
        $unit = $lease->unit;

        if ($lease->status === 'active') {
            if ($unit->status !== 'occupied') {
                $unit->update(['status' => 'occupied']);
            }

            return;
        }

        $hasOtherActiveLease = $unit->leases()
            ->where('status', 'active')
            ->where('id', '!=', $lease->id)
            ->exists();

        if ($unit->status === 'occupied' && ! $hasOtherActiveLease) {
            $unit->update(['status' => 'vacant']);
        }
    }

    /**
     * Notifies the tenant (lease welcome) and, if the unit's property
     * has a landlord on file, the landlord (new-tenant notice) - see
     * App\Modules\Leases\Notifications\LeaseCreatedTenantNotice/
     * LeaseCreatedLandlordNotice.
     */
    protected function sendLeaseCreationMessages(Lease $lease): void
    {
        $tenant = $lease->tenant;
        $unit = $lease->unit;
        $property = $unit->building->property;
        $landlord = $property->landlord;

        // Where the tenant should pay - the company or the landlord's own
        // account, per the company's Settings choice.
        $company = $tenant->company;
        $payTo = $this->paymentDetails->resolve($company, $landlord);

        $tenant->notify(new LeaseCreatedTenantNotice([
            'tenant_name' => $tenant->name,
            'company_name' => $company->name,
            'deposit_amount' => number_format((float) $lease->deposit_amount, 0),
            'property_name' => $property->name,
            'unit_number' => $unit->unit_number,
            'rent_amount' => number_format($lease->rent_amount, 0),
            'start_date' => $lease->start_date->format('M j, Y'),
            'end_date' => $lease->end_date->format('M j, Y'),
            'bank_name' => $payTo['bank_name'],
            'account_name' => $payTo['account_name'],
            'account_number' => $payTo['account_number'],
            'control_number' => $tenant->control_number,
        ]));

        if ($landlord) {
            $landlord->notify(new LeaseCreatedLandlordNotice([
                'landlord_name' => $landlord->name,
                'property_name' => $property->name,
                'unit_number' => $unit->unit_number,
                'tenant_name' => $tenant->name,
                'tenant_phone' => $tenant->phone,
                'start_date' => $lease->start_date->format('M j, Y'),
                'end_date' => $lease->end_date->format('M j, Y'),
                'rent_amount' => number_format($lease->rent_amount, 0),
            ]));
        }
    }

    /**
     * A deposit entered on the lease form is money already collected at
     * signing, so it is recorded as a real payment against the lease's
     * first invoice (capped at that invoice's total): the invoice's
     * paid/balance/status reflect it, and InvoiceService::recordPayment()
     * sends the tenant and landlord payment confirmations through their
     * templates. A zero/empty deposit records nothing and sends nothing.
     */
    protected function applyDepositToInvoice(Lease $lease, Invoice $invoice): void
    {
        $deposit = (float) $lease->deposit_amount;

        if ($deposit <= 0) {
            return;
        }

        $this->invoiceService->recordPayment($invoice, [
            'amount' => min($deposit, (float) $invoice->total_amount),
            'paid_on' => now()->toDateString(),
            'method' => 'cash',
            'notes' => 'Deposit received at lease signing.',
            // Only staff are recorded here (a landlord's id isn't a user id).
            'recorded_by_id' => Auth::user() instanceof \App\Models\User ? Auth::id() : null,
        ]);
    }
}
