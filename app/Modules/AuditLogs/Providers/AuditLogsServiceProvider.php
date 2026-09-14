<?php

namespace App\Modules\AuditLogs\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Modules\AuditLogs\Models\ActivityLogEntry;
use App\Modules\AuditLogs\Policies\AuditLogPolicy;
use Illuminate\Support\Facades\Gate;

class AuditLogsServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(ActivityLogEntry::class, AuditLogPolicy::class);
    }
}
