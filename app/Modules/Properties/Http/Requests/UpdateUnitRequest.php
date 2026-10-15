<?php

namespace App\Modules\Properties\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('unit'));
    }

    public function rules(): array
    {
        return [
            'unit_number' => [
                'required', 'string', 'max:50',
                Rule::unique('units', 'unit_number')
                    ->where('building_id', $this->route('unit')->building_id)
                    ->ignore($this->route('unit')->id),
            ],
            'type' => ['required', Rule::in(['apartment', 'studio', 'office', 'shop', 'warehouse_space', 'room', 'other'])],
            'status' => ['required', Rule::in(['vacant', 'occupied', 'under_maintenance', 'reserved'])],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:50'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:50'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'area_unit' => ['nullable', 'string', 'max:10'],
            'rent_amount' => ['nullable', 'numeric', 'min:0'],
            'rent_period' => ['nullable', Rule::in(array_keys(\App\Modules\Leases\Support\RentPeriods::MONTHS))],
            'description' => ['nullable', 'string'],
        ];
    }
}
