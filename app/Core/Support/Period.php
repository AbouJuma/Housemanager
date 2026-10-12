<?php

namespace App\Core\Support;

use Illuminate\Support\Carbon;

/**
 * Report/list date filters: a named period (today, this week, this
 * month...) or a custom from/to, in the company's timezone - plus how to
 * break a range down (by day, week or month).
 */
class Period
{
    public const NAMES = ['today', 'yesterday', 'this_week', 'last_week', 'this_month', 'last_month', 'this_year', 'last_year'];

    public const GROUPS = ['day', 'week', 'month'];

    /**
     * @return array{0: Carbon, 1: Carbon, 2: ?string} from, to, the named period used (null = custom)
     */
    public static function resolve(?string $period, ?string $from = null, ?string $to = null, string $default = 'this_year'): array
    {
        $now = company_now();

        if (! $period && ($from || $to)) {
            $end = $to ? Carbon::parse($to, $now->getTimezone())->endOfDay() : $now->copy()->endOfDay();
            $start = $from ? Carbon::parse($from, $now->getTimezone())->startOfDay() : $end->copy()->startOfYear();

            return $start->greaterThan($end) ? [$end->copy()->startOfDay(), $start->copy()->endOfDay(), null] : [$start, $end, null];
        }

        $period = in_array($period, self::NAMES, true) ? $period : $default;

        [$start, $end] = match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfDay()],
            'last_week' => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'last_year' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            default => [$now->copy()->startOfYear(), $now->copy()->endOfDay()],
        };

        return [$start, $end, $period];
    }

    /** Day for up to a month, week for up to ~4 months, month beyond - unless asked. */
    public static function group(?string $requested, Carbon $from, Carbon $to): string
    {
        if (in_array($requested, self::GROUPS, true)) {
            return $requested;
        }
        $days = $from->diffInDays($to);

        return $days <= 31 ? 'day' : ($days <= 120 ? 'week' : 'month');
    }

    /** The bucket a date falls in, e.g. 2026-10-12 / 2026-W41 / 2026-10. */
    public static function bucket(Carbon $date, string $group): string
    {
        return match ($group) {
            'day' => $date->format('Y-m-d'),
            'week' => $date->copy()->startOfWeek()->format('Y-m-d'),
            default => $date->format('Y-m'),
        };
    }

    /**
     * Every bucket between two dates, in order.
     *
     * @return list<array{key: string, label: string}>
     */
    public static function buckets(Carbon $from, Carbon $to, string $group): array
    {
        $buckets = [];
        $cursor = match ($group) {
            'day' => $from->copy()->startOfDay(),
            'week' => $from->copy()->startOfWeek(),
            default => $from->copy()->startOfMonth(),
        };

        while ($cursor->lessThanOrEqualTo($to) && count($buckets) < 400) {
            $buckets[] = [
                'key' => self::bucket($cursor, $group),
                'label' => match ($group) {
                    'day' => $cursor->translatedFormat('D d M'),
                    'week' => __('Week of :date', ['date' => $cursor->translatedFormat('d M')]),
                    default => $cursor->translatedFormat('M Y'),
                },
            ];
            $cursor = match ($group) {
                'day' => $cursor->addDay(),
                'week' => $cursor->addWeek(),
                default => $cursor->addMonthNoOverflow(),
            };
        }

        return $buckets;
    }
}
