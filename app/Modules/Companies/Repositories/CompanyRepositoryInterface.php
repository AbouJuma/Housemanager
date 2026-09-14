<?php

namespace App\Modules\Companies\Repositories;

use App\Core\Repositories\RepositoryInterface;
use App\Modules\Companies\Models\Company;

interface CompanyRepositoryInterface extends RepositoryInterface
{
    public function findBySlug(string $slug): ?Company;

    public function slugExists(string $slug, ?int $exceptId = null): bool;
}
