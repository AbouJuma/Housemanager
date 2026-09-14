<?php

namespace App\Modules\Companies\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Companies\Models\Company;

class EloquentCompanyRepository extends BaseRepository implements CompanyRepositoryInterface
{
    public function __construct(Company $model)
    {
        parent::__construct($model);
    }

    public function findBySlug(string $slug): ?Company
    {
        return $this->query()->where('slug', $slug)->first();
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        return $this->query()
            ->where('slug', $slug)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists();
    }
}
