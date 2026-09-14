<?php

namespace App\Modules\RentCollection\Console\Commands;

use App\Modules\RentCollection\Models\InvoicePaymentAttempt;
use App\Modules\RentCollection\Services\RentCollectionService;
use Illuminate\Console\Command;

/**
 * Scheduled every 15 minutes (see RentCollectionServiceProvider::boot())
 * - a robustness net alongside the inbound payment webhook, not a
 * replacement for it: this app has no queue worker to react to a
 * webhook instantly (ARCHITECTURE.md §6), and not every gateway
 * deployment will have a reachable public webhook URL configured, so
 * polling every pending attempt this often is the honest way to
 * guarantee a charge that succeeded gets recorded within a bounded
 * time even if the webhook never arrives.
 */
class VerifyPendingRentPayments extends Command
{
    protected $signature = 'rent:verify-pending-payments';

    protected $description = 'Re-check pending online rent payment attempts against their gateway, and expire stale ones';

    public function handle(RentCollectionService $service): int
    {
        $pending = InvoicePaymentAttempt::query()->where('status', 'pending')->get();

        foreach ($pending as $attempt) {
            $service->verifyAttempt($attempt);
        }

        $expired = $service->expireStaleAttempts();

        $this->info('Checked '.$pending->count()." pending attempt(s), expired {$expired} stale one(s).");

        return self::SUCCESS;
    }
}
