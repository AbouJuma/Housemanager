<?php

namespace App\Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Companies\Models\Company;
use App\Modules\Notifications\Models\MessageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Read-only filterable report over MessageLog - see
 * App\Modules\Notifications\Channels\CompanySmsChannel/CompanyMailChannel
 * for where rows get written. No Policy - a single `message-logs.view`
 * permission checked directly (same pattern as
 * App\Modules\Reports\Http\Controllers\ReportController), granted to
 * the SaaS owner via a Gate::before hook in App\Providers\
 * AppServiceProvider since they have no company-scoped role/permission
 * assignment of their own.
 */
class MessageLogController extends Controller
{
    /**
     * Notifiable classes a viewer can filter by, with a human label for
     * each - MessageLog stores the full morph class string (no
     * Relation::morphMap() is registered in this app), so this list is
     * maintained here rather than derived.
     */
    protected const NOTIFIABLE_TYPES = [
        \App\Modules\Tenants\Models\Tenant::class => 'Tenant',
        \App\Modules\Landlords\Models\Landlord::class => 'Landlord',
        \App\Modules\Sales\Models\Buyer::class => 'Buyer',
        \App\Models\User::class => 'Staff user',
    ];

    public function index(Request $request): View
    {
        $this->authorize('message-logs.view');

        $user = $request->user();
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $query = MessageLog::query()->between($startDate, $endDate->copy()->endOfDay());

        if ($user->isSaasOwner()) {
            if ($request->filled('company_id')) {
                $query->forCompany((int) $request->integer('company_id'));
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $query
            ->when($request->filled('notifiable_type'), fn ($q) => $q->notifiableType($request->string('notifiable_type')->toString()))
            ->when($request->filled('channel'), fn ($q) => $q->channel($request->string('channel')->toString()))
            ->when($request->filled('status'), fn ($q) => $q->status($request->string('status')->toString()));

        return view('notifications::message-logs.index', [
            'logs' => $query->latest()->paginate(25)->withQueryString(),
            'notifiableTypes' => self::NOTIFIABLE_TYPES,
            'companies' => $user->isSaasOwner() ? Company::query()->orderBy('name')->get(['id', 'name']) : null,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    protected function resolveDateRange(Request $request): array
    {
        if ($request->string('date_preset')->toString() === 'this_year') {
            return [now()->startOfYear(), now()->endOfYear()];
        }

        if ($request->filled('start_date') || $request->filled('end_date')) {
            $start = $request->filled('start_date') ? Carbon::parse($request->string('start_date')) : now()->startOfMonth();
            $end = $request->filled('end_date') ? Carbon::parse($request->string('end_date')) : now()->endOfMonth();

            return [$start, $end];
        }

        return [now()->startOfMonth(), now()->endOfMonth()];
    }
}
