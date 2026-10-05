<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Console\Commands\Server\BulkPowerActionCommandTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Pterodactyl\Models\Server;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonPower;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

test('bulk power only targets available servers when server and node filters are combined', function (): void {
    $selectedServer = $this->createServerModel();
    $nodeServer = $this->createServerModel();
    $this->createServerModel(['node_id' => $nodeServer->node_id, 'status' => Server::STATUS_SUSPENDED]);
    $this->createServerModel(['node_id' => $nodeServer->node_id, 'status' => Server::STATUS_INSTALLING]);
    $this->createServerModel();
    $power = new FakeDaemonPower;

    $this->artisan('p:server:bulk-power', [
        'action' => 'start',
        '--servers' => (string) $selectedServer->id,
        '--nodes' => (string) $nodeServer->node_id,
        '--no-interaction' => true,
    ])->assertSuccessful();

    $power->assertSentTimes('start', 2);
    $power->assertSentTo('start', $selectedServer->uuid);
    $power->assertSentTo('start', $nodeServer->uuid);
});
