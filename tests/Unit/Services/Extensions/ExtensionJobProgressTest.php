<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Extensions\ExtensionJobProgressTest;

use InvalidArgumentException;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionJobProgress;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);

test('snapshots are namespaced monotonic terminal and available to a new worker', function (): void {
    $user = User::factory()->make(['uuid' => 'test-user']);
    $progress = new ExtensionJobProgress;
    $started = $progress->begin('probe', $user);
    $updated = $progress->update('probe', $started->id, 40, 'Working');
    expect((new ExtensionJobProgress)->find('probe', $started->id))->toEqual($updated);
    expect($updated->sequence)->toBe(2);
    expect($progress->find('other', $started->id))->toBeNull();
    expect(fn () => $progress->update('probe', $started->id, 20))->toThrow(InvalidArgumentException::class, 'backwards');
    $finished = $progress->update('probe', $started->id, 40, 'Done', 'completed');
    expect($finished->percent)->toBe(100);
    expect(fn () => $progress->update('probe', $started->id, 100))->toThrow(InvalidArgumentException::class, 'cannot be updated');
    $this->travel(61)->minutes();
    expect($progress->find('probe', $started->id))->toBeNull();
});

test('progress rejects invalid updates and permissions outside its namespace', function (): void {
    $user = User::factory()->make(['uuid' => 'test-user']);
    $server = Server::factory()->make(['uuid' => 'test-server']);
    $progress = new ExtensionJobProgress;
    expect(fn () => $progress->begin('probe', $user, $server, 'ext.other.view'))->toThrow(InvalidArgumentException::class);
    expect(fn () => $progress->begin('probe', $user, permission: 'ext.probe.view'))->toThrow(InvalidArgumentException::class);
    $started = $progress->begin('probe', $user, $server, 'ext.probe.view');
    expect(fn () => $progress->update('probe', $started->id, 101))->toThrow(InvalidArgumentException::class);
    expect(fn () => $progress->update('probe', $started->id, 10, str_repeat('x', 1001)))->toThrow(InvalidArgumentException::class);
    $failed = $progress->update('probe', $started->id, 10, 'Failed', 'failed');
    expect($failed->status)->toBe('failed');
});
