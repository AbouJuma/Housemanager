<?php

namespace App\Core\Services;

use App\Core\Repositories\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class BaseService
{
    public function __construct(protected RepositoryInterface $repository)
    {
    }

    public function create(array $attributes): Model
    {
        return DB::transaction(fn () => $this->repository->create($attributes));
    }

    public function update(Model $model, array $attributes): Model
    {
        return DB::transaction(fn () => $this->repository->update($model, $attributes));
    }

    public function delete(Model $model): bool
    {
        return DB::transaction(fn () => $this->repository->delete($model));
    }
}
