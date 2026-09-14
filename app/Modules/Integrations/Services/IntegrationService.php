<?php

namespace App\Modules\Integrations\Services;

use App\Modules\Integrations\Contracts\IntegrationTester;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Integrations\Repositories\IntegrationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class IntegrationService
{
    public function __construct(protected IntegrationRepositoryInterface $repository)
    {
    }

    public function findOrNewFor(int $companyId, string $type, string $provider): Integration
    {
        $this->assertKnownProvider($type, $provider);

        return $this->repository->findForCompany($companyId, $type, $provider)
            ?? new Integration(['company_id' => $companyId, 'type' => $type, 'provider' => $provider]);
    }

    public function save(Integration $integration, array $attributes): Integration
    {
        // Blank credential fields mean "leave unchanged" - the form
        // never re-displays a decrypted secret to type over - so merge
        // onto whatever's already stored rather than replacing it
        // wholesale.
        $credentials = array_filter([
            'api_key' => $attributes['api_key'] ?? null,
            'api_secret' => $attributes['api_secret'] ?? null,
            'merchant_id' => $attributes['merchant_id'] ?? null,
            'username' => $attributes['username'] ?? null,
            'password' => $attributes['password'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        $merged = array_merge($integration->credentials ?? [], $credentials);

        return DB::transaction(function () use ($integration, $attributes, $merged) {
            $isNew = ! $integration->exists;

            $integration->fill([
                'mode' => $attributes['mode'] ?? 'sandbox',
                'is_active' => (bool) ($attributes['is_active'] ?? false),
                'webhook_url' => $attributes['webhook_url'] ?? null,
                'callback_url' => $attributes['callback_url'] ?? null,
                'credentials' => $merged,
            ]);

            if ($isNew) {
                $integration->status = 'untested';
            }

            $integration->save();

            return $integration;
        });
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function test(Integration $integration): array
    {
        $testerClass = config("integrations.testers.{$integration->provider}");

        if (! $testerClass || ! is_subclass_of($testerClass, IntegrationTester::class)) {
            $integration->update(['status' => 'untested', 'last_tested_at' => now()]);

            return [
                'success' => false,
                'message' => "No connection tester is available for this provider yet — it arrives when the {$integration->type} framework is built.",
            ];
        }

        $result = app($testerClass)->test($integration);

        $integration->update([
            'status' => $result['success'] ? 'connected' : 'failed',
            'last_tested_at' => now(),
        ]);

        return $result;
    }

    protected function assertKnownProvider(string $type, string $provider): void
    {
        if (! array_key_exists($provider, config("integrations.types.{$type}.providers", []))) {
            throw new InvalidArgumentException("Unknown provider [{$provider}] for integration type [{$type}].");
        }
    }
}
