<?php

namespace App\Modules\RentCollection\Console\Commands;

use App\Modules\Leases\Models\Lease;
use App\Modules\RentCollection\Services\RentInvoiceGenerator;
use Illuminate\Console\Command;

/**
 * Scheduled daily (see RentCollectionServiceProvider::boot()), before
 * SendRentReminders so a freshly generated invoice is eligible for the
 * same day's due-soon check. Fills a real gap: before this command,
 * NO invoice was ever created automatically anywhere in the app - every
 * rent invoice was hand-created by staff.
 *
 * A brand-new lease's very first invoice is generated immediately by
 * App\Modules\Leases\Services\LeaseService::create() instead of waiting
 * for this daily sweep (see RentInvoiceGenerator::generateNextInvoiceForLease()'s
 * $ignoreLeadTime param) - this command's job is every invoice AFTER
 * that first one, generated a few days ahead of each billing cycle.
 */
class GenerateRentInvoices extends Command
{
    protected $signature = 'rent:generate-invoices';

    protected $description = 'Generate the next rent invoice for each active lease ahead of its billing cycle';

    public function __construct(protected RentInvoiceGenerator $generator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $generated = 0;

        Lease::query()
            ->where('status', 'active')
            ->with('unit.building.property', 'invoices')
            ->chunkById(100, function ($leases) use (&$generated): void {
                foreach ($leases as $lease) {
                    if ($this->generator->generateNextInvoiceForLease($lease)) {
                        $generated++;
                    }
                }
            });

        $this->info("Generated {$generated} rent invoice(s).");

        return self::SUCCESS;
    }
}
