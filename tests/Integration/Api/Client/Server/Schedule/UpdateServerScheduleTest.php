<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\Schedule\UpdateServerScheduleTest;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Helpers\Utilities;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

uses(ClientApiIntegrationTestCase::class);
beforeEach(function (): void {
    $this->updateData = ['name' => 'Updated Schedule Name', 'minute' => '5', 'hour' => '*', 'day_of_week' => '*', 'month' => '*', 'day_of_month' => '*', 'is_active' => false];
});
dataset('permissionsDataProvider', fn (): array => [[[]], [[Permissions::ScheduleUpdate->value]]]);
test('schedule can be updated', function (array $permissions): void {
    [$user, $server] = $this->generateTestAccount($permissions);
    /** @var Schedule $schedule */
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $expected = Utilities::getScheduleNextRunDate('5', '*', '*', '*', '*');
    $response = $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/schedules/{$schedule->id}", $this->updateData);
    $schedule = $schedule->refresh();
    $response->assertOk();
    expect($schedule->name)->toBe('Updated Schedule Name');
    expect($schedule->is_active)->toBeFalse();
    expect(collect($response->json('attributes'))->except('relationships')->all())->toBe([
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
    expect($schedule->next_run_at->toAtomString())->toBe($expected->toAtomString());
})->with('permissionsDataProvider');
test('error is returned if schedule does not belong to server', function (): void {
    [$user, $server] = $this->generateTestAccount();
    $server2 = $this->createServerModel(['owner_id' => $user->id]);
    $schedule = Schedule::factory()->create(['server_id' => $server2->id]);
    $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/schedules/{$schedule->id}")->assertNotFound();
});
test('error is returned if subuser does not have permission to modify schedule', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::ScheduleCreate->value]);
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/schedules/{$schedule->id}")->assertForbidden();
});
test('schedule is processing is set to false when active state changes', function (): void {
    [$user, $server] = $this->generateTestAccount();
    /** @var Schedule $schedule */
    $schedule = Schedule::factory()->create(['server_id' => $server->id, 'is_active' => true, 'is_processing' => true]);
    expect($schedule->is_active)->toBeTrue();
    expect($schedule->is_processing)->toBeTrue();

    $response = $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/schedules/{$schedule->id}", $this->updateData);
    $schedule = $schedule->refresh();
    $response->assertOk();
    expect($schedule->is_active)->toBeFalse();
    expect($schedule->is_processing)->toBeFalse();
});
