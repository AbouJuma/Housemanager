<?php

namespace App\Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Settings\Http\Requests\UpdateCompanySettingsRequest;
use App\Modules\Settings\Http\Requests\UpdateSystemSettingsRequest;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(protected SettingsService $service)
    {
    }

    public function edit(Request $request): View
    {
        $this->authorize('settings.view-company');

        $values = $this->service->allForCompany($request->user()->company_id);

        return view('settings::edit', compact('values'));
    }

    public function update(UpdateCompanySettingsRequest $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;

        foreach ($request->validated() as $key => $value) {
            $this->service->setForCompany($companyId, $key, $value);
        }

        return redirect()->route('settings.edit')->with('success', 'Settings updated.');
    }

    public function systemEdit(): View
    {
        $this->authorize('settings.view-system');

        $values = $this->service->allSystem();

        return view('settings::system', compact('values'));
    }

    public function systemUpdate(UpdateSystemSettingsRequest $request): RedirectResponse
    {
        foreach ($request->validated() as $key => $value) {
            $this->service->setSystem($key, $value);
        }

        return redirect()->route('settings.system.edit')->with('success', 'System settings updated.');
    }
}
