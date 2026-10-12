<?php

namespace App\Modules\LandlordPortal\Http\Requests;

use App\Modules\LandlordPortal\Support\LandlordAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** A landlord adding a building to one of their own properties. */
class LandlordBuildingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('property')?->landlord_id === $this->user()?->id
            && LandlordAccess::can($this->user(), 'units.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'floor_count' => ['nullable', 'integer', 'min:0', 'max:300'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'under_maintenance'])],
        ];
    }
}
