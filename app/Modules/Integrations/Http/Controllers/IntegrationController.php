<?php

namespace App\Modules\Integrations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Integrations\Http\Requests\UpdateIntegrationRequest;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Integrations\Services\IntegrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IntegrationController extends Controller
{
    public function __construct(protected IntegrationService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Integration::class);

        $configured = Integration::query()
            ->where('company_id', $request->user()->company_id)
            ->get()
            ->keyBy(fn (Integration $integration) => "{$integration->type}.{$integration->provider}");

        return view('integrations::index', [
            'types' => config('integrations.types'),
            'configured' => $configured,
        ]);
    }

    public function edit(Request $request, string $type, string $provider): View
    {
        $integration = $this->service->findOrNewFor($request->user()->company_id, $type, $provider);
        $this->authorize('update', $integration);

        $genericLabels = [
            'api_key' => 'API Key',
            'api_secret' => 'API Secret',
            'merchant_id' => 'Merchant ID',
            'username' => 'Username',
            'password' => 'Password',
        ];

        return view('integrations::edit', [
            'integration' => $integration,
            'typeLabel' => config("integrations.types.{$type}.label"),
            'providerLabel' => config("integrations.types.{$type}.providers.{$provider}"),
            'fieldLabels' => array_merge($genericLabels, config("integrations.types.{$type}.field_labels", [])),
            'requiresCredentials' => ! in_array($provider, config("integrations.types.{$type}.providers_without_credentials", []), true),
        ]);
    }

    public function update(UpdateIntegrationRequest $request, string $type, string $provider): RedirectResponse
    {
        $integration = $this->service->findOrNewFor($request->user()->company_id, $type, $provider);
        $this->authorize('update', $integration);

        $this->service->save($integration, $request->validated());

        return redirect()->route('integrations.edit', [$type, $provider])->with('success', 'Integration saved.');
    }

    public function test(Request $request, string $type, string $provider): RedirectResponse
    {
        $integration = $this->service->findOrNewFor($request->user()->company_id, $type, $provider);
        $this->authorize('update', $integration);

        if (! $integration->exists) {
            return redirect()->route('integrations.edit', [$type, $provider])
                ->with('error', 'Save the integration before testing the connection.');
        }

        $result = $this->service->test($integration);

        return redirect()->route('integrations.edit', [$type, $provider])
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
