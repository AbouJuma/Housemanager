<?php

namespace App\Modules\VisitorManagement\Services;

use App\Core\Services\BaseService;
use App\Modules\VisitorManagement\Models\Visitor;
use App\Modules\VisitorManagement\Repositories\VisitorRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * @property VisitorRepositoryInterface $repository see App\Modules\Companies\Services\CompanyService
 *  for why this can't be redeclared as a typed constructor property.
 */
class VisitorService extends BaseService
{
    public function __construct(VisitorRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function paginate(?string $search, ?int $propertyId, ?string $status, ?string $purpose, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->query()
            ->with(['property', 'unit', 'tenant', 'vendor'])
            ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('visitor_name', 'like', "%{$search}%")->orWhere('vehicle_plate', 'like', "%{$search}%");
            }))
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->when($status === 'checked_in', fn ($query) => $query->whereNull('check_out_at'))
            ->when($status === 'checked_out', fn ($query) => $query->whereNotNull('check_out_at'))
            ->when($purpose, fn ($query) => $query->where('purpose', $purpose))
            ->latest('check_in_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $attributes): Model
    {
        $attributes['check_in_at'] ??= now();

        return parent::create($attributes);
    }

    /**
     * The only way check_out_at ever gets set - a distinct, explicit
     * action rather than something the update form can set directly, so
     * "who checked this visitor out and when" is always a deliberate
     * record. Gated by the existing `visitors.update` permission, not
     * its own - the same restraint applied to MaintenanceRequest's
     * recordExpense (ARCHITECTURE.md §15).
     */
    public function checkOut(Visitor $visitor, array $attributes): Visitor
    {
        if (! $visitor->isCheckedIn()) {
            throw ValidationException::withMessages(['check_out_at' => 'This visitor has already checked out.']);
        }

        $visitor->update([
            'check_out_at' => $attributes['check_out_at'] ?? now(),
            'checked_out_by_id' => $attributes['checked_out_by_id'] ?? null,
        ]);

        return $visitor->fresh();
    }
}
