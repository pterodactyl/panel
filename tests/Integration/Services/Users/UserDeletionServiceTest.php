<?php

namespace Pterodactyl\Tests\Integration\Services\Users;

use Pterodactyl\Models\User;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Users\UserDeletionService;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

class UserDeletionServiceTest extends IntegrationTestCase
{
    public function testExceptionReturnedIfUserAssignedToServers(): void
    {
        $server = $this->createServerModel();

        $this->expectException(DisplayException::class);
        $this->expectExceptionMessage(__('admin/user.exceptions.user_has_servers'));

        $this->app->make(UserDeletionService::class)->handle($server->user);

        $this->assertModelExists($server->user);
    }

    public function testUserIsDeleted(): void
    {
        $user = User::factory()->create();

        $this->app->make(UserDeletionService::class)->handle($user);

        $this->assertModelMissing($user);
    }
}
