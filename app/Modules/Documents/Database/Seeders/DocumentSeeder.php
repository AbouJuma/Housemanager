<?php

namespace App\Modules\Documents\Database\Seeders;

use App\Modules\Companies\Models\Company;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Services\DocumentService;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Leases\Models\Lease;
use App\Modules\Properties\Models\Property;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\Vendors\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

/**
 * Goes through DocumentService::create() with a real fake UploadedFile
 * for every seeded document (never a raw Document::create()) - the same
 * "seeders shouldn't bypass real business logic" discipline as
 * ExpenseSeeder/MaintenanceRequestSeeder/InventoryItemSeeder, needed
 * here specifically because the file itself has to be written through
 * Spatie Media Library to exist as real, downloadable seeded data.
 */
class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(DocumentService::class);

        Company::query()->each(function (Company $company) use ($service): void {
            $pools = [
                'property' => Property::query()->where('company_id', $company->id)->get(),
                'landlord' => Landlord::query()->where('company_id', $company->id)->get(),
                'tenant' => Tenant::query()->where('company_id', $company->id)->get(),
                'lease' => Lease::query()->where('company_id', $company->id)->get(),
                'vendor' => Vendor::query()->where('company_id', $company->id)->get(),
            ];
            $typeMap = Document::documentableTypeMap();

            for ($i = 0; $i < random_int(6, 12); $i++) {
                $attachToType = fake()->boolean(80) ? fake()->randomElement(array_keys($pools)) : null;
                $pool = $attachToType ? $pools[$attachToType] : null;
                $target = $pool && $pool->isNotEmpty() ? $pool->random() : null;

                $service->create([
                    'company_id' => $company->id,
                    'documentable_type' => $target ? $typeMap[$attachToType] : null,
                    'documentable_id' => $target?->id,
                    'title' => fake()->randomElement([
                        'Signed Lease Agreement', 'Property Deed', 'Insurance Certificate', 'Business License',
                        'Tenant ID Copy', 'Vendor Contract', 'Fire Safety Certificate', 'Utility Bill',
                    ]),
                    'category' => fake()->randomElement([
                        'contract', 'lease_agreement', 'id_document', 'certificate',
                        'insurance', 'deed', 'invoice', 'license', 'other',
                    ]),
                    'expiry_date' => fake()->boolean(30) ? now()->addMonths(random_int(1, 18))->toDateString() : null,
                    'file' => UploadedFile::fake()->create('document-'.$i.'.pdf', 100, 'application/pdf'),
                ]);
            }
        });
    }
}
