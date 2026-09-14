<?php

namespace App\Modules\Inventory\Database\Factories;

use App\Modules\Inventory\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'LED Light Bulb', 'PVC Pipe Fitting', 'Padlock', 'Paint (White, 1L)', 'Door Hinge',
                'Extension Cord', 'Smoke Detector', 'Air Filter', 'Cleaning Detergent', 'Work Gloves',
            ]),
            'sku' => fake()->boolean(60) ? strtoupper(fake()->bothify('SKU-####??')) : null,
            'category' => fake()->randomElement([
                'appliance', 'furniture', 'tool', 'plumbing_supply', 'electrical_supply',
                'cleaning_supply', 'hardware', 'safety_equipment', 'other',
            ]),
            'unit_of_measure' => fake()->randomElement(['piece', 'box', 'liter', 'kilogram', 'meter', 'roll', 'pack']),
            'reorder_level' => fake()->boolean(70) ? fake()->randomFloat(2, 2, 20) : null,
            'unit_cost' => fake()->boolean(80) ? fake()->randomFloat(2, 1, 150) : null,
            'status' => 'active',
        ];
    }

    public function discontinued(): static
    {
        return $this->state(fn () => ['status' => 'discontinued']);
    }
}
