<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Servers\SuspensionServiceTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Pterodactyl\Contracts\Servers\TogglesServerSuspension;
use Pterodactyl\Events\Server\OperationCompleted;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Models\Server;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonServer;

use function pterodactylTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);
/**
 * Setup test instance.
 */
beforeEach(function () {
    $this->repository = new FakeDaemonServer;
});
test('server is suspended and unsuspended', function () {
    $server = $this->createServerModel();
    getService()->toggle($server);
    expect($server->refresh()->isSuspended())->toBeTrue();
    getService()->toggle($server, TogglesServerSuspension::ACTION_UNSUSPEND);
    expect($server->refresh()->isSuspended())->toBeFalse();
    $this->repository->assertSyncedTimes(2);
});
test('no action is taken if suspension status is unchanged', function () {
    $server = $this->createServerModel();
    getService()->toggle($server, TogglesServerSuspension::ACTION_UNSUSPEND);
    $server->refresh();
    expect($server->isSuspended())->toBeFalse();
    $server->update(['status' => Server::STATUS_SUSPENDED]);
    getService()->toggle($server);
    $server->refresh();
    expect($server->isSuspended())->toBeTrue();
    $this->repository->assertNothingHappened();
});
test('exception is thrown if invalid actions are passed', function () {
    $server = $this->createServerModel();
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Unsupported suspension action [foo].');
    getService()->toggle($server, 'foo');
});
function getService(): TogglesServerSuspension
{
    return (function () {
        return $this->app->make(TogglesServerSuspension::class);
    })->call(pterodactylTestCase());
}

test('accepted suspension changes are reported to operation listeners', function () {
    Event::fake([OperationCompleted::class]);
    $server = $this->createServerModel();

    getService()->toggle($server);
    getService()->toggle($server);
    getService()->toggle($server, TogglesServerSuspension::ACTION_UNSUSPEND);

    $operations = Event::dispatched(OperationCompleted::class)->map(fn (array $arguments): array => [$arguments[0]->operation, $arguments[0]->successful, $arguments[0]->serverUuid, $arguments[0]->resourceUuid]);
    expect($operations->all())->toBe([
        ['suspend', true, $server->uuid, $server->uuid],
        ['unsuspend', true, $server->uuid, $server->uuid],
    ]);
});

test('a suspension Wings rejects is reverted and not reported', function () {
    Event::fake([OperationCompleted::class]);
    $server = $this->createServerModel();
    $this->repository->throwable = new DaemonConnectionException(Http::failedRequest([], 500));

    expect(fn () => getService()->toggle($server))->toThrow(DaemonConnectionException::class);

    expect($server->refresh()->isSuspended())->toBeFalse();
    Event::assertNotDispatched(OperationCompleted::class);
});
