<?php

namespace App\Modules\LandlordPortal\Support;

use App\Core\Support\Period;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Landlords\Models\Landlord;
use Illuminate\Database\Eloquent\Builder;

/**
 * The landlord's invoices list (portal and app): pending by default -
 * sent, partly paid or overdue - filterable by status and due date.
 */
class LandlordInvoices
{
    public const PENDING = ['sent', 'partially_paid', 'overdue'];

    public const FILTERS = ['pending', 'overdue', 'partially_paid', 'paid', 'all'];

    /** @return array{0: Builder, 1: string} the filtered query and the status filter used */
    public static function query(Landlord $landlord, ?string $status, ?string $period, ?string $from, ?string $to): array
    {
        $status = in_array($status, self::FILTERS, true) ? $status : 'pending';

        $query = $landlord->invoicesQuery()
            ->with(['lease.tenant:id,uuid,name,phone', 'lease.unit:id,uuid,unit_number,building_id', 'lease.unit.building:id,property_id', 'lease.unit.building.property:id,uuid,name',
                'sale.buyer:id,uuid,name,phone', 'sale.unit:id,uuid,unit_number,building_id', 'sale.unit.building:id,property_id', 'sale.unit.building.property:id,uuid,name'])
            ->when($status === 'pending', fn ($q) => $q->whereIn('status', self::PENDING))
            ->when(! in_array($status, ['pending', 'all'], true), fn ($q) => $q->where('status', $status))
            ->where('status', '!=', 'cancelled');

        if ($period || $from || $to) {
            [$start, $end] = Period::resolve($period, $from, $to);
            $query->whereDate('due_date', '>=', $start->toDateString())->whereDate('due_date', '<=', $end->toDateString());
        }

        return [$query, $status];
    }

    public static function row(Invoice $invoice): array
    {
        $unit = $invoice->lease?->unit ?? $invoice->sale?->unit;
        $who = $invoice->lease?->tenant ?? $invoice->sale?->buyer;

        return [
            'uuid' => $invoice->uuid,
            'invoice_number' => $invoice->invoice_number,
            'kind' => $invoice->lease_id ? 'rent' : 'sale',
            'status' => $invoice->status,
            'issue_date' => $invoice->issue_date?->toDateString(),
            'due_date' => $invoice->due_date?->toDateString(),
            'total_amount' => (float) $invoice->total_amount,
            'amount_paid' => (float) $invoice->amount_paid,
            'balance_due' => $invoice->balanceDue(),
            'is_overdue' => $invoice->status === 'overdue' || ($invoice->balanceDue() > 0 && $invoice->due_date?->isPast()),
            'person' => $who ? ['uuid' => $who->uuid, 'name' => $who->name, 'phone' => $who->phone] : null,
            'unit_number' => $unit?->unit_number,
            'property' => $unit?->building?->property ? ['uuid' => $unit->building->property->uuid, 'name' => $unit->building->property->name] : null,
        ];
    }

    /** @return array{count: int, total: float, balance: float} */
    public static function summary(Builder $query): array
    {
        $invoices = (clone $query)->get(['id', 'total_amount', 'amount_paid']);

        return [
            'count' => $invoices->count(),
            'total' => (float) $invoices->sum('total_amount'),
            'balance' => (float) $invoices->sum(fn (Invoice $invoice) => $invoice->balanceDue()),
        ];
    }
}
