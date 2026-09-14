<?php

namespace App\Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\AuditLogs\Models\ActivityLogEntry;
use App\Modules\Companies\Models\Company;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Subscriptions\Services\SubscriptionOverviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected SubscriptionOverviewService $subscriptionOverview)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        return view(
            'dashboard::index',
            $user->isSaasOwner() ? $this->saasOwnerData() : $this->companyData($user->company_id)
        );
    }

    protected function saasOwnerData(): array
    {
        $companies = Company::query();
        $subscriptions = $this->subscriptionOverview->summarize();

        return [
            'isSaasOwner' => true,
            'totalCompanies' => (clone $companies)->count(),
            'activeCompanies' => (clone $companies)->where('status', 'active')->count(),
            'trialCompanies' => (clone $companies)->where('status', 'trial')->count(),
            'totalUsers' => User::query()->count(),
            'companiesByStatus' => (clone $companies)
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'signupsByDay' => $this->companySignupsByDay(),
            'mrr' => $subscriptions['mrr'],
            'activeSubscriptionsCount' => $subscriptions['activeSubscriptionsCount'],
            'expiringSoonCount' => $subscriptions['expiringSoonCount'],
            'unpaidInvoicesCount' => $subscriptions['unpaidInvoicesCount'],
            'unpaidInvoicesTotal' => $subscriptions['unpaidInvoicesTotal'],
            'recentActivity' => ActivityLogEntry::with(['causer', 'subject', 'company'])
                ->latest()
                ->limit(8)
                ->get(),
        ];
    }

    protected function companyData(int $companyId): array
    {
        $company = Company::findOrFail($companyId);

        $totalUnits = Unit::query()->where('company_id', $companyId)->count();
        $occupiedUnits = Unit::query()->where('company_id', $companyId)->where('status', 'occupied')->count();

        return [
            'isSaasOwner' => false,
            'company' => $company,
            'totalUsers' => User::query()->where('company_id', $companyId)->count(),
            'totalProperties' => Property::query()->where('company_id', $companyId)->count(),
            'totalUnits' => $totalUnits,
            'occupiedUnits' => $occupiedUnits,
            'occupancyRate' => $totalUnits > 0 ? round($occupiedUnits / $totalUnits * 100) : null,
            'usersByRole' => $this->usersByRole($companyId),
            'recentActivity' => ActivityLogEntry::where('company_id', $companyId)
                ->with(['causer', 'subject'])
                ->latest()
                ->limit(8)
                ->get(),
        ];
    }

    /**
     * @return array<string, int> ISO date => new-company count, one entry
     *  per day for the last 14 days (0-filled, not just days with data)
     *  so the chart's x-axis is contiguous.
     */
    protected function companySignupsByDay(): array
    {
        $counts = Company::query()
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $days = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $days[$date] = (int) ($counts[$date] ?? 0);
        }

        return $days;
    }

    /**
     * Spatie's Role model has no built-in "count users per role"
     * aggregate, and its query builder doesn't scope by team on plain
     * queries anyway (see App\Modules\Users\Services\RoleService's
     * docblock) - going straight at the pivot table is simplest here.
     *
     * @return array<string, int> role name => user count
     */
    protected function usersByRole(int $companyId): array
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.company_id', $companyId)
            ->where('model_has_roles.model_type', User::class)
            ->selectRaw('roles.name, count(*) as count')
            ->groupBy('roles.name')
            ->pluck('count', 'roles.name')
            ->all();
    }
}
