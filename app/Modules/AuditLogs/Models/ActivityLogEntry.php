<?php

namespace App\Modules\AuditLogs\Models;

use App\Core\Tenancy\CompanyContext;
use App\Modules\Companies\Models\Company;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity;

/**
 * Spatie's own Activity model is package code we don't own, so tenant
 * scoping is bolted on here instead: `config('activitylog.activity_model')`
 * points at this subclass, and every entry is auto-stamped with the
 * company the *subject being logged* belongs to - not the acting
 * user's own company. A SaaS-owner editing a specific company's data
 * must still show up in that company's own audit log, not vanish into
 * a "system-wide" bucket just because the SaaS owner has no company of
 * their own (CompanyContext would be null for them).
 */
class ActivityLogEntry extends Activity
{
    protected static function booted(): void
    {
        static::creating(function (self $activity): void {
            if ($activity->company_id !== null) {
                return;
            }

            $activity->company_id = static::resolveCompanyId($activity);
        });
    }

    protected static function resolveCompanyId(self $activity): ?int
    {
        $subject = $activity->subject;

        if ($subject instanceof Company) {
            return $subject->id;
        }

        if ($subject !== null && $subject->exists) {
            // A freshly created $subject's in-memory attributes only
            // include what was explicitly passed to create() -
            // company_id is absent entirely (not merely null) unless
            // the caller set it, so a raw array_key_exists()/isset()
            // check on the cached relation is unreliable either way.
            // fresh() re-queries the real row so every actual column,
            // including a genuinely-null company_id, is present.
            $subject = $subject->fresh();
        }

        if ($subject !== null && array_key_exists('company_id', $subject->getAttributes())) {
            return $subject->company_id;
        }

        return CompanyContext::id();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
