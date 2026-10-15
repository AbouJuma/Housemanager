<?php

namespace App\Modules\LandlordPortal\Http\Requests;

use App\Modules\LandlordPortal\Support\LandlordAccess;
use App\Modules\Leases\Http\Requests\StoreLeaseRequest;
use Closure;

/**
 * A landlord renting out one of their own vacant units to one of their
 * tenants - the same rules as staff (incl. rent and end date filled in
 * from the unit and billing cycle), limited to the landlord's own units
 * and tenants.
 */
class LandlordLeaseRequest extends StoreLeaseRequest
{
    public function authorize(): bool
    {
        return LandlordAccess::can($this->user(), 'leases.create');
    }

    public function rules(): array
    {
        $landlord = $this->user();
        $rules = parent::rules();

        $rules['unit_id'][] = function (string $attribute, mixed $value, Closure $fail) use ($landlord): void {
            if (! $landlord->unitsQuery()->whereKey($value)->exists()) {
                $fail(__('Choose one of your own units.'));
            }
        };
        $rules['tenant_id'][] = function (string $attribute, mixed $value, Closure $fail) use ($landlord): void {
            if (! $landlord->tenantsQuery()->whereKey($value)->exists()) {
                $fail(__('Choose one of your tenants (add them first if they are new).'));
            }
        };

        return $rules;
    }
}
