<?php

namespace App\Modules\Companies\Services;

use App\Core\Services\BaseService;
use App\Modules\Companies\Events\CompanyCreated;
use App\Modules\Companies\Models\Company;
use App\Modules\Companies\Repositories\CompanyRepositoryInterface;
use App\Modules\Settings\Services\SettingsService;
use App\Modules\Storage\Services\StorageResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @property CompanyRepositoryInterface $repository PHP forbids narrowing
 *  BaseService::$repository's declared type in a subclass, so the
 *  parent's RepositoryInterface-typed property is reused as-is; the
 *  Companies-specific methods below still exist on it at runtime.
 */
class CompanyService extends BaseService
{
    public function __construct(
        CompanyRepositoryInterface $repository,
        protected SettingsService $settings,
        protected StorageResolver $storage,
    ) {
        parent::__construct($repository);
    }

    public function paginate(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->query()
            ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $attributes): Company
    {
        $attributes['slug'] = $this->uniqueSlug($attributes['slug'] ?? $attributes['name']);
        $attributes['status'] ??= 'trial';
        $attributes['trial_ends_at'] ??= now()->addDays($this->settings->getSystem('default_trial_days'));
        $attributes['currency_code'] ??= $this->settings->getSystem('default_currency_code');

        /** @var Company $company */
        $company = DB::transaction(fn () => $this->repository->create($attributes));

        CompanyCreated::dispatch($company);

        return $company;
    }

    public function update(Model $model, array $attributes): Company
    {
        if (! empty($attributes['slug']) && $attributes['slug'] !== $model->slug) {
            $attributes['slug'] = $this->uniqueSlug($attributes['slug'], $model->id);
        } else {
            unset($attributes['slug']);
        }

        /** @var Company */
        return DB::transaction(fn () => $this->repository->update($model, $attributes));
    }

    public function updateBranding(Company $company, ?UploadedFile $logo, ?string $primaryColor, ?string $secondaryColor): Company
    {
        return DB::transaction(function () use ($company, $logo, $primaryColor, $secondaryColor) {
            if ($logo) {
                $company->addMedia($logo)->toMediaCollection('logo', $this->storage->diskFor($company));
            }

            $company->fill(array_filter([
                'primary_color' => $primaryColor,
                'secondary_color' => $secondaryColor,
            ], fn ($value) => $value !== null));

            $company->save();

            return $company;
        });
    }

    protected function uniqueSlug(string $source, ?int $exceptId = null): string
    {
        $base = Str::slug($source);
        $slug = $base;
        $suffix = 1;

        while ($this->repository->slugExists($slug, $exceptId)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
