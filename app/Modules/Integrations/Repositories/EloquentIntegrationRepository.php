<?php

namespace App\Modules\Integrations\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Integrations\Models\Integration;

class EloquentIntegrationRepository extends BaseRepository implements IntegrationRepositoryInterface
{
    public function __construct(Integration $model)
    {
        parent::__construct($model);
    }

    public function findForCompany(int $companyId, string $type, string $provider): ?Integration
    {
        return $this->query()
            ->where('company_id', $companyId)
            ->where('type', $type)
            ->where('provider', $provider)
            ->first();
    }
}
