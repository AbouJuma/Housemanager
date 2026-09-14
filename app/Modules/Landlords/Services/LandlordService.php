<?php

namespace App\Modules\Landlords\Services;

use App\Core\Services\BaseService;
use App\Modules\Landlords\Repositories\LandlordRepositoryInterface;
use App\Modules\Tenants\Services\BeemsSmsService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

/**
 * @property LandlordRepositoryInterface $repository see App\Modules\Companies\Services\CompanyService
 *  for why this can't be redeclared as a typed constructor property.
 */
class LandlordService extends BaseService
{
    protected const BANK_DETAIL_FIELDS = ['bank_name', 'bank_account_name', 'bank_account_number', 'bank_branch', 'bank_swift_code'];

    protected BeemsSmsService $smsService;

    public function __construct(LandlordRepositoryInterface $repository, BeemsSmsService $smsService)
    {
        parent::__construct($repository);
        $this->smsService = $smsService;
    }

    public function paginate(?string $search, ?string $type, ?string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->query()
            ->withCount('properties')
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
        $landlord = parent::create($this->withAssembledBankDetails($attributes));
        
        // Send welcome message
        $this->sendWelcomeMessage($landlord);
        
        return $landlord;
    }

    public function update(Model $model, array $attributes): Model
    {
        return parent::update($model, $this->withAssembledBankDetails($attributes));
    }

    /**
     * Send welcome message to landlord
     */
    protected function sendWelcomeMessage(Model $landlord): void
    {
        try {
            $company = $landlord->company;
            
            // Get property names
            $propertyNames = $landlord->properties->pluck('name')->implode(', ') ?: 'No properties yet';

            $data = [
                'landlord_name' => $landlord->name,
                'company_name' => $company->name,
                'property_names' => $propertyNames,
            ];

            // Get message template (shared templates for all companies)
            $template = \App\Models\MessageTemplate::whereNull('company_id')
                ->where('type', 'landlord_welcome')
                ->where('is_active', true)
                ->first();

            // If no shared template, try company-specific template
            if (!$template) {
                $template = \App\Models\MessageTemplate::where('company_id', $landlord->company_id)
                    ->where('type', 'landlord_welcome')
                    ->where('is_active', true)
                    ->first();
            }

            // Only send messages if template exists and is active
            if (!$template) {
                \Log::warning('No active landlord_welcome template found', [
                    'company_id' => $landlord->company_id,
                ]);
                return;
            }

            // Send SMS
            if ($landlord->phone) {
                $smsMessage = $template->renderSmsTemplate($data);
                \Log::info('Sending SMS to landlord', ['phone' => $landlord->phone, 'message' => $smsMessage]);
                $this->smsService->send($landlord->phone, $smsMessage);
            } else {
                \Log::warning('Landlord has no phone number', ['landlord_id' => $landlord->id]);
            }

            // Send Email
            if ($landlord->email) {
                $emailSubject = $template->renderEmailSubject($data);
                $emailBody = $template->renderEmailTemplate($data);
                
                \Log::info('Sending email to landlord', ['email' => $landlord->email]);
                Mail::raw($emailBody, function ($message) use ($landlord, $emailSubject) {
                    $message->to($landlord->email)
                        ->subject($emailSubject);
                });
            } else {
                \Log::warning('Landlord has no email', ['landlord_id' => $landlord->id]);
            }
        } catch (\Exception $e) {
            \Log::error('Error sending landlord welcome message', [
                'landlord_id' => $landlord->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Assembles the form/API's individual bank_* fields into the single
     * `bank_details` array the model's encrypted:array cast expects,
     * mirroring how Module 7's Integration credential fields get merged
     * before saving. Lives here, not in the controllers, since both the
     * web and API controllers need identical behavior - this is
     * business logic, not an HTTP concern.
     */
    protected function withAssembledBankDetails(array $attributes): array
    {
        $bankDetails = array_filter(
            array_combine(
                array_map(fn ($key) => str_replace('bank_', '', $key), self::BANK_DETAIL_FIELDS),
                array_map(fn ($key) => $attributes[$key] ?? null, self::BANK_DETAIL_FIELDS),
            ),
            fn ($value) => $value !== null && $value !== '',
        );

        $attributes['bank_details'] = $bankDetails !== [] ? $bankDetails : null;

        return array_diff_key($attributes, array_flip(self::BANK_DETAIL_FIELDS));
    }
}
