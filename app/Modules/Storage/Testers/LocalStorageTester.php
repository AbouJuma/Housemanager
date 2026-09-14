<?php

namespace App\Modules\Storage\Testers;

use App\Modules\Integrations\Contracts\IntegrationTester;
use App\Modules\Integrations\Models\Integration;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Local disk storage needs no credentials, so "testing" it just proves
 * the disk is actually writable (permissions issues on shared hosting
 * are the realistic failure mode, not a wrong config value).
 */
class LocalStorageTester implements IntegrationTester
{
    public function test(Integration $integration): array
    {
        $path = 'integration-tests/'.Str::uuid().'.txt';

        try {
            Storage::disk('public')->put($path, 'connection test');

            return ['success' => true, 'message' => 'Local storage is writable.'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => "Local storage isn't writable: {$e->getMessage()}"];
        } finally {
            Storage::disk('public')->delete($path);
        }
    }
}
