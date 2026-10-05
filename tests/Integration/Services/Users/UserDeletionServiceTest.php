<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Users\UserDeletionServiceTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Pterodactyl\Contracts\Users\DeletesUsers;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Jobs\RevokeSftpAccessJob;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);
beforeEach(function () {
    Bus::fake([RevokeSftpAccessJob::class]);
});
test('exception returned if user assigned to servers', function () {
    $server = $this->createServerModel();
    $this->expectException(DisplayException::class);
    $this->expectExceptionMessage(__('admin/user.exceptions.user_has_servers'));
    $this->app->make(DeletesUsers::class)->delete($server->user);
    $this->assertModelExists($server->user);
    Bus::assertNotDispatched(RevokeSftpAccessJob::class);
});
test('user is deleted', function () {
    $user = User::factory()->create();
    $this->app->make(DeletesUsers::class)->delete($user);
    $this->assertModelMissing($user);
    Bus::assertNotDispatched(RevokeSftpAccessJob::class);
});
test('user is deleted and access revoked', function () {
    $user = User::factory()->create();
    $server1 = $this->createServerModel();
    $server2 = $this->createServerModel(['node_id' => $server1->node_id]);
    Subuser::factory()->for($server1)->for($user)->create();
    Subuser::factory()->for($server2)->for($user)->create();
    $this->app->make(DeletesUsers::class)->delete($user);
    $this->assertModelMissing($user);
    Bus::assertDispatchedTimes(RevokeSftpAccessJob::class);
    Bus::assertDispatched(fn (RevokeSftpAccessJob $job) => $job->user === $user->uuid && $job->target->is($server1->node));
});
