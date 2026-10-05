<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Integration\Api\Admin;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Traits\Http\IntegrationJsonRequestAssertions;
use Pterodactyl\Tests\Traits\Integration\CreatesTestModels;

abstract class AdminApiIntegrationTestCase extends IntegrationTestCase
{
    use CreatesTestModels;
    use DatabaseTransactions;
    use IntegrationJsonRequestAssertions;

    private User $user;

    /** Authenticated via the session guard and gated on the root administrator flag. */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAdminUser();

        $this->actingAs($this->user);
    }

    /** Return the root administrator the test is currently acting as. */
    public function getAdminUser(): User
    {
        return $this->user;
    }

    /** Create a root administrator user. */
    protected function createAdminUser(): User
    {
        return User::factory()->create([
            'root_admin' => true,
        ]);
    }

    /** Switch to a non-administrative account to assert the admin API rejects it. */
    protected function actingAsNonAdmin(): User
    {
        $user = User::factory()->create([
            'root_admin' => false,
        ]);

        $this->actingAs($user);

        return $user;
    }
}
