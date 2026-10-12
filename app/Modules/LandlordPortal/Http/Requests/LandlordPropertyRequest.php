<?php

namespace App\Modules\LandlordPortal\Http\Requests;

use App\Modules\LandlordPortal\Support\LandlordAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A landlord adding or editing one of their own properties (portal and
 * app). Same fields as the staff form, minus choosing the landlord - it
 * is always the signed-in landlord.
 */
class LandlordPropertyRequest extends FormRequest
{
    public const TYPES = ['residential', 'commercial', 'mixed_use', 'warehouse', 'retail', 'hotel', 'hostel', 'industrial', 'land', 'other'];

    public const STATUSES = ['active', 'inactive', 'under_maintenance', 'archived'];

    public function authorize(): bool
    {
        $property = $this->route('property');

        if ($property) {
            return $property->landlord_id === $this->user()?->id
                && LandlordAccess::can($this->user(), 'properties.update');
        }

        return LandlordAccess::can($this->user(), 'properties.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(self::TYPES)],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'description' => ['nullable', 'string', 'max:5000'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'total_area' => ['nullable', 'numeric', 'min:0'],
            'area_unit' => ['nullable', 'string', 'max:10'],
        ];
    }
}
