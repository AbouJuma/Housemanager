<?php

namespace Tests\Feature\Core;

use App\Core\Tenancy\PermissionsTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PermissionsTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_switches_the_team_id_for_the_duration_of_the_callback(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId(1);

        $seenInsideCallback = null;

        PermissionsTeam::runAs(42, function () use (&$seenInsideCallback, $registrar): void {
            $seenInsideCallback = $registrar->getPermissionsTeamId();
        });

        $this->assertSame(42, $seenInsideCallback);
    }

    public function test_it_restores_the_original_team_id_afterward(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId(7);

        PermissionsTeam::runAs(99, fn () => null);

        $this->assertSame(7, $registrar->getPermissionsTeamId());
    }

    public function test_it_restores_the_original_team_id_even_if_the_callback_throws(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId(7);

        try {
            PermissionsTeam::runAs(99, function (): void {
                throw new \RuntimeException('boom');
            });
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertSame(7, $registrar->getPermissionsTeamId());
    }

    public function test_it_returns_the_callbacks_value(): void
    {
        $result = PermissionsTeam::runAs(1, fn () => 'hello');

        $this->assertSame('hello', $result);
    }
}
