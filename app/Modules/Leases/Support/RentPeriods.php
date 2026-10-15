<?php

namespace App\Modules\Leases\Support;

use Illuminate\Support\Carbon;

/**
 * Rent periods (monthly, quarterly, semi-annual, annual): a unit's price
 * is quoted per one of them, a lease is billed per one of them, and the
 * two are converted through the monthly equivalent - e.g. a unit at
 * 500,000 a month on a quarterly lease is billed 1,500,000 a quarter.
 */
class RentPeriods
{
    /** Months in each period. */
    public const MONTHS = ['monthly' => 1, 'quarterly' => 3, 'semi_annual' => 6, 'annual' => 12];

    public static function months(?string $period): int
    {
        return self::MONTHS[$period] ?? 1;
    }

    /** An amount quoted per $from, expressed per $to. */
    public static function convert(float $amount, ?string $from, ?string $to): float
    {
        return round($amount / self::months($from) * self::months($to), 2);
    }

    /** The last day of $periods billing periods starting on $start. */
    public static function endDate(Carbon $start, ?string $cycle, int $periods = 1): Carbon
    {
        return $start->copy()->addMonthsNoOverflow(self::months($cycle) * max(1, $periods))->subDay();
    }

    /** How many billing periods a lease from $start to $end covers (part periods count as whole). */
    public static function periodsBetween(Carbon $start, Carbon $end, ?string $cycle): int
    {
        $months = $start->copy()->startOfDay()->diffInMonths($end->copy()->addDay()->startOfDay());

        return max(1, (int) ceil(round($months, 4) / self::months($cycle)));
    }
}
