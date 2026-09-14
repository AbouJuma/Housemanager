<?php

namespace App\Modules\Documents\Database\Factories;

use App\Modules\Documents\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 *
 * Produces the row only - no file attached (fileUrl() simply returns
 * null, which every view/resource already handles gracefully). Seeded,
 * realistic documents with a real attached file go through
 * DocumentService::create() with a real UploadedFile instead (see
 * DocumentSeeder), the same "seeders shouldn't bypass real business
 * logic" discipline as ExpenseSeeder/MaintenanceRequestSeeder.
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'title' => fake()->randomElement([
                'Signed Lease Agreement', 'Property Deed', 'Insurance Certificate', 'Business License',
                'Tenant ID Copy', 'Vendor Contract', 'Fire Safety Certificate', 'Utility Bill',
            ]),
            'category' => fake()->randomElement([
                'contract', 'lease_agreement', 'id_document', 'certificate',
                'insurance', 'deed', 'invoice', 'license', 'other',
            ]),
            'description' => fake()->boolean(50) ? fake()->sentence() : null,
            'expiry_date' => fake()->boolean(30) ? now()->addMonths(random_int(1, 18))->toDateString() : null,
        ];
    }
}
