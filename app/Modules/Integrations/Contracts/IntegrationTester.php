<?php

namespace App\Modules\Integrations\Contracts;

use App\Modules\Integrations\Models\Integration;

/**
 * Implemented by each real gateway driver as it's built (Modules 8-11:
 * Storage, Email, SMS, Payment). Module 7 builds the Integrations
 * Center itself - the encrypted storage, the CRUD, the "Test
 * Connection" button and its plumbing - but registers no testers of
 * its own. Until a provider has one registered in
 * config('integrations.testers'), IntegrationService::test() reports
 * that honestly rather than faking a connectivity result.
 */
interface IntegrationTester
{
    /**
     * @return array{success: bool, message: string}
     */
    public function test(Integration $integration): array;
}
