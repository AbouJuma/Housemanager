<?php

namespace App\Modules\Storage\Testers;

use App\Modules\Integrations\Contracts\IntegrationTester;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Storage\Services\StorageResolver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Shared by s3/r2/spaces (all S3-compatible via Laravel's 's3' driver -
 * see StorageResolver::buildDiskConfig()). Builds a disk from *this*
 * integration's stored credentials regardless of whether it's the
 * company's currently active provider, so a config can be verified
 * before being switched on.
 */
class S3CompatibleTester implements IntegrationTester
{
    public function __construct(protected StorageResolver $resolver)
    {
    }

    public function test(Integration $integration): array
    {
        $path = 'integration-tests/'.Str::uuid().'.txt';

        try {
            $disk = $this->resolver->diskForIntegration($integration);

            Storage::disk($disk)->put($path, 'connection test');
            Storage::disk($disk)->delete($path);

            return ['success' => true, 'message' => 'Successfully wrote and deleted a test file in the bucket.'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => "Connection failed: {$e->getMessage()}"];
        }
    }
}
