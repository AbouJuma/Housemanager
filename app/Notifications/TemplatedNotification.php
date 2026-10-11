<?php

namespace App\Notifications;

use App\Models\MessageTemplate;
use Illuminate\Notifications\Notification;

/**
 * Base for every "render a MessageTemplate and send it" notification
 * (tenant/landlord/buyer welcome messages, payment confirmations, the
 * lease-created landlord notice) - via()/toArray()/toCompanySms()/
 * toCompanyMail() are identical across all of them; only the template
 * `type` and the placeholder `$data` differ per subclass. Template
 * lookup is shared-then-company, matching the pre-consolidation
 * behavior every ad-hoc sender in the app used to hand-roll.
 *
 * toCompanySms()/toCompanyMail() return empty when no active template
 * exists for the type - CompanySmsChannel/CompanyMailChannel treat an
 * empty message as "don't send", not an error, so a company that never
 * configured this template type just doesn't get this message, exactly
 * like before.
 */
abstract class TemplatedNotification extends Notification
{
    /**
     * @param  array<string, mixed>  $data  placeholders for the template
     */
    public function __construct(protected string $type, protected array $data)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database', 'company-mail', 'company-sms'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'title' => $this->title(),
            'message' => $this->summary($notifiable),
        ];
    }

    public function toCompanySms(mixed $notifiable): string
    {
        return $this->template($notifiable)?->renderSmsTemplate($this->data) ?? '';
    }

    /**
     * @return array{subject: string, body: string}
     */
    public function toCompanyMail(mixed $notifiable): array
    {
        $template = $this->template($notifiable);

        return [
            'subject' => $template?->renderEmailSubject($this->data) ?? '',
            'body' => $template?->renderEmailTemplate($this->data) ?? '',
        ];
    }

    abstract protected function title(): string;

    abstract protected function summary(mixed $notifiable): string;

    /**
     * The shared template (company_id NULL - the one the Message
     * Templates screen edits) wins; a company-specific row is only a
     * fallback for a type with no shared one. `withoutGlobalScope('company')`
     * is essential: during a real logged-in request BelongsToCompany's
     * scope adds `company_id = <current company>`, which turned the
     * shared lookup into `company_id IS NULL AND company_id = 1` - always
     * empty - so edited shared wording was never used, stale per-company
     * copies were picked instead, and any type without such a copy
     * silently sent nothing (see MessageTemplate::usage()).
     */
    protected function template(mixed $notifiable): ?MessageTemplate
    {
        $active = fn () => MessageTemplate::withoutGlobalScope('company')
            ->where('type', $this->type)
            ->where('is_active', true);

        return $active()->whereNull('company_id')->first()
            ?? $active()->where('company_id', $notifiable->company_id)->first();
    }
}
