<?php

namespace Tests\Feature\Core;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_switcher_changes_the_language_and_remembers_it_on_the_account(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'name' => 'Amina Mushi']);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Dashboard');

        $this->actingAs($user)->from(route('dashboard'))->post(route('locale.switch', 'sw'))->assertRedirect(route('dashboard'));
        $this->assertSame('sw', $user->fresh()->locale);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('<html lang="sw">', false)
            ->assertSee('Dashibodi')
            ->assertSee('Toka'); // Log Out
    }

    public function test_the_company_default_language_applies_until_someone_chooses(): void
    {
        $company = Company::factory()->create(['locale' => 'sw']);
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->get(route('dashboard'))->assertSee('<html lang="sw">', false);
    }

    public function test_sign_in_pages_and_validation_errors_follow_the_chosen_language(): void
    {
        $this->post(route('locale.switch', 'sw'));

        $this->get(route('login'))->assertOk()->assertSee('Nenosiri')->assertSee('Ingia');

        $this->from(route('login'))->post(route('login'), ['email' => '', 'password' => ''])
            ->assertSessionHasErrors(['email' => 'Barua pepe inahitajika.']);
    }

    public function test_the_mobile_api_answers_in_the_language_the_app_asks_for(): void
    {
        $this->withHeader('Accept-Language', 'sw')
            ->postJson('/api/v1/portal/tenant/login', ['email' => 'nobody', 'password' => 'wrong'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Taarifa ulizoweka hazilingani na kumbukumbu zetu.');

        $this->withHeader('Accept-Language', 'en')
            ->postJson('/api/v1/portal/tenant/login', ['email' => 'nobody', 'password' => 'wrong'])
            ->assertJsonPath('errors.email.0', 'These credentials do not match our records.');
    }

    public function test_unsupported_languages_are_refused(): void
    {
        $this->post(route('locale.switch', 'fr'))->assertNotFound();
    }

    public function test_swahili_dictionary_entries_are_all_strings(): void
    {
        $dictionary = json_decode(file_get_contents(lang_path('sw.json')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertGreaterThan(400, count($dictionary));
        foreach ($dictionary as $english => $swahili) {
            $this->assertIsString($swahili, "Bad translation for [{$english}]");
            $this->assertNotSame('', trim($swahili), "Empty translation for [{$english}]");
            // Placeholders must survive translation.
            preg_match_all('/:[a-z_]+/', $english, $placeholders);
            foreach ($placeholders[0] as $placeholder) {
                $this->assertStringContainsString($placeholder, $swahili, "[{$english}] lost {$placeholder}");
            }
        }
    }
}
