<?php

namespace Tests\Feature\Notifications;

use App\Models\MessageTemplate;
use App\Models\User;
use Database\Seeders\MessageTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTemplateScreensTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_welcome_and_lease_created_are_separate_templates_with_their_own_fields(): void
    {
        (new MessageTemplateSeeder())->run();
        $owner = User::factory()->create();

        $welcome = MessageTemplate::whereNull('company_id')->where('type', 'tenant_welcome')->firstOrFail();
        $lease = MessageTemplate::whereNull('company_id')->where('type', 'lease_created_tenant')->firstOrFail();
        $this->assertNotSame($welcome->id, $lease->id);

        $welcomePage = $this->actingAs($owner)->get(route('message-templates.edit', $welcome->id))->assertOk();
        $welcomePage->assertSee('data-field="control_number"', false);
        $welcomePage->assertDontSee('data-field="rent_amount"', false);
        $welcomePage->assertDontSee('data-field="bank_name"', false);

        $leasePage = $this->actingAs($owner)->get(route('message-templates.edit', $lease->id))->assertOk();
        foreach (['rent_amount', 'start_date', 'end_date', 'unit_number', 'bank_name', 'deposit_amount'] as $field) {
            $leasePage->assertSee('data-field="'.$field.'"', false);
        }
    }

    public function test_the_index_and_create_screens_include_the_lease_created_template(): void
    {
        (new MessageTemplateSeeder())->run();
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('message-templates.index'))->assertOk()->assertSee('Lease Created (Tenant)');
        $this->actingAs($owner)->get(route('message-templates.create'))->assertOk()->assertSee('lease_created_tenant');
    }

    public function test_every_seeded_template_only_uses_its_own_listed_placeholders(): void
    {
        (new MessageTemplateSeeder())->run();

        foreach (MessageTemplate::whereNull('company_id')->get() as $template) {
            $allowed = MessageTemplate::placeholders($template->type);
            $text = $template->sms_template.' '.$template->email_subject.' '.$template->email_template;
            preg_match_all('/\{([a-z_]+)\}/', $text, $found);

            foreach (array_unique($found[1]) as $placeholder) {
                $this->assertContains($placeholder, $allowed, "Seeded [{$template->type}] uses {{$placeholder}} which the sender never fills.");
            }
        }
    }
}
