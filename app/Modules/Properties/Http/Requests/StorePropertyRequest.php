<?php

namespace App\Modules\Properties\Http\Requests;

use App\Modules\Properties\Models\Property;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Property::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in([
                'residential', 'commercial', 'mixed_use', 'warehouse',
                'retail', 'hotel', 'hostel', 'industrial', 'land', 'other',
            ])],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'under_maintenance', 'archived'])],
            // Scoped to the acting user's own company - without this,
            // a landlord_id belonging to another company would pass a
            // bare exists() check and only get caught later (if at all),
            // since the FK itself doesn't know about tenancy.
            'landlord_id' => ['nullable', Rule::exists('landlords', 'id')->where('company_id', $this->user()->company_id)],
            'description' => ['nullable', 'string'],
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
