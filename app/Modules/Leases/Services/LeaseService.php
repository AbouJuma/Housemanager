<?php

namespace App\Modules\Leases\Services;

use App\Core\Services\BaseService;
use App\Modules\Leases\Models\Lease;
use App\Modules\Leases\Repositories\LeaseRepositoryInterface;
use App\Modules\Tenants\Services\BeemsSmsService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * @property LeaseRepositoryInterface $repository see App\Modules\Companies\Services\CompanyService
 *  for why this can't be redeclared as a typed constructor property.
 */
class LeaseService extends BaseService
{
    protected BeemsSmsService $smsService;

    public function __construct(LeaseRepositoryInterface $repository, BeemsSmsService $smsService)
    {
        parent::__construct($repository);
        $this->smsService = $smsService;
    }

    public function paginate(?string $search, ?string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->query()
            ->with(['tenant', 'unit.building.property'])
            ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                $query->whereHas('tenant', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('unit', fn ($q) => $q->where('unit_number', 'like', "%{$search}%"));
            }))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $attributes): Model
    {
        return DB::transaction(function () use ($attributes) {
            $lease = $this->repository->create($attributes);
            $this->syncUnitStatus($lease);

            // Send lease creation message to tenant
            $this->sendLeaseCreationMessage($lease);

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
     * Send lease creation message to tenant
     */
    protected function sendLeaseCreationMessage(Lease $lease): void
    {
        try {
            $tenant = $lease->tenant;
            $unit = $lease->unit;
            $property = $unit->building->property;
            $landlord = $property->landlord;

            // Get bank details from landlord
            $bankName = 'N/A';
            $accountNumber = 'N/A';
            
            if ($landlord) {
                $bankDetails = $landlord->bank_details ?? [];
                $bankName = $bankDetails['bank_name'] ?? 'N/A';
                $accountNumber = $bankDetails['account_number'] ?? 'N/A';
            }

            $data = [
                'tenant_name' => $tenant->name,
                'property_name' => $property->name,
                'unit_number' => $unit->unit_number,
                'rent_amount' => number_format($lease->monthly_rent, 0),
                'start_date' => $lease->start_date,
                'end_date' => $lease->end_date,
                'bank_name' => $bankName,
                'account_number' => $accountNumber,
                'control_number' => $tenant->control_number,
            ];

            // Get message template (shared templates for all companies)
            $template = \App\Models\MessageTemplate::whereNull('company_id')
                ->where('type', 'tenant_welcome')
                ->where('is_active', true)
                ->first();

            // If no shared template, try company-specific template
            if (!$template) {
                $template = \App\Models\MessageTemplate::where('company_id', $lease->company_id)
                    ->where('type', 'tenant_welcome')
                    ->where('is_active', true)
                    ->first();
            }

            // Only send messages if template exists and is active
            if (!$template) {
                \Log::warning('No active tenant_welcome template found for lease creation', [
                    'company_id' => $lease->company_id,
                ]);
                return;
            }

            // Send SMS to tenant
            if ($tenant->phone) {
                $smsMessage = $template->renderSmsTemplate($data);
                \Log::info('Sending lease creation SMS to tenant', ['phone' => $tenant->phone, 'message' => $smsMessage]);
                $this->smsService->send($tenant->phone, $smsMessage);
            } else {
                \Log::warning('Tenant has no phone number', ['tenant_id' => $tenant->id]);
            }

            // Send Email to tenant
            if ($tenant->email) {
                $emailSubject = $template->renderEmailSubject($data);
                $emailBody = $template->renderEmailTemplate($data);
                
                \Log::info('Sending lease creation email to tenant', ['email' => $tenant->email]);
                Mail::raw($emailBody, function ($message) use ($tenant, $emailSubject) {
                    $message->to($tenant->email)
                        ->subject($emailSubject);
                });
            } else {
                \Log::warning('Tenant has no email', ['tenant_id' => $tenant->id]);
            }

            // Send message to landlord
            if ($landlord) {
                $this->sendLeaseCreationToLandlord($lease, $tenant, $unit, $property, $landlord);
            }
        } catch (\Exception $e) {
            \Log::error('Error sending lease creation message', [
                'lease_id' => $lease->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send lease creation message to landlord
     */
    protected function sendLeaseCreationToLandlord(Lease $lease, $tenant, $unit, $property, $landlord): void
    {
        try {
            $data = [
                'landlord_name' => $landlord->name,
                'property_name' => $property->name,
                'unit_number' => $unit->unit_number,
                'tenant_name' => $tenant->name,
                'tenant_phone' => $tenant->phone,
                'start_date' => $lease->start_date,
                'end_date' => $lease->end_date,
                'rent_amount' => number_format($lease->monthly_rent, 0),
            ];

            // Get message template (shared templates for all companies)
            $template = \App\Models\MessageTemplate::whereNull('company_id')
                ->where('type', 'lease_landlord_notification')
                ->where('is_active', true)
                ->first();

            // If no shared template, try company-specific template
            if (!$template) {
                $template = \App\Models\MessageTemplate::where('company_id', $lease->company_id)
                    ->where('type', 'lease_landlord_notification')
                    ->where('is_active', true)
                    ->first();
            }

            // Only send messages if template exists and is active
            if (!$template) {
                \Log::warning('No active lease_landlord_notification template found for lease creation', [
                    'company_id' => $lease->company_id,
                ]);
                return;
            }

            // Send SMS to landlord
            if ($landlord->phone) {
                $smsMessage = $template->renderSmsTemplate($data);
                \Log::info('Sending lease creation SMS to landlord', ['phone' => $landlord->phone, 'message' => $smsMessage]);
                $this->smsService->send($landlord->phone, $smsMessage);
            } else {
                \Log::warning('Landlord has no phone number', ['landlord_id' => $landlord->id]);
            }

            // Send Email to landlord
            if ($landlord->email) {
                $emailSubject = $template->renderEmailSubject($data);
                $emailBody = $template->renderEmailTemplate($data);
                
                \Log::info('Sending lease creation email to landlord', ['email' => $landlord->email]);
                Mail::raw($emailBody, function ($message) use ($landlord, $emailSubject) {
                    $message->to($landlord->email)
                        ->subject($emailSubject);
                });
            } else {
                \Log::warning('Landlord has no email', ['landlord_id' => $landlord->id]);
            }
        } catch (\Exception $e) {
            \Log::error('Error sending lease creation message to landlord', [
                'lease_id' => $lease->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
