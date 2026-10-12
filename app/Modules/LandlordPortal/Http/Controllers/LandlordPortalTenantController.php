<?php

namespace App\Modules\LandlordPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LandlordPortal\Http\Requests\LandlordTenantRequest;
use App\Modules\LandlordPortal\Services\LandlordManagementService;
use App\Modules\LandlordPortal\Support\LandlordAccess;
use App\Modules\LandlordPortal\Support\LandlordTenantPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LandlordPortalTenantController extends Controller
{
    public function __construct(protected LandlordManagementService $service) {}

    public function index(Request $request): View
    {
        $landlord = Auth::guard('landlord')->user();
        LandlordAccess::authorize($landlord, 'tenants.viewAny');
        $search = $request->string('search')->trim()->toString();

        $tenants = $landlord->tenantsQuery()
            ->with(LandlordTenantPresenter::with($landlord))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('landlord-portal::tenants.index', [
            'tenants' => $tenants,
            'rows' => $tenants->getCollection()->map(fn ($tenant) => LandlordTenantPresenter::row($tenant, $landlord)),
            'canCreate' => LandlordAccess::can($landlord, 'tenants.create'),
        ]);
    }

    public function create(): View
    {
        LandlordAccess::authorize(Auth::guard('landlord')->user(), 'tenants.create');

        return view('landlord-portal::tenants.create');
    }

    public function store(LandlordTenantRequest $request): RedirectResponse
    {
        [$tenant, $password] = $this->service->createTenant($request->user(), $request->validated());

        return redirect()->route('landlord-portal.tenants.index')->with('success', __(
            'Tenant ":name" added. Portal login - username: :username, password: :password',
            ['name' => $tenant->name, 'username' => $tenant->username, 'password' => $password],
        ));
    }
}
