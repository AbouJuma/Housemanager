<?php

namespace App\Modules\Companies\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Companies\Http\Requests\UpdateCompanyProfileRequest;
use App\Modules\Companies\Models\Company;
use App\Modules\Companies\Services\CompanyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Settings -> Company Profile: a company admin keeps their own name,
 * contacts, address, timezone and currency up to date. Same gate as the
 * other company settings screens (SettingsController, BrandingController).
 */
class CompanyProfileController extends Controller
{
    public function __construct(protected CompanyService $service) {}

    public function edit(Request $request): View
    {
        $this->authorize('settings.view-company');

        return view('companies::profile', [
            'company' => $this->company($request),
            'currencies' => config('company.currencies'),
            'commonTimezones' => config('company.common_timezones'),
            'regions' => array_keys(config('tanzania.regions')),
        ]);
    }

    public function update(UpdateCompanyProfileRequest $request): RedirectResponse
    {
        $this->service->update($this->company($request), $request->validated());

        return redirect()->route('company-profile.edit')->with('success', __('Company details updated.'));
    }

    protected function company(Request $request): Company
    {
        abort_if($request->user()->isSaasOwner() || ! $request->user()->company_id, 403);

        return Company::query()->findOrFail($request->user()->company_id);
    }
}
