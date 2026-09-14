<?php

namespace App\Modules\Maintenance\Models;

use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasUuid;
use App\Models\User;
use App\Modules\Expenses\Models\Expense;
use App\Modules\Inventory\Models\InventoryTransaction;
use App\Modules\Maintenance\Database\Factories\MaintenanceRequestFactory;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\Vendors\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MaintenanceRequest extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'property_id',
        'unit_id',
        'tenant_id',
        'vendor_id',
        'expense_id',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'reported_date',
        'scheduled_date',
        'completed_date',
        'estimated_cost',
        'actual_cost',
        'notes',
        'reported_by_id',
    ];

    protected $attributes = [
        'category' => 'other',
        'priority' => 'medium',
        'status' => 'open',
    ];

    protected $casts = [
        'reported_date' => 'date',
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
    ];

    protected static function newFactory(): MaintenanceRequestFactory
    {
        return MaintenanceRequestFactory::new();
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_id');
    }

    /**
     * Populated purely as a byproduct of recording a `usage` transaction
     * from the Inventory item's own side (Module 23) - this request has
     * no action of its own that creates these rows.
     */
    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function hasRecordedExpense(): bool
    {
        return $this->expense_id !== null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'priority'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('maintenance_request');
    }
}
