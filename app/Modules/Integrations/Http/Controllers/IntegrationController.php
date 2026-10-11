<?php

namespace App\Modules\Integrations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Integrations\Http\Requests\UpdateIntegrationRequest;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Integrations\Services\IntegrationService;
use App\Modules\Sms\Services\SmsManager;
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

    public function edit(Request $request, string $type, string $provider, SmsManager $smsManager): View
    {
        // Integrations are per company; a platform (SaaS owner) account has none.
        abort_if(! $request->user()->company_id, 403, 'Integrations are set up by each company from its own account.');

        $integration = $this->service->findOrNewFor($request->user()->company_id, $type, $provider);
        $this->authorize('update', $integration);

        $genericLabels = [
            'api_key' => 'API Key',
            'api_secret' => 'API Secret',
            'merchant_id' => 'Merchant ID',
            'username' => 'Username',
            'password' => 'Password',
        ];

        // Only relevant for type=sms: is this company currently relying
        // on the platform's shared Beem account because it has no
        // active SMS integration of its own (any provider, not just the
        // one being edited right now)?
        $usingPlatformSmsFallback = $type === 'sms'
            && $smsManager->isPlatformFallbackAvailable()
            && ! Integration::query()->forCompany($request->user()->company_id)->where('type', 'sms')->where('is_active', true)->exists();

        return view('integrations::edit', [
            'integration' => $integration,
            'typeLabel' => config("integrations.types.{$type}.label"),
            'providerLabel' => config("integrations.types.{$type}.providers.{$provider}"),
            'fieldLabels' => array_merge($genericLabels, config("integrations.types.{$type}.field_labels", [])),
            'requiresCredentials' => ! in_array($provider, config("integrations.types.{$type}.providers_without_credentials", []), true),
            'usingPlatformSmsFallback' => $usingPlatformSmsFallback,
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
