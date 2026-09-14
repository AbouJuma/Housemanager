<?php

namespace App\Modules\Settings\Services;

use App\Core\Tenancy\CompanyContext;
use App\Modules\Settings\Models\Setting;
use InvalidArgumentException;

/**
 * A generic key/value registry so a module can read/write a setting
 * without a migration of its own - only `config/settings.php` needs a
 * new catalog entry. `company_id === null` rows are system-wide
 * (SaaS-owner scope); everything else belongs to exactly one company.
 */
class SettingsService
{
    public function getSystem(string $key): mixed
    {
        return $this->get(null, $key);
    }

    public function setSystem(string $key, mixed $value): void
    {
        $this->set(null, $key, $value, 'system');
    }

    public function getForCompany(int $companyId, string $key): mixed
    {
        return $this->get($companyId, $key);
    }

    public function setForCompany(int $companyId, string $key, mixed $value): void
    {
        $this->set($companyId, $key, $value, 'company');
    }

    /**
     * Reads a company setting for whichever company is currently in
     * context (App\Core\Tenancy\CompanyContext, set by IdentifyCompany).
     * Falls back to the catalog default with no company in context
     * (e.g. a SaaS-owner request) rather than throwing, since callers
     * like Blade views want a sane value more than a hard error.
     */
    public function forCurrentCompany(string $key): mixed
    {
        $companyId = CompanyContext::id();

        return $companyId ? $this->get($companyId, $key) : $this->catalogEntry('company', $key)['default'];
    }

    public function allForCompany(int $companyId): array
    {
        return $this->allForScope('company', $companyId);
    }

    public function allSystem(): array
    {
        return $this->allForScope('system', null);
    }

    protected function get(?int $companyId, string $key): mixed
    {
        $scope = $companyId === null ? 'system' : 'company';
        $entry = $this->catalogEntry($scope, $key);

        $row = Setting::query()->where('company_id', $companyId)->where('key', $key)->first();

        return $row ? $this->cast($row->value, $entry['type']) : $entry['default'];
    }

    protected function set(?int $companyId, string $key, mixed $value, string $scope): void
    {
        $this->catalogEntry($scope, $key); // validates the key is known

        Setting::query()->updateOrCreate(
            ['company_id' => $companyId, 'key' => $key],
            ['value' => $value],
        );
    }

    protected function allForScope(string $scope, ?int $companyId): array
    {
        $catalog = config("settings.{$scope}", []);
        $rows = Setting::query()->where('company_id', $companyId)->get()->keyBy('key');

        $values = [];
        foreach ($catalog as $key => $entry) {
            $values[$key] = $rows->has($key) ? $this->cast($rows[$key]->value, $entry['type']) : $entry['default'];
        }

        return $values;
    }

    protected function catalogEntry(string $scope, string $key): array
    {
        $entry = config("settings.{$scope}.{$key}");

        if ($entry === null) {
            throw new InvalidArgumentException("Unknown {$scope} setting [{$key}]. Add it to config/settings.php first.");
        }

        return $entry;
    }

    protected function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => (bool) $value,
            default => $value,
        };
    }
}
