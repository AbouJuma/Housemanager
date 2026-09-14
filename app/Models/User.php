<?php

namespace App\Models;

use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasUuid;
use App\Core\Tenancy\PermissionsTeam;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use BelongsToCompany;
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use HasUuid;
    use LogsActivity;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'company_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isSaasOwner(): bool
    {
        return is_null($this->company_id);
    }

    /**
     * getRoleNames()/hasRole() etc. resolve against whatever team is
     * currently set on the PermissionRegistrar (the acting user's
     * company), not this model's own company_id - so reading a user's
     * roles from a cross-company context (e.g. a SaaS-owner list view)
     * needs this instead of calling getRoleNames() directly.
     */
    public function roleNamesForOwnCompany(): \Illuminate\Support\Collection
    {
        return PermissionsTeam::runAs($this->company_id, fn () => $this->getRoleNames());
    }

    public function getActivitylogOptions(): LogOptions
    {
        // Allowlist, not blocklist: password/remember_token must never
        // reach the log, and this guarantees it regardless of what
        // future columns get added to $fillable.
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'company_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('user');
    }
}
