<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Subusers\UpdateSubuserTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Subusers\UpdatesSubusers;
use Pterodactyl\Jobs\RevokeSftpAccessJob;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionPermissionRegistry;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    Bus::fake([RevokeSftpAccessJob::class]);
    $this->app->make(ExtensionPermissionRegistry::class)->register('issue-probe', 'Control the probe.', ['view' => 'View the probe.']);
});

test('permissions updated without the http api meet the same rules', function (array $permissions, string $field): void {
    $server = $this->createServerModel();
    $subuser = Subuser::factory()->for($server)->for(User::factory()->create())->create(['permissions' => ['control.start']]);

    try {
        $this->app->make(UpdatesSubusers::class)->update($server, $subuser, $permissions);
        $this->fail('The subuser was updated.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect($subuser->refresh()->permissions)->toBe(['control.start']);
    Bus::assertNotDispatched(RevokeSftpAccessJob::class);
})->with([
    'no permissions' => [[], 'permissions'],
    'a permission that is not a string' => [['control.start', ['control.stop']], 'permissions.1'],
    'a permission nothing defines' => [['power.fake'], 'permissions.0'],
    'a permission of an extension that is not registered' => [['control.start', 'ext.unregistered.view'], 'permissions.1'],
]);

test('the permissions are replaced and the subuser loses its sftp sessions', function (): void {
    $server = $this->createServerModel();
    $subuser = Subuser::factory()->for($server)->for(User::factory()->create())->create(['permissions' => ['control.start']]);

    $this->app->make(UpdatesSubusers::class)->update($server, $subuser, ['control.stop', 'ext.issue-probe.view']);

    expect($subuser->refresh()->permissions)->toBe(['control.stop', 'ext.issue-probe.view']);
    Bus::assertDispatched(RevokeSftpAccessJob::class, fn (RevokeSftpAccessJob $job): bool => $job->user === $subuser->user->uuid && $job->target->is($server));
});

test('a change is logged on the server and the subuser\'s account, and unchanged permissions change nothing', function (): void {
    $server = $this->createServerModel();
    $subuser = Subuser::factory()->for($server)->for(User::factory()->create())->create(['permissions' => ['control.start', 'control.stop']]);
    $updates = $this->app->make(UpdatesSubusers::class);

    $updates->update($server, $subuser, ['control.stop', 'control.start']);

    Bus::assertNotDispatched(RevokeSftpAccessJob::class);
    expect($server->activity()->where('event', 'server:subuser.update')->exists())->toBeFalse();

    $updates->update($server, $subuser, ['control.stop']);

    expect($this->assertActivityOn('server:subuser.update', $server, $subuser->user)->properties->only(['old', 'new'])->all())
        ->toBe(['old' => ['control.start', 'control.stop'], 'new' => ['control.stop']]);
});

test('a permission the subuser already holds can be kept once nothing defines it', function (): void {
    $server = $this->createServerModel();
    $subuser = Subuser::factory()->for($server)->for(User::factory()->create())->create(['permissions' => ['control.start', 'ext.disabled.view']]);
    $subusers = $this->app->make(UpdatesSubusers::class);

    $subusers->update($server, $subuser, ['control.stop', 'ext.disabled.view']);
    expect($subuser->refresh()->permissions)->toBe(['control.stop', 'ext.disabled.view']);

    expect(fn () => $subusers->update($server, $subuser, ['control.stop', 'ext.disabled.view', 'ext.other.view']))->toThrow(ValidationException::class);
    expect($subuser->refresh()->permissions)->toBe(['control.stop', 'ext.disabled.view']);
});
