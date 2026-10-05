<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\Schedule\CreateServerScheduleTest;

use Illuminate\Http\Response;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

uses(ClientApiIntegrationTestCase::class);
dataset('permissionsDataProvider', fn (): array => [[[]], [[Permissions::ScheduleCreate->value]]]);
test('schedule can be created for server', function (array $permissions): void {
    [$user, $server] = $this->generateTestAccount($permissions);
    $response = $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/schedules", ['name' => 'Test Schedule', 'is_active' => false, 'minute' => '0', 'hour' => '*/2', 'day_of_week' => '2', 'month' => '1', 'day_of_month' => '*']);
    $response->assertOk();

    expect($id = $response->json('attributes.id'))->not->toBeNull();
    /** @var Schedule $schedule */
    $schedule = Schedule::query()->findOrFail($id);
    expect($schedule->is_active)->toBeFalse();
    expect($schedule->is_processing)->toBeFalse();
    expect($schedule->cron_minute)->toBe('0');
    expect($schedule->cron_hour)->toBe('*/2');
    expect($schedule->cron_day_of_week)->toBe('2');
    expect($schedule->cron_month)->toBe('1');
    expect($schedule->cron_day_of_month)->toBe('*');
    expect($schedule->name)->toBe('Test Schedule');
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
    $response->assertJsonCount(0, 'attributes.relationships.tasks.data');
})->with('permissionsDataProvider');
test('schedule validation rules', function (): void {
    [$user, $server] = $this->generateTestAccount();
    $response = $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/schedules", []);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    foreach (['name', 'minute', 'hour', 'day_of_month', 'month', 'day_of_week'] as $i => $field) {
        $response->assertJsonPath("errors.{$i}.code", 'ValidationException');
        $response->assertJsonPath("errors.{$i}.meta.rule", 'required');
        $response->assertJsonPath("errors.{$i}.meta.source_field", $field);
    }

    $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/schedules", ['name' => 'Testing', 'is_active' => 'no', 'minute' => '*', 'hour' => '*', 'day_of_month' => '*', 'month' => '*', 'day_of_week' => '*'])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonPath('errors.0.meta.rule', 'boolean');
});
test('subuser cannot create schedule without permissions', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::ScheduleUpdate->value]);
    $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/schedules", [])->assertForbidden();
});
