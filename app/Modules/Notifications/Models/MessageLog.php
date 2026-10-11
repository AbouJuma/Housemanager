<?php

namespace App\Modules\Notifications\Models;

use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One row per outbound SMS/email attempt, written by
 * App\Modules\Notifications\Channels\CompanySmsChannel and
 * CompanyMailChannel - every notification routed through either of
 * those two channels is logged here automatically, current and future,
 * with no per-notification-class changes needed. `company_id` is
 * always passed explicitly at write time (never relies on
 * BelongsToCompany's CompanyContext-stamping) since these channels
 * often run from scheduled commands with no active request/company
 * context.
 */
class MessageLog extends Model
{
    use BelongsToCompany;
    use HasUuid;

    protected $fillable = [
        'company_id',
        'notifiable_type',
        'notifiable_id',
        'channel',
        'recipient',
        'message_type',
        'status',
        'provider',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeBetween(Builder $query, $start, $end): Builder
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    public function scopeNotifiableType(Builder $query, string $notifiableType): Builder
    {
        return $query->where('notifiable_type', $notifiableType);
    }
}
