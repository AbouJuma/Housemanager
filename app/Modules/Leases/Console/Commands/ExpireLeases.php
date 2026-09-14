<?php

namespace App\Modules\Leases\Console\Commands;

use App\Modules\Leases\Models\Lease;
use App\Modules\Leases\Services\LeaseService;
use Illuminate\Console\Command;

/**
 * Scheduled daily (see LeasesServiceProvider::boot()). A lease that
 * reaches its end_date without being renewed or terminated first just
 * runs out - this flips it to "expired" and frees the unit back to
 * vacant, the same status-sync LeaseService applies for any other
 * status change, so a lease ending via the clock behaves identically
 * to one ended through the UI.
 */
class ExpireLeases extends Command
{
    protected $signature = 'leases:expire';

    protected $description = 'Expire active leases whose end date has passed and free their units';

    public function handle(LeaseService $service): int
    {
        $leases = Lease::query()
            ->where('status', 'active')
            ->whereDate('end_date', '<', now()->toDateString())
            ->get();

        foreach ($leases as $lease) {
            $service->update($lease, ['status' => 'expired']);
        }

        $this->info("Expired {$leases->count()} lease(s).");

        return self::SUCCESS;
    }
}
