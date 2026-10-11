<?php

namespace App\Modules\Storage\Services;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use Illuminate\Support\Facades\Config;
use RuntimeException;

/**
 * Resolves which Laravel filesystem disk a company's files should be
 * written to, based on its active "storage" integration (Module 7 -
 * Integrations Center). S3-compatible providers (s3/r2/spaces) get
 * their stored credentials assembled into a disk config registered at
 * config('filesystems.disks.company') and returned under that fixed
 * name; no active integration, or the 'local' provider, falls back to
 * the 'public' disk shipped with every Laravel app.
 *
 * A fixed disk name (rather than one keyed by company id) is safe here
 * because only one company is ever in scope per request (see
 * App\Core\Tenancy\CompanyContext) and this app deliberately targets
 * non-persistent PHP workers (shared hosting, no Octane/Swoole) where
 * Config::set() never survives past the request that set it.
 */
class StorageResolver
{
    public const COMPANY_DISK = 'company';

    public const S3_COMPATIBLE_PROVIDERS = ['s3', 'r2', 'spaces', 'gcs'];

    /**
     * The disk to use for a company's file operations right now, based
     * on whichever storage integration that company has marked active.
     */
    public function diskFor(Company $company): string
    {
        $integration = Integration::query()
            ->forCompany($company->id)
            ->where('type', 'storage')
            ->where('is_active', true)
            ->first();

        if (! $integration || $integration->provider === 'local') {
            return 'public';
        }

        return $this->diskForIntegration($integration);
    }

    /**
     * The disk built from one specific integration's stored credentials,
     * regardless of whether it's the company's currently active one -
     * this is what "Test Connection" needs, so a provider can be
     * verified before being switched on.
     */
    public function diskForIntegration(Integration $integration): string
    {
        if (! in_array($integration->provider, self::S3_COMPATIBLE_PROVIDERS, true)) {
            throw new RuntimeException(
                "Storage provider [{$integration->provider}] isn't wired up yet — ".
                'pick Local Storage, Amazon S3, Cloudflare R2, DigitalOcean Spaces or Google Cloud Storage for now.'
            );
        }

        // Without these the S3 driver dies with a bare TypeError deep in
        // Flysystem - fail with something a user can act on instead.
        foreach (['merchant_id' => 'bucket', 'api_key' => 'access key', 'api_secret' => 'secret key'] as $field => $label) {
            if (blank($integration->credential($field))) {
                throw new RuntimeException(
                    "Storage provider [{$integration->provider}] isn't fully set up yet - its {$label} is missing. ".
                    'Add it under Integrations, or switch back to Local Storage.'
                );
            }
        }

        Config::set('filesystems.disks.'.self::COMPANY_DISK, $this->buildDiskConfig($integration));

        return self::COMPANY_DISK;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildDiskConfig(Integration $integration): array
    {
        // The Integrations Center's five credential fields are generic
        // across every integration type (see config/integrations.php's
        // 'field_labels'); for storage they're repurposed as: api_key =
        // access key, api_secret = secret key, merchant_id = bucket,
        // username = region, password = custom endpoint (R2/Spaces).
        $config = [
            'driver' => 's3',
            'key' => $integration->credential('api_key'),
            'secret' => $integration->credential('api_secret'),
            'region' => $integration->credential('username') ?: 'auto',
            'bucket' => $integration->credential('merchant_id'),
            'visibility' => 'public',
            'throw' => true,
        ];

        if ($endpoint = $integration->credential('password')) {
            $config['endpoint'] = $endpoint;
            $config['use_path_style_endpoint'] = true;
        }

        return $config;
    }
}
