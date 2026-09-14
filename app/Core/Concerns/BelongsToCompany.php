<?php

namespace App\Core\Concerns;

use App\Core\Tenancy\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Row-level multi-tenancy: scopes every query to the current company
 * and stamps new records with it, so cross-tenant leakage requires an
 * explicit opt-out (withoutGlobalScope) rather than an explicit filter.
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder): void {
            if ($companyId = CompanyContext::id()) {
                $builder->where($builder->getModel()->getTable().'.company_id', $companyId);
            }
        });

        static::creating(function ($model): void {
            if (empty($model->company_id) && $companyId = CompanyContext::id()) {
                $model->company_id = $companyId;
            }
        });
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->withoutGlobalScope('company')->where('company_id', $companyId);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(config('tenancy.company_model'), 'company_id');
    }
}
