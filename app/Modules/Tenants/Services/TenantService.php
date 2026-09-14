<?php

namespace App\Modules\Tenants\Services;

use App\Core\Services\BaseService;
use App\Mail\TenantWelcomeMail;
use App\Modules\Tenants\Repositories\TenantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

/**
 * @property TenantRepositoryInterface $repository see App\Modules\Companies\Services\CompanyService
 *  for why this can't be redeclared as a typed constructor property.
 */
class TenantService extends BaseService
{
    protected BeemsSmsService $smsService;

    public function __construct(TenantRepositoryInterface $repository, BeemsSmsService $smsService)
    {
        parent::__construct($repository);
        $this->smsService = $smsService;
    }

    public function paginate(?string $search, ?string $type, ?string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->query()
            ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $attributes): Model
    {
        // Generate control number if not provided
        if (empty($attributes['control_number'])) {
            $companyId = $attributes['company_id'] ?? auth()->user()->company_id;
            $attributes['control_number'] = $this->generateControlNumber($companyId);
        }

        $tenant = parent::create($attributes);

        // Send welcome SMS and email
        $this->sendWelcomeMessage($tenant);

        return $tenant;
    }

    protected function generateControlNumber(int $companyId): string
    {
        $maxAttempts = 100;
        $attempts = 0;
        
        do {
            // Generate random 10-digit number starting with 9
            $controlNumber = '9' . str_pad((string) rand(0, 999999999), 9, '0', STR_PAD_LEFT);
            
            // Check if this control number already exists (including soft-deleted)
            $exists = \App\Modules\Tenants\Models\Tenant::withTrashed()
                ->where('control_number', $controlNumber)
                ->exists();
            
            $attempts++;
        } while ($exists && $attempts < $maxAttempts);
        
        return $controlNumber;
    }

    protected function sendWelcomeMessage(\App\Modules\Tenants\Models\Tenant $tenant): void
    {
        try {
            $company = $tenant->company;

            // Get the most recent lease for rent amount and landlord bank details
            $lease = $tenant->leases()->with('unit.building.property.landlord')->latest()->first();
            $rentAmount = $lease ? $lease->monthly_rent : 0;

            // Get bank details from landlord
            $bankName = 'N/A';
            $accountNumber = 'N/A';
            $propertyName = 'N/A';
            $unitNumber = 'N/A';

            if ($lease && $lease->unit && $lease->unit->building && $lease->unit->building->property && $lease->unit->building->property->landlord) {
                $landlord = $lease->unit->building->property->landlord;
                $bankDetails = $landlord->bank_details ?? [];
                $bankName = $bankDetails['bank_name'] ?? 'N/A';
                $accountNumber = $bankDetails['account_number'] ?? 'N/A';
                $propertyName = $lease->unit->building->property->name ?? 'N/A';
                $unitNumber = $lease->unit->unit_number ?? 'N/A';
            }

            $data = [
                'tenant_name' => $tenant->name,
                'company_name' => $company->name,
                'phone' => $tenant->phone,
                'control_number' => $tenant->control_number,
                'bank_name' => $bankName,
                'account_number' => $accountNumber,
                'rent_amount' => number_format($rentAmount, 0),
                'property_name' => $propertyName,
                'unit_number' => $unitNumber,
            ];

            // Get message template (try shared templates first, then company-specific)
            $template = \App\Models\MessageTemplate::whereNull('company_id')
                ->where('type', 'tenant_welcome')
                ->where('is_active', true)
                ->first();

            // If no shared template, try company-specific template
            if (!$template) {
                $template = \App\Models\MessageTemplate::where('company_id', $tenant->company_id)
                    ->where('type', 'tenant_welcome')
                    ->where('is_active', true)
                    ->first();
            }

            // Only send messages if template exists and is active
            if (!$template) {
                \Log::warning('No active tenant_welcome template found', [
                    'company_id' => $tenant->company_id,
                    'shared_template_count' => \App\Models\MessageTemplate::whereNull('company_id')->where('type', 'tenant_welcome')->where('is_active', true)->count(),
                    'company_template_count' => \App\Models\MessageTemplate::where('company_id', $tenant->company_id)->where('type', 'tenant_welcome')->where('is_active', true)->count(),
                ]);
                return;
            }

            // Send SMS
            if ($tenant->phone) {
                $smsMessage = $template->renderSmsTemplate($data);
                \Log::info('Sending SMS to tenant', ['phone' => $tenant->phone, 'message' => $smsMessage]);
                $this->smsService->send($tenant->phone, $smsMessage);
            } else {
                \Log::warning('Tenant has no phone number', ['tenant_id' => $tenant->id]);
            }

            // Send Email
            if ($tenant->email) {
                $emailSubject = $template->renderEmailSubject($data);
                $emailBody = $template->renderEmailTemplate($data);
                
                $mailData = array_merge($data, [
                    'email_subject' => $emailSubject,
                    'email_body' => $emailBody,
                ]);
                
                \Log::info('Sending email to tenant', ['email' => $tenant->email]);
                Mail::to($tenant->email)->send(new TenantWelcomeMail($mailData));
            } else {
                \Log::warning('Tenant has no email', ['tenant_id' => $tenant->id]);
            }
        } catch (\Exception $e) {
            \Log::error('Error sending tenant welcome message', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Build welcome message in Swahili (fallback if no template)
     */
    protected function buildWelcomeMessage(array $data): string
    {
        return sprintf(
            "karibu %s kwenye Apartment %s login kwa www.apartments.ecofieldgroup.com phone:%s utapata otp\nLipia kodi yako kupitia control namba yako ni %s bank %s a/c no.%s kwaajili ya malipo kiasi cha kulipia ni Tsh %s\nplease pay your rent via Control number %s Bank %s",
            $data['tenant_name'],
            $data['company_name'],
            $data['phone'],
            $data['control_number'],
            $data['bank_name'],
            $data['account_number'],
            $data['rent_amount'],
            $data['control_number'],
            $data['bank_name']
        );
    }

    /**
     * Build email body (fallback if no template)
     */
    protected function buildEmailBody(array $data): string
    {
        return "Welcome to " . $data['company_name'];
    }
}
