<?php

namespace App\Modules\Companies\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Companies\Http\Requests\StoreCompanyRequest;
use App\Modules\Companies\Http\Requests\UpdateCompanyBrandingRequest;
use App\Modules\Companies\Http\Requests\UpdateCompanyRequest;
use App\Modules\Companies\Models\Company;
use App\Modules\Companies\Services\CompanyService;
use App\Modules\Subscriptions\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class CompanyController extends Controller
{
    public function __construct(protected CompanyService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Company::class);

        $companies = $this->service->paginate($request->string('search')->toString() ?: null);

        return view('companies::index', compact('companies'));
    }

    public function create(): View
    {
        $this->authorize('create', Company::class);

        return view('companies::create');
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        $company = $this->service->create($request->validated());

        return redirect()->route('companies.show', $company)->with('success', "Company \"{$company->name}\" created.");
    }

    public function show(Company $company): View
    {
        $this->authorize('view', $company);

        $subscription = $company->currentSubscription();
        $subscriptionInvoices = $company->subscriptionInvoices()->latest('issue_date')->get();
        $subscriptionPlans = auth()->user()->isSaasOwner()
            ? SubscriptionPlan::query()->where('is_active', true)->orderBy('sort_order')->get()
            : collect();

        return view('companies::show', compact('company', 'subscription', 'subscriptionInvoices', 'subscriptionPlans'));
    }

    public function edit(Company $company): View
    {
        $this->authorize('update', $company);

        return view('companies::edit', compact('company'));
    }

    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        $this->service->update($company, $request->validated());

        return redirect()->route('companies.show', $company)->with('success', 'Company updated.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->authorize('delete', $company);

        $this->service->delete($company);

        return redirect()->route('companies.index')->with('success', "Company \"{$company->name}\" deleted.");
    }

    public function updateBranding(UpdateCompanyBrandingRequest $request, Company $company): RedirectResponse
    {
        try {
            $this->service->updateBranding(
                $company,
                $request->file('logo'),
                $request->string('primary_color')->toString() ?: null,
                $request->string('secondary_color')->toString() ?: null,
            );
        } catch (RuntimeException $e) {
            return redirect()->route('companies.show', $company)->with('error', $e->getMessage());
        }

        return redirect()->route('companies.show', $company)->with('success', 'Branding updated.');
    }
}
