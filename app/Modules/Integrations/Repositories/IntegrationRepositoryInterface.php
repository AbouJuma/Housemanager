<?php

namespace App\Modules\Integrations\Repositories;

use App\Core\Repositories\RepositoryInterface;
use App\Modules\Integrations\Models\Integration;

interface IntegrationRepositoryInterface extends RepositoryInterface
{
    public function findForCompany(int $companyId, string $type, string $provider): ?Integration;
}
