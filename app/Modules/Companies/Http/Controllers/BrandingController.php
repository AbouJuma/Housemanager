<?php

namespace App\Modules\Companies\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Companies\Http\Requests\UpdateCompanyBrandingRequest;
use App\Modules\Companies\Models\Company;
use App\Modules\Companies\Services\CompanyService;
use App\Modules\Companies\Support\BrandTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/**
 * A company admin's own branding screen (Settings -> Branding) - logo
 * and colors applied across the back office, the tenant/landlord
 * portals and the mobile app. Gated by the same permissions as company
 * settings, unlike CompanyController::updateBranding() which is the
 * SaaS owner's per-company view.
 */
class BrandingController extends Controller
{
    public function __construct(protected CompanyService $service) {}

    public function edit(Request $request): View
    {
        $this->authorize('settings.view-company');

        $company = $this->company($request);

        return view('companies::branding', [
            'company' => $company,
            'theme' => BrandTheme::forCompany($company),
            'presets' => BrandTheme::PRESETS,
        ]);
    }

    public function update(UpdateCompanyBrandingRequest $request): RedirectResponse
    {
        $this->authorize('settings.update-company');

        $company = $this->company($request);

        try {
            if ($request->boolean('reset')) {
                $this->service->resetBranding($company);

                return redirect()->route('branding.edit')->with('success', 'Branding reset to the default colors.');
            }

            if ($request->boolean('remove_logo')) {
                $this->service->removeLogo($company);
            }

            $this->service->updateBranding(
                $company,
                $request->file('logo'),
                $request->string('primary_color')->toString() ?: null,
                $request->string('secondary_color')->toString() ?: null,
            );
        } catch (RuntimeException $e) {
            return redirect()->route('branding.edit')->with('error', $e->getMessage());
        }

        return redirect()->route('branding.edit')->with('success', 'Branding updated - your new look is live.');
    }

    protected function company(Request $request): Company
    {
        abort_if($request->user()->isSaasOwner() || ! $request->user()->company_id, 403);

        return Company::query()->findOrFail($request->user()->company_id);
    }
}
