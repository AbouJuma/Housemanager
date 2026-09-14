<?php

namespace App\Modules\Inventory\Models;

use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasUuid;
use App\Models\User;
use App\Modules\Inventory\Database\Factories\InventoryTransactionFactory;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable, append-only ledger row - no SoftDeletes, no update() path
 * exposed anywhere (see InventoryItemService::recordTransaction()).
 * Correcting a mistake means logging a new offsetting `adjustment`
 * transaction, the same "never edit history" discipline as
 * JournalEntry (ARCHITECTURE.md §12), just without the formal
 * void-with-reversal ceremony - lower stakes than money, but the same
 * instinct.
 */
class InventoryTransaction extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'company_id',
        'inventory_item_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'maintenance_request_id',
        'reason',
        'recorded_by_id',
        'transaction_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'quantity_before' => 'decimal:2',
        'quantity_after' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    protected static function newFactory(): InventoryTransactionFactory
    {
        return InventoryTransactionFactory::new();
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }
}
