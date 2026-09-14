<?php

namespace App\Modules\Sms\Contracts;

use App\Modules\Integrations\Models\Integration;

/**
 * Implemented by each real SMS gateway driver (App\Modules\Sms\Drivers).
 * Unlike Storage/Email, there's no shared native transport to lean on
 * here - every provider is a genuinely different HTTP API - so this
 * interface is the thin, uniform seam App\Modules\Sms\Services\
 * SmsManager and any future caller (Notifications, Module 12) send
 * through, regardless of which gateway a company has configured.
 */
interface SmsProviderInterface
{
    /**
     * @return array{success: bool, message: string, message_id?: string|null}
     */
    public function send(Integration $integration, string $to, string $message): array;
}
