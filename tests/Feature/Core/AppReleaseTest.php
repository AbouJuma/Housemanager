<?php

namespace Tests\Feature\Core;

use App\Models\User;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The Android download under the sign-in pages: the super admin
 * publishes an APK and every login page offers the newest one.
 */
class AppReleaseTest extends TestCase
{
    use RefreshDatabase;

    protected string $storage;

    protected function setUp(): void
    {
        parent::setUp();

        // Keep test APKs out of the real storage folder.
        $this->storage = sys_get_temp_dir().'/app-release-test-'.uniqid();
        File::ensureDirectoryExists($this->storage.'/app');
        File::ensureDirectoryExists($this->storage.'/framework/views');
        $this->app->useStoragePath($this->storage);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storage);
        parent::tearDown();
    }

    protected function owner(): User
    {
        return User::factory()->create(['company_id' => null]);
    }

    public function test_no_download_is_shown_until_an_apk_is_published(): void
    {
        $this->get(route('login'))->assertOk()->assertDontSee(route('app.download'), false);
        $this->get(route('app.download'))->assertNotFound();
    }

    public function test_the_super_admin_publishes_a_version_and_every_login_page_offers_it(): void
    {
        $this->actingAs($this->owner())->get(route('app-releases.index'))->assertOk()->assertSee(__('Publish a new version'));

        $this->actingAs($this->owner())->post(route('app-releases.store'), [
            'version' => '1.0.2',
            'apk' => UploadedFile::fake()->create('app-release.apk', 2048, 'application/vnd.android.package-archive'),
        ])->assertRedirect(route('app-releases.index'))->assertSessionHasNoErrors();
        auth()->logout();

        foreach (['login', 'portal.login', 'landlord-portal.login'] as $page) {
            $this->get(route($page))->assertOk()
                ->assertSee(route('app.download'), false)
                ->assertSee('Download for Android')
                ->assertSee('1.0.2');
        }

        $download = $this->get(route('app.download'))->assertOk();
        $this->assertSame('application/vnd.android.package-archive', $download->headers->get('Content-Type'));
        $this->assertStringContainsString('HouseManagers-v1.0.2.apk', $download->headers->get('Content-Disposition'));
    }

    public function test_the_newest_upload_replaces_the_download_and_old_ones_are_pruned(): void
    {
        foreach (['1.0.1', '1.0.2', '1.0.3', '1.0.4'] as $version) {
            $this->actingAs($this->owner())->post(route('app-releases.store'), [
                'version' => $version,
                'apk' => UploadedFile::fake()->create("v{$version}.apk", 100),
            ]);
        }

        $this->get(route('app.download'))->assertOk()->assertDownload('HouseManagers-v1.0.4.apk');
        $this->assertCount(3, File::files($this->storage.'/app/releases'), 'keeps the newest three');
    }

    public function test_only_the_super_admin_can_publish_and_only_apk_files(): void
    {
        $owner = $this->owner();
        $staff = User::factory()->create(['company_id' => Company::factory()->create()->id]);
        $this->actingAs($staff)->get(route('app-releases.index'))->assertForbidden();
        $this->actingAs($staff)->post(route('app-releases.store'), ['version' => '9.9.9', 'apk' => UploadedFile::fake()->create('x.apk', 10)])->assertForbidden();

        $this->actingAs($owner)->post(route('app-releases.store'), ['version' => 'latest', 'apk' => UploadedFile::fake()->create('notes.pdf', 10)])
            ->assertSessionHasErrors(['version', 'apk']);
        $this->get(route('app.download'))->assertNotFound();
    }

    public function test_the_sms_test_route_is_closed_to_the_public(): void
    {
        $this->get('/test-sms')->assertRedirect(route('login'));
        $staff = User::factory()->create(['company_id' => Company::factory()->create()->id]);
        $this->actingAs($staff)->get('/test-sms')->assertNotFound();
    }
}
