<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\Schedule\GetServerSchedulesTest;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Task;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

uses(ClientApiIntegrationTestCase::class);
/**
 * Cleanup after tests run.
 */
afterEach(function (): void {
    Task::query()->forceDelete();
    Schedule::query()->forceDelete();
});
dataset('permissionsDataProvider', fn (): array => [[[], false], [[], true], [[Permissions::ScheduleRead->value], false], [[Permissions::ScheduleRead->value], true]]);
test('server schedules are returned', function (array $permissions, bool $individual): void {
    [$user, $server] = $this->generateTestAccount($permissions);
    /** @var Schedule $schedule */
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    /** @var Task $task */
    $task = Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 1, 'time_offset' => 0]);
    $response = $this->actingAs($user)->getJson($individual ? "/api/client/servers/{$server->uuid}/schedules/{$schedule->id}" : "/api/client/servers/{$server->uuid}/schedules")->assertOk();
    $prefix = $individual ? '' : 'data.0.';
    if (! $individual) {
        $response->assertJsonCount(1, 'data');
    }

    $response->assertJsonCount(1, $prefix.'attributes.relationships.tasks.data');
    $response->assertJsonPath($prefix.'object', Schedule::RESOURCE_NAME);
    $response->assertJsonPath($prefix.'attributes.relationships.tasks.data.0.object', Task::RESOURCE_NAME);

    expect(collect($response->json($prefix.'attributes'))->except('relationships')->all())->toBe([
        'id' => $schedule->id,
        'name' => $schedule->name,
        'cron' => [
            'day_of_week' => $schedule->cron_day_of_week,
            'day_of_month' => $schedule->cron_day_of_month,
            'month' => $schedule->cron_month,
            'hour' => $schedule->cron_hour,
            'minute' => $schedule->cron_minute,
        ],
        'is_active' => $schedule->is_active,
        'is_processing' => $schedule->is_processing,
        'only_when_online' => $schedule->only_when_online,
        'last_run_at' => $schedule->last_run_at?->toAtomString(),
        'next_run_at' => $schedule->next_run_at?->toAtomString(),
        'created_at' => $schedule->created_at->toAtomString(),
        'updated_at' => $schedule->updated_at->toAtomString(),
    ]);
    expect($response->json($prefix.'attributes.relationships.tasks.data.0.attributes'))->toBe([
        'id' => $task->id,
        'sequence_id' => $task->sequence_id,
        'action' => $task->action,
        'payload' => $task->payload,
        'time_offset' => $task->time_offset,
        'is_queued' => $task->is_queued,
        'continue_on_failure' => $task->continue_on_failure,
        'created_at' => $task->created_at->toAtomString(),
        'updated_at' => $task->updated_at->toAtomString(),
    ]);
})->with('permissionsDataProvider');
test('schedule belonging to another server cannot be viewed', function (): void {
    [$user, $server] = $this->generateTestAccount();
    $server2 = $this->createServerModel(['owner_id' => $user->id]);
    $schedule = Schedule::factory()->create(['server_id' => $server2->id]);
    $this->actingAs($user)->getJson("/api/client/servers/{$server->uuid}/schedules/{$schedule->id}")->assertNotFound();
});
test('user without permission cannot view schedules', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::WebsocketConnect->value]);
    $this->actingAs($user)->getJson("/api/client/servers/{$server->uuid}/schedules")->assertForbidden();
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $this->actingAs($user)->getJson("/api/client/servers/{$server->uuid}/schedules/{$schedule->id}")->assertForbidden();
});
