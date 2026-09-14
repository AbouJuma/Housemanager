<?php

namespace App\Modules\Inventory\Http\Requests;

use App\Modules\Inventory\Models\InventoryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', InventoryItem::class);
    }

    public function rules(): array
    {
        $categories = ['appliance', 'furniture', 'tool', 'plumbing_supply', 'electrical_supply', 'cleaning_supply', 'hardware', 'safety_equipment', 'other'];
        $units = ['piece', 'box', 'liter', 'kilogram', 'meter', 'roll', 'pack', 'other'];

        return [
            'property_id' => ['nullable', Rule::exists('properties', 'id')->where('company_id', $this->user()->company_id)],
            'vendor_id' => ['nullable', Rule::exists('vendors', 'id')->where('company_id', $this->user()->company_id)],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'category' => ['required', Rule::in($categories)],
            'unit_of_measure' => ['required', Rule::in($units)],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
