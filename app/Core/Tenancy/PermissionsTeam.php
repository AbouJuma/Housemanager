<?php

namespace App\Core\Tenancy;

use Spatie\Permission\PermissionRegistrar;

/**
 * Spatie Permission's teams feature scopes every role/permission lookup
 * to whatever team id is currently set on the PermissionRegistrar - not
 * to any particular model's own company_id. Reading or writing roles
 * for a company other than the acting user's current one (e.g. a
 * SaaS-owner action, or seeding a brand-new company's default roles)
 * requires temporarily switching that context and restoring it
 * afterward, which is exactly what this helper does.
 */
final class PermissionsTeam
{
    public static function runAs(?int $companyId, callable $callback): mixed
    {
        $registrar = app(PermissionRegistrar::class);
        $original = $registrar->getPermissionsTeamId();

        $registrar->setPermissionsTeamId($companyId);

        try {
            return $callback();
        } finally {
            $registrar->setPermissionsTeamId($original);
        }
    }
}
