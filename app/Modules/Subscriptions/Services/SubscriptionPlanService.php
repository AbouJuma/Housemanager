<?php

namespace App\Modules\Subscriptions\Services;

use App\Core\Services\BaseService;
use App\Modules\Subscriptions\Repositories\SubscriptionPlanRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property SubscriptionPlanRepositoryInterface $repository see App\Modules\Companies\Services\CompanyService
 *  for why this can't be redeclared as a typed constructor property.
 */
class SubscriptionPlanService extends BaseService
{
    public function __construct(SubscriptionPlanRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function paginate(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->query()
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $attributes): Model
    {
        $attributes['slug'] ??= Str::slug($attributes['name']);

        return parent::create($attributes);
    }

    public function update(Model $model, array $attributes): Model
    {
        $attributes['slug'] ??= Str::slug($attributes['name'] ?? $model->name);

        return parent::update($model, $attributes);
    }
}
