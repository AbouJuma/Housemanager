<?php

namespace App\Modules\Users\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * "Your access changed" - sent to a staff member when their roles change
 * or a role they hold gains/loses permissions, so they know why new
 * tabs or actions appeared (or disappeared) in the web app and mobile
 * app. Stored in their notification inbox (web bell + app).
 */
class AccessChanged extends Notification
{
    use Queueable;

    /**
     * @param  array<int, string>  $granted  permission names now allowed
     * @param  array<int, string>  $revoked  permission names no longer allowed
     * @param  array<int, string>  $roles  their role names after the change
     */
    public function __construct(
        protected array $granted,
        protected array $revoked,
        protected array $roles,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        $locale = in_array($notifiable->locale ?? null, ['en', 'sw'], true) ? $notifiable->locale : app()->getLocale();
        $parts = [];

        if ($this->roles) {
            $parts[] = __('Your role: :roles.', ['roles' => implode(', ', $this->roles)], $locale);
        }
        if ($this->granted) {
            $parts[] = __('You can now: :list.', ['list' => $this->describe($this->granted)], $locale);
        }
        if ($this->revoked) {
            $parts[] = __('No longer allowed: :list.', ['list' => $this->describe($this->revoked)], $locale);
        }

        return [
            'kind' => 'access',
            'title' => __('Your access was updated', [], $locale),
            'message' => implode(' ', $parts),
            'granted' => array_values($this->granted),
            'revoked' => array_values($this->revoked),
            'roles' => array_values($this->roles),
        ];
    }

    /**
     * "tenants.create" -> "Tenants: create"; long lists are shortened.
     *
     * @param  array<int, string>  $permissions
     */
    protected function describe(array $permissions): string
    {
        $labels = collect($permissions)->sort()->map(function (string $permission) {
            [$area, $action] = array_pad(explode('.', $permission, 2), 2, '');

            return Str::headline($area).($action !== '' ? ': '.Str::lower(Str::headline($action)) : '');
        })->values();

        return $labels->count() > 8
            ? $labels->take(8)->implode(', ').' (+'.($labels->count() - 8).')'
            : $labels->implode(', ');
    }
}
