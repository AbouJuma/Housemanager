<?php

namespace App\Modules\Inventory\Services;

use App\Core\Services\BaseService;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Repositories\InventoryItemRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * @property InventoryItemRepositoryInterface $repository see App\Modules\Companies\Services\CompanyService
 *  for why this can't be redeclared as a typed constructor property.
 */
class InventoryItemService extends BaseService
{
    public function __construct(InventoryItemRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function paginate(?string $search, ?string $category, ?string $status, bool $lowStockOnly, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->query()
            ->with(['property', 'vendor'])
            ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%");
            }))
            ->when($category, fn ($query) => $query->where('category', $category))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($lowStockOnly, fn ($query) => $query->whereNotNull('reorder_level')->whereColumn('quantity_on_hand', '<=', 'reorder_level'))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * quantity_on_hand always starts at zero, regardless of what the
     * caller sends - it's system-derived, only ever moved by
     * recordTransaction(). Initial stock on hand is recorded as a real
     * `receipt` transaction right after creation, not backdoored in here.
     */
    public function create(array $attributes): Model
    {
        return parent::create([...$attributes, 'quantity_on_hand' => 0]);
    }

    public function update(Model $model, array $attributes): Model
    {
        unset($attributes['quantity_on_hand']);

        return parent::update($model, $attributes);
    }

    /**
     * The only way quantity_on_hand ever changes. Computes the signed
     * delta from `type` (+magnitude for receipt, -magnitude for usage,
     * caller-chosen `direction` for adjustment) and records both the
     * delta and a quantity_before/quantity_after snapshot on an
     * immutable InventoryTransaction row - see ARCHITECTURE.md §16.
     * Stock can never go negative; over-consuming throws.
     */
    public function recordTransaction(InventoryItem $item, array $attributes): InventoryItem
    {
        $magnitude = (float) $attributes['quantity'];

        $delta = match ($attributes['type']) {
            'receipt' => $magnitude,
            'usage' => -$magnitude,
            'adjustment' => ($attributes['direction'] ?? 'increase') === 'decrease' ? -$magnitude : $magnitude,
        };

        return DB::transaction(function () use ($item, $attributes, $delta) {
            $quantityBefore = (float) $item->quantity_on_hand;
            $quantityAfter = $quantityBefore + $delta;

            if ($quantityAfter < 0) {
                throw ValidationException::withMessages(['quantity' => 'This would take stock on hand below zero.']);
            }

            // forceFill, not update() - quantity_on_hand is deliberately
            // absent from InventoryItem::$fillable (see the migration
            // comment), so a plain mass-assigned update() would silently
            // discard it.
            $item->forceFill(['quantity_on_hand' => $quantityAfter])->save();

            $item->transactions()->create([
                'company_id' => $item->company_id,
                'type' => $attributes['type'],
                'quantity' => $delta,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'maintenance_request_id' => $attributes['maintenance_request_id'] ?? null,
                'reason' => $attributes['reason'] ?? null,
                'recorded_by_id' => $attributes['recorded_by_id'] ?? null,
                'transaction_date' => $attributes['transaction_date'],
            ]);

            return $item->fresh();
        });
    }
}
