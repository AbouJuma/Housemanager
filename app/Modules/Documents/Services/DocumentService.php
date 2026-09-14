<?php

namespace App\Modules\Documents\Services;

use App\Core\Services\BaseService;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Repositories\DocumentRepositoryInterface;
use App\Modules\Storage\Services\StorageResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * @property DocumentRepositoryInterface $repository see App\Modules\Companies\Services\CompanyService
 *  for why this can't be redeclared as a typed constructor property.
 */
class DocumentService extends BaseService
{
    public function __construct(
        DocumentRepositoryInterface $repository,
        protected StorageResolver $storage,
    ) {
        parent::__construct($repository);
    }

    /**
     * @param  class-string|null  $documentableType  the resolved FQCN, not the short form-facing key
     */
    public function paginate(?string $search, ?string $category, ?string $documentableType, bool $expiringOnly, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->query()
            ->with(['documentable', 'uploadedBy'])
            ->when($search, fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->when($category, fn ($query) => $query->where('category', $category))
            ->when($documentableType, fn ($query) => $query->where('documentable_type', $documentableType))
            ->when($expiringOnly, fn ($query) => $query->whereNotNull('expiry_date')->where('expiry_date', '<=', now()->addDays(30)->toDateString()))
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * $attributes['file'] is pulled out and attached via Spatie Media
     * Library after the row exists, resolving the destination disk from
     * the owning company's active storage integration - the same
     * addMedia()-inside-DB::transaction() pattern CompanyService::
     * updateBranding() already established.
     */
    public function create(array $attributes): Model
    {
        $file = Arr::pull($attributes, 'file');

        return DB::transaction(function () use ($attributes, $file) {
            /** @var Document $document */
            $document = $this->repository->create($attributes);

            if ($file) {
                $document->addMedia($file)->toMediaCollection('file', $this->storage->diskFor($document->company));
            }

            return $document;
        });
    }

    /**
     * A replacement file is optional - if none is sent, the existing
     * one stays untouched, the same "nullable, only replaced if
     * provided" shape as CompanyService::updateBranding()'s logo.
     */
    public function update(Model $model, array $attributes): Model
    {
        $file = Arr::pull($attributes, 'file');

        return DB::transaction(function () use ($model, $attributes, $file) {
            /** @var Document $document */
            $document = $this->repository->update($model, $attributes);

            if ($file) {
                $document->clearMediaCollection('file');
                $document->addMedia($file)->toMediaCollection('file', $this->storage->diskFor($document->company));
            }

            return $document;
        });
    }
}
