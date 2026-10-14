<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Servers\SendServerPowerTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Pterodactyl\Contracts\Servers\SendsServerCommands;
use Pterodactyl\Contracts\Servers\SendsServerPower;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonCommand;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonPower;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

test('a power signal sent without the http api is logged on the server', function (): void {
    new FakeDaemonPower;
    $server = $this->createServerModel();

    $this->app->make(SendsServerPower::class)->send($server, 'restart');

    $this->assertActivityOn('server:power.restart', $server);
});

test('a command sent without the http api is logged on the server', function (): void {
    new FakeDaemonCommand;
    $server = $this->createServerModel();

    $this->app->make(SendsServerCommands::class)->send($server, 'say hi');

    expect($this->assertActivityOn('server:console.command', $server)->properties->get('command'))->toBe('say hi');
});
