<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Extensions\DynamicDatabaseConnectionTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

afterEach(function (): void {
    DB::purge('dynamic');
});

test('setting a new host replaces the already resolved connection', function (): void {
    $first = DatabaseHost::factory()->create(['port' => 3306, 'username' => 'first']);
    $second = DatabaseHost::factory()->create(['port' => 3307, 'username' => 'second']);
    $dynamic = new DynamicDatabaseConnection();

    $dynamic->set('dynamic', $first);

    $resolved = DB::connection('dynamic');
    expect($resolved->getConfig('host'))->toBe($first->host);

    $dynamic->set('dynamic', $second->id, 'panel');
    $connection = DB::connection('dynamic');

    expect($connection)->not->toBe($resolved)
        ->and($connection->getConfig('host'))->toBe($second->host)
        ->and($connection->getConfig('port'))->toBe(3307)
        ->and($connection->getConfig('username'))->toBe('second')
        ->and($connection->getConfig('database'))->toBe('panel');
});
