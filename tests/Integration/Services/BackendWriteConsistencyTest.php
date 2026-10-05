<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\BackendWriteConsistencyTest;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Pterodactyl\Contracts\Schedules\DeletesScheduleTasks;
use Pterodactyl\Contracts\Schedules\UpdatesScheduleTasks;
use Pterodactyl\Contracts\Subusers\CreatesSubusers;
use Pterodactyl\Contracts\Subusers\DeletesSubusers;
use Pterodactyl\Contracts\Subusers\UpdatesSubusers;
use Pterodactyl\Contracts\Transfers\InitiatesTransfers;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Http\Server\ServerStateConflictException;
use Pterodactyl\Exceptions\Service\Subuser\ServerSubuserExistsException;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\ServerTransfer;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\Task;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonTransfer;
use RuntimeException;

uses(ClientApiIntegrationTestCase::class);

beforeEach(function (): void {
    config()->set('database.connections.commit-publication-tests', ['driver' => 'sqlite', 'database' => ':memory:']);
    config()->set('queue.default', 'commit-publication-tests');
    config()->set('queue.connections.commit-publication-tests', [
        'driver' => 'database', 'connection' => 'commit-publication-tests', 'table' => 'jobs',
        'queue' => 'standard', 'retry_after' => 90, 'after_commit' => false,
    ]);
    Schema::connection('commit-publication-tests')->create('jobs', function (Blueprint $table): void {
        $table->id();
        $table->string('queue');
        $table->longText('payload');
        $table->unsignedTinyInteger('attempts');
        $table->unsignedInteger('reserved_at')->nullable();
        $table->unsignedInteger('available_at');
        $table->unsignedInteger('created_at');
    });
});

test('a transfer rejects an allocation claimed after request validation without reserving the rest', function (bool $claimPrimary): void {
    $server = $this->createServerModel();
    $other = $this->createServerModel();
    $node = Node::factory()->create(['location_id' => $server->node->location_id, 'memory' => 102400, 'disk' => 102400]);
    $primary = Allocation::factory()->for($node)->create(['server_id' => null]);
    $additional = Allocation::factory()->for($node)->create(['server_id' => null]);
    ($claimPrimary ? $primary : $additional)->update(['server_id' => $other->id]);
    $fake = new FakeDaemonTransfer;
    expect(fn () => $this->app->make(InitiatesTransfers::class)->initiate($server, $node, $primary->id, [$additional->id]))
        ->toThrow(DisplayException::class, 'The selected allocations are no longer available on the target node.');
    $this->assertDatabaseMissing('server_transfers', ['server_id' => $server->id]);
    expect($primary->refresh()->server_id)->toBe($claimPrimary ? $other->id : null);
    expect($additional->refresh()->server_id)->toBe($claimPrimary ? null : $other->id);

    $fake->assertNothingHappened();
})->with(['primary claimed' => true, 'additional claimed' => false]);

test('a stale server cannot start another pending transfer', function (): void {
    $server = $this->createServerModel();
    $server->load('transfer');

    $node = Node::factory()->create(['location_id' => $server->node->location_id, 'memory' => 102400, 'disk' => 102400]);
    $primary = Allocation::factory()->for($node)->create(['server_id' => null]);
    ServerTransfer::factory()->create(['server_id' => $server->id, 'old_node' => $server->node_id, 'new_node' => $node->id]);
    $fake = new FakeDaemonTransfer;
    expect(fn () => $this->app->make(InitiatesTransfers::class)->initiate($server, $node, $primary->id, []))
        ->toThrow(ServerStateConflictException::class);
    expect(ServerTransfer::query()->where('server_id', $server->id)->count())->toBe(1);
    expect($primary->refresh()->server_id)->toBeNull();

    $fake->assertNothingHappened();
});

test('reordering a stale task uses its latest position', function (): void {
    $server = $this->createServerModel();
    $schedule = Schedule::factory()->for($server)->create();
    $tasks = Task::factory()->for($schedule)->sequence(['sequence_id' => 1], ['sequence_id' => 2], ['sequence_id' => 3])->count(3)->create();
    $updates = $this->app->make(UpdatesScheduleTasks::class);
    $data = ['action' => 'command', 'payload' => 'say test', 'time_offset' => 0, 'continue_on_failure' => false, 'sequence_id' => 3];
    $updates->update($schedule, $tasks[0], $data);
    $updates->update($schedule, $tasks[1], $data);

    expect($tasks[0]->refresh()->sequence_id)->toBe(2);
    expect($tasks[1]->refresh()->sequence_id)->toBe(3);
    expect($tasks[2]->refresh()->sequence_id)->toBe(1);
});

test('deleting a stale task closes its latest sequence gap', function (): void {
    $server = $this->createServerModel();
    $schedule = Schedule::factory()->for($server)->create();
    $tasks = Task::factory()->for($schedule)->sequence(['sequence_id' => 1], ['sequence_id' => 2], ['sequence_id' => 3])->count(3)->create();
    $this->app->make(UpdatesScheduleTasks::class)->update($schedule, $tasks[0], [
        'action' => 'command', 'payload' => 'say test', 'time_offset' => 0, 'continue_on_failure' => false, 'sequence_id' => 3,
    ]);
    $this->app->make(DeletesScheduleTasks::class)->delete($schedule, $tasks[1]);
    expect($tasks[0]->refresh()->sequence_id)->toBe(2);
    expect($tasks[2]->refresh()->sequence_id)->toBe(1);
    $this->assertDatabaseMissing('tasks', ['id' => $tasks[1]->id]);
});

