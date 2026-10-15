<?php

namespace App\Modules\Leases\Http\Requests\Concerns;

use App\Modules\Leases\Support\RentPeriods;
use App\Modules\Properties\Models\Unit;
use Illuminate\Support\Carbon;

/**
 * New leases (staff and landlords, web and app): when left blank,
 * - rent_amount = the unit's rent converted to the lease's billing cycle
 *   (a 500,000/month unit billed quarterly = 1,500,000), and
 * - end_date = start_date + `periods` billing periods (default 1).
 * Anything typed in is kept as it is.
 */
trait FillsLeaseTerms
{
    protected function fillFromUnitAndPeriods(): void
    {
        $cycle = $this->input('billing_cycle') ?: 'monthly';

        if (blank($this->input('rent_amount')) && $this->input('unit_id')) {
            $unit = Unit::query()->find($this->input('unit_id'));
            if ($unit && $unit->rent_amount !== null) {
                $this->merge(['rent_amount' => RentPeriods::convert((float) $unit->rent_amount, $unit->rent_period ?? 'monthly', $cycle)]);
            }
        }

        if (blank($this->input('end_date')) && filled($this->input('start_date'))) {
            try {
                $start = Carbon::parse($this->input('start_date'));
            } catch (\Throwable) {
                return; // the date rule reports it
            }
            $this->merge(['end_date' => RentPeriods::endDate($start, $cycle, (int) ($this->input('periods') ?: 1))->toDateString()]);
        }
    }
}
