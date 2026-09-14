<?php

namespace App\Modules\Accounting\Services;

use App\Core\Services\BaseService;
use App\Modules\Accounting\Repositories\AccountRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @property AccountRepositoryInterface $repository see App\Modules\Companies\Services\CompanyService
 *  for why this can't be redeclared as a typed constructor property.
 */
class AccountService extends BaseService
{
    public function __construct(AccountRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function paginate(?string $search, ?string $type, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->query()
            // Single query per sum column across the whole page, not
            // one per row - Account::balance() reads these directly
            // when present.
            ->withSum('lines as debit_total', 'debit')
            ->withSum('lines as credit_total', 'credit')
            ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%");
            }))
            ->when($type, fn ($query) => $query->where('type', $type))
            ->orderBy('code')
            ->paginate($perPage)
            ->withQueryString();
    }
}
