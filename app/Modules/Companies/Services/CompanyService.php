<?php

namespace App\Modules\Companies\Services;

use App\Core\Services\BaseService;
use App\Models\User;
use App\Modules\Users\Services\UserService;
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

    /**
     * A new company together with its first user, who gets the Admin role
     * and is sent their username, password and login link (StaffWelcome).
     *
     * @param  array<string, mixed>  $attributes  company fields plus admin_name, admin_email,
     *                                            admin_username?, admin_phone?, admin_password?
     * @return array{0: Company, 1: User, 2: ?string} the company, its admin and the admin's first password
     */
    public function createWithAdmin(array $attributes): array
    {
        $admin = collect($attributes)->filter(fn ($value, $key) => str_starts_with($key, 'admin_'))
            ->mapWithKeys(fn ($value, $key) => [substr($key, 6) => $value])->all();
        $companyAttributes = collect($attributes)->reject(fn ($value, $key) => str_starts_with($key, 'admin_'))->all();

        return DB::transaction(function () use ($companyAttributes, $admin) {
            $company = $this->create($companyAttributes);

            $users = app(UserService::class);
            $user = $users->create([
                'name' => $admin['name'],
                'username' => $admin['username'] ?? null,
                'email' => $admin['email'],
                'phone' => $admin['phone'] ?? null,
                'password' => $admin['password'] ?? null,
                'company_id' => $company->id,
                'roles' => ['Admin'],
            ]);

            return [$company, $user, $users->issuedPassword()];
        });
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

    /**
     * Back to the app's default palette (BrandTheme::DEFAULT_*), and
     * optionally the default logo mark too.
     */
    public function resetBranding(Company $company, bool $removeLogo = false): Company
    {
        return DB::transaction(function () use ($company, $removeLogo) {
            $company->forceFill(['primary_color' => null, 'secondary_color' => null])->save();

            if ($removeLogo) {
                $company->clearMediaCollection('logo');
            }

            return $company;
        });
    }

    public function removeLogo(Company $company): Company
    {
        $company->clearMediaCollection('logo');

        return $company;
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
