<?php

namespace App\Modules\LandlordPortal\Http\Requests;

use App\Modules\LandlordPortal\Support\LandlordAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A landlord adding a unit to one of their buildings, or editing one of
 * their units. They can't mark a unit "occupied" - a lease does that.
 */
class LandlordUnitRequest extends FormRequest
{
    public const TYPES = ['apartment', 'studio', 'office', 'shop', 'warehouse_space', 'room', 'other'];

    public const STATUSES = ['vacant', 'under_maintenance', 'reserved'];

    public function authorize(): bool
    {
        $unit = $this->route('unit');
        $building = $unit?->building ?? $this->route('building');

        return same_id($building?->property?->landlord_id, $this->user()?->id)
            && LandlordAccess::can($this->user(), $unit ? 'units.update' : 'units.create');
    }

    public function rules(): array
    {
        $unit = $this->route('unit');
        $buildingId = $unit?->building_id ?? $this->route('building')?->id;

        return [
            'unit_number' => ['required', 'string', 'max:50',
                Rule::unique('units', 'unit_number')->where('building_id', $buildingId)->ignore($unit?->id)],
            'type' => ['required', Rule::in(self::TYPES)],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:50'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:50'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'area_unit' => ['nullable', 'string', 'max:10'],
            'rent_amount' => ['nullable', 'numeric', 'min:0'],
            'rent_period' => ['nullable', Rule::in(array_keys(\App\Modules\Leases\Support\RentPeriods::MONTHS))],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return ['unit_number.unique' => __('This building already has a unit with that number.')];
    }
}