test('an existing grant is rejected without changing its permissions', function (): void {
    Notification::fake();
    $server = $this->createServerModel();
    $user = User::factory()->create();
    $creates = $this->app->make(CreatesSubusers::class);
    $subuser = $creates->create($server, $user->email, ['control.start']);
    expect(fn () => $creates->create($server, $user->email, ['control.stop']))->toThrow(ServerSubuserExistsException::class);
    expect($server->subusers()->where('user_id', $user->id)->count())->toBe(1);
    expect($subuser->refresh()->permissions)->toBe(['control.start']);
});

test('new account and subuser notifications are only published after the outer transaction commits', function (): void {
    $server = $this->createServerModel();
    $email = fake()->unique()->safeEmail();
    DB::transaction(function () use ($server, $email): void {
        $this->app->make(CreatesSubusers::class)->create($server, $email, ['control.start']);
        expect(DB::connection('commit-publication-tests')->table('jobs')->count())->toBe(0);
    });
    expect(DB::connection('commit-publication-tests')->table('jobs')->count())->toBe(2);
});

test('a rolled back invitation publishes no account or subuser notifications', function (): void {
    $server = $this->createServerModel();
    $email = fake()->unique()->safeEmail();
    expect(fn () => DB::transaction(function () use ($server, $email): void {
        $this->app->make(CreatesSubusers::class)->create($server, $email, ['control.start']);
        throw new RuntimeException('rollback invitation');
    }))->toThrow(RuntimeException::class, 'rollback invitation');
    expect(DB::connection('commit-publication-tests')->table('jobs')->count())->toBe(0);
    $this->assertDatabaseMissing('users', ['email' => $email]);
});

test('permission revocation waits for the outer transaction commit', function (): void {
    $server = $this->createServerModel();
    $subuser = Subuser::factory()->for($server)->for(User::factory()->create())->create(['permissions' => ['control.start']]);
    DB::connection('commit-publication-tests')->table('jobs')->delete();
    DB::transaction(function () use ($server, $subuser): void {
        $this->app->make(UpdatesSubusers::class)->update($server, $subuser, ['control.stop']);
        expect(DB::connection('commit-publication-tests')->table('jobs')->count())->toBe(0);
    });
    expect(DB::connection('commit-publication-tests')->table('jobs')->count())->toBe(1);
    expect($subuser->refresh()->permissions)->toBe(['control.stop']);
});

test('a rolled back permission update publishes no revocation', function (): void {
    $server = $this->createServerModel();
    $subuser = Subuser::factory()->for($server)->for(User::factory()->create())->create(['permissions' => ['control.start']]);
    DB::connection('commit-publication-tests')->table('jobs')->delete();
    expect(fn () => DB::transaction(function () use ($server, $subuser): void {
        $this->app->make(UpdatesSubusers::class)->update($server, $subuser, ['control.stop']);
        throw new RuntimeException('rollback permissions');
    }))->toThrow(RuntimeException::class, 'rollback permissions');
    expect(DB::connection('commit-publication-tests')->table('jobs')->count())->toBe(0);
    expect($subuser->refresh()->permissions)->toBe(['control.start']);

    $this->app->make(UpdatesSubusers::class)->update($server, $subuser, ['control.stop']);
    expect(DB::connection('commit-publication-tests')->table('jobs')->count())->toBe(1);
});

test('subuser removal publishes revocation and notification after commit', function (): void {
    $server = $this->createServerModel();
    $subuser = Subuser::factory()->for($server)->for(User::factory()->create())->create();
    DB::connection('commit-publication-tests')->table('jobs')->delete();
    DB::transaction(function () use ($server, $subuser): void {
        $this->app->make(DeletesSubusers::class)->delete($server, $subuser);
        expect(DB::connection('commit-publication-tests')->table('jobs')->count())->toBe(0);
    });
    expect(DB::connection('commit-publication-tests')->table('jobs')->count())->toBe(2);
    $this->assertDatabaseMissing('subusers', ['id' => $subuser->id]);
});

test('a rolled back removal publishes neither revocation nor notification', function (): void {
    $server = $this->createServerModel();
    $subuser = Subuser::factory()->for($server)->for(User::factory()->create())->create();
    DB::connection('commit-publication-tests')->table('jobs')->delete();
    expect(fn () => DB::transaction(function () use ($server, $subuser): void {
        $this->app->make(DeletesSubusers::class)->delete($server, $subuser);
        throw new RuntimeException('rollback removal');
    }))->toThrow(RuntimeException::class, 'rollback removal');
    expect(DB::connection('commit-publication-tests')->table('jobs')->count())->toBe(0);
    $this->assertDatabaseHas('subusers', ['id' => $subuser->id]);

    $this->app->make(DeletesSubusers::class)->delete($server, Subuser::query()->findOrFail($subuser->id));
    expect(DB::connection('commit-publication-tests')->table('jobs')->count())->toBe(2);
    $this->assertDatabaseMissing('subusers', ['id' => $subuser->id]);
});
