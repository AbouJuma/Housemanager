<?php

namespace App\Modules\LandlordPortal\Services;

use App\Core\Support\Period;
use App\Modules\Expenses\Models\Expense;
use App\Modules\Invoices\Models\InvoicePayment;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Properties\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A landlord's own numbers - profit & loss and unit occupancy - for the
 * landlord portal and the app, limited to the landlord's properties.
 *
 * Income is money actually received (invoice payments, by the date they
 * were paid): rent from leases and instalments from unit sales. Expenses
 * are the non-voided expenses booked against the landlord's properties,
 * by expense date.
 */
class LandlordReportService
{
    public const UNIT_STATUSES = ['occupied', 'vacant', 'reserved', 'under_maintenance'];

    /** @param  ?string  $group  day / week / month (null = picked from the range length) */
    public function profitAndLoss(Landlord $landlord, Carbon $from, Carbon $to, ?string $group = null): array
    {
        $group = Period::group($group, $from, $to);
        $properties = $landlord->properties()->get(['id', 'uuid', 'name'])->keyBy('id');
        $propertyIds = $properties->keys()->all();

        $payments = $this->payments($propertyIds, $from, $to);
        $expenses = Expense::query()
            ->with('account:id,name')
            ->whereIn('property_id', $propertyIds ?: [0])
            ->where('status', '!=', 'voided')
            ->whereDate('expense_date', '>=', $from->toDateString())
            ->whereDate('expense_date', '<=', $to->toDateString())
            ->get(['id', 'property_id', 'account_id', 'amount', 'expense_date']);

        $rent = $payments->where('source', 'rent')->sum('amount');
        $sales = $payments->where('source', 'sale')->sum('amount');
        $income = $rent + $sales;
        $spent = (float) $expenses->sum('amount');

        $byProperty = $properties->map(function ($property) use ($payments, $expenses) {
            $in = (float) $payments->where('property_id', $property->id)->sum('amount');
            $out = (float) $expenses->where('property_id', $property->id)->sum('amount');

            return ['uuid' => $property->uuid, 'name' => $property->name, 'income' => $in, 'expenses' => $out, 'net' => $in - $out];
        })->sortByDesc('income')->values()->all();

        $byCategory = $expenses->groupBy(fn ($expense) => $expense->account?->name ?? __('Other'))
            ->map(fn (Collection $rows, string $name) => ['category' => $name, 'amount' => (float) $rows->sum('amount')])
            ->sortByDesc('amount')->values()->all();

        // Day / week / month breakdown of the same figures.
        $breakdown = [];
        foreach (Period::buckets($from, $to, $group) as $bucket) {
            $in = (float) $payments->filter(fn ($p) => Period::bucket($p['date'], $group) === $bucket['key'])->sum('amount');
            $out = (float) $expenses->filter(fn ($e) => Period::bucket(Carbon::parse($e->expense_date), $group) === $bucket['key'])->sum('amount');
            $breakdown[] = [...$bucket, 'income' => $in, 'expenses' => $out, 'net' => $in - $out];
        }

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'income' => ['rent' => (float) $rent, 'sales' => (float) $sales, 'total' => (float) $income],
            'expenses' => ['total' => $spent, 'by_category' => $byCategory],
            'net' => (float) $income - $spent,
            'margin' => $income > 0 ? round((($income - $spent) / $income) * 100, 1) : null,
            'by_property' => $byProperty,
            'group' => $group,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Every unit of the landlord's with who rents it - optionally only
     * one status ('occupied' = rented, 'vacant' = free).
     */
    public function units(Landlord $landlord, ?string $status = null): array
    {
        $units = $landlord->unitsQuery()
            ->with(['building:id,name,property_id', 'building.property:id,uuid,name', 'leases' => fn ($q) => $q->where('status', 'active')->with('tenant:id,uuid,name,phone')])
            ->orderBy('building_id')->orderBy('unit_number')
            ->get();

        $summary = [
            'total' => $units->count(),
            'occupied' => $units->where('status', 'occupied')->count(),
            'vacant' => $units->where('status', 'vacant')->count(),
            'other' => $units->whereNotIn('status', ['occupied', 'vacant'])->count(),
        ];
        $summary['occupancy_rate'] = $summary['total'] > 0 ? round($summary['occupied'] / $summary['total'] * 100, 1) : 0.0;
        $summary['monthly_rent_occupied'] = (float) $units->where('status', 'occupied')->sum('rent_amount');
        $summary['monthly_rent_vacant'] = (float) $units->where('status', 'vacant')->sum('rent_amount');

        $rows = $units
            ->when(in_array($status, self::UNIT_STATUSES, true), fn ($all) => $all->where('status', $status))
            ->map(function (Unit $unit) {
                $lease = $unit->leases->first();

                return [
                    'uuid' => $unit->uuid,
                    'unit_number' => $unit->unit_number,
                    'type' => $unit->type,
                    'status' => $unit->status,
                    'is_rented' => $unit->status === 'occupied',
                    'rent_amount' => (float) $unit->rent_amount,
                    'building' => $unit->building?->name,
                    'property' => $unit->building?->property ? ['uuid' => $unit->building->property->uuid, 'name' => $unit->building->property->name] : null,
                    'tenant' => $lease?->tenant ? ['uuid' => $lease->tenant->uuid, 'name' => $lease->tenant->name, 'phone' => $lease->tenant->phone] : null,
                    'lease_end' => $lease?->end_date?->toDateString(),
                ];
            })->values()->all();

        return ['summary' => $summary, 'units' => $rows];
    }

    /**
     * Payments received on invoices for the landlord's units, tagged rent
     * or sale, with the property they belong to.
     *
     * @param  list<int>  $propertyIds
     */
    protected function payments(array $propertyIds, Carbon $from, Carbon $to): Collection
    {
        if (! $propertyIds) {
            return collect();
        }

        $inProperties = fn (Builder $q) => $q->whereIn('property_id', $propertyIds);

        return InvoicePayment::query()
            ->with(['invoice:id,lease_id,sale_id', 'invoice.lease:id,unit_id', 'invoice.lease.unit:id,building_id', 'invoice.lease.unit.building:id,property_id',
                'invoice.sale:id,unit_id', 'invoice.sale.unit:id,building_id', 'invoice.sale.unit.building:id,property_id'])
            ->whereDate('paid_on', '>=', $from->toDateString())
            ->whereDate('paid_on', '<=', $to->toDateString())
            ->whereHas('invoice', fn (Builder $invoice) => $invoice
                ->whereHas('lease.unit.building', $inProperties)
                ->orWhereHas('sale.unit.building', $inProperties))
            ->get(['id', 'invoice_id', 'amount', 'paid_on'])
            ->map(function (InvoicePayment $payment) {
                $invoice = $payment->invoice;
                $isRent = $invoice?->lease_id !== null;
                $building = $isRent ? $invoice->lease?->unit?->building : $invoice?->sale?->unit?->building;

                return [
                    'amount' => (float) $payment->amount,
                    'source' => $isRent ? 'rent' : 'sale',
                    'property_id' => $building?->property_id,
                    'date' => Carbon::parse($payment->paid_on),
                ];
            });
    }
}
