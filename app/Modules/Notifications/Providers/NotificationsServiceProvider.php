<?php

namespace App\Modules\Notifications\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Modules\Notifications\Channels\CompanyMailChannel;
use App\Modules\Notifications\Channels\CompanySmsChannel;
use App\Modules\Notifications\Console\Commands\NotifyTrialsEndingSoon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Notification;

class NotificationsServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        parent::boot();

        Notification::extend('company-mail', fn ($app) => $app->make(CompanyMailChannel::class));
        Notification::extend('company-sms', fn ($app) => $app->make(CompanySmsChannel::class));

        $this->commands([NotifyTrialsEndingSoon::class]);

        // Scheduled here rather than in routes/console.php, so the
        // module stays self-contained - no core file needs editing to
        // add or remove a module's scheduled work (see ARCHITECTURE.md
        // §1's module philosophy).
        $this->app->booted(function (): void {
            $this->app->make(Schedule::class)->command(NotifyTrialsEndingSoon::class)->daily();
        });
    }
}
