<?php

namespace Pterodactyl\Tests\Integration\Services\Users;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Subuser;
use Illuminate\Support\Facades\Bus;
use Pterodactyl\Jobs\RevokeSftpAccessJob;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Users\UserDeletionService;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

class UserDeletionServiceTest extends IntegrationTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Bus::fake([RevokeSftpAccessJob::class]);
    }

    public function testExceptionReturnedIfUserAssignedToServers(): void
    {
        $server = $this->createServerModel();

        $this->expectException(DisplayException::class);
        $this->expectExceptionMessage(__('admin/user.exceptions.user_has_servers'));

        $this->app->make(UserDeletionService::class)->handle($server->user);

        $this->assertModelExists($server->user);

        Bus::assertNotDispatched(RevokeSftpAccessJob::class);
    }

    public function testUserIsDeleted(): void
    {
        $user = User::factory()->create();

        $this->app->make(UserDeletionService::class)->handle($user);

        $this->assertModelMissing($user);

        Bus::assertNotDispatched(RevokeSftpAccessJob::class);
    }

    public function testUserIsDeletedAndAccessRevoked(): void
    {
        $user = User::factory()->create();

        $server1 = $this->createServerModel();
        $server2 = $this->createServerModel(['node_id' => $server1->node_id]);

        Subuser::factory()->for($server1)->for($user)->create();
        Subuser::factory()->for($server2)->for($user)->create();

        $this->app->make(UserDeletionService::class)->handle($user);

        $this->assertModelMissing($user);

        Bus::assertDispatchedTimes(RevokeSftpAccessJob::class);
        Bus::assertDispatched(fn (RevokeSftpAccessJob $job) => $job->user === $user->uuid && $job->target->is($server1->node));
    }
}
