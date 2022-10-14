<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server\Schedule;

use Illuminate\Http\Response;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Permission;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class CreateServerScheduleTest extends ClientApiIntegrationTestCase
{
    /**
     * Test that a schedule can be created for the server.
     *
     * @dataProvider permissionsDataProvider
     */
    public function testScheduleCanBeCreatedForServer(array $permissions)
    {
        [$user, $server] = $this->generateTestAccount($permissions);

        $response = $this->actingAs($user)->postJson("/api/client/servers/$server->uuid/schedules", [
            'name' => 'Test Schedule',
            'is_active' => false,
            'minute' => '0',
            'hour' => '*/2',
            'day_of_week' => '2',
            'month' => '1',
            'day_of_month' => '*',
        ]);

        $response->assertOk();

        $this->assertNotNull($id = $response->json('attributes.id'));

        /** @var \Pterodactyl\Models\Schedule $schedule */
        $schedule = Schedule::query()->findOrFail($id);
        $this->assertFalse($schedule->is_active);
        $this->assertFalse($schedule->is_processing);
        $this->assertSame('0', $schedule->cron_minute);
        $this->assertSame('*/2', $schedule->cron_hour);
        $this->assertSame('2', $schedule->cron_day_of_week);
        $this->assertSame('1', $schedule->cron_month);
        $this->assertSame('*', $schedule->cron_day_of_month);
        $this->assertSame('Test Schedule', $schedule->name);

        $this->assertJsonTransformedWith($response->json('attributes'), $schedule);
        $response->assertJsonCount(0, 'attributes.relationships.tasks.data');
    }

    /**
     * Test that the validation rules for scheduling work as expected.
     */
    public function testScheduleValidationRules()
    {
        [$user, $server] = $this->generateTestAccount();

        $response = $this->actingAs($user)->postJson("/api/client/servers/$server->uuid/schedules", []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        foreach (['name', 'minute', 'hour', 'day_of_month', 'day_of_week'] as $i => $field) {
            $response->assertJsonPath("errors.$i.code", 'ValidationException');
            $response->assertJsonPath("errors.$i.meta.rule", 'required');
            $response->assertJsonPath("errors.$i.meta.source_field", $field);
        }

        $this->actingAs($user)
            ->postJson("/api/client/servers/$server->uuid/schedules", [
                'name' => 'Testing',
                'is_active' => 'no',
                'minute' => '*',
                'hour' => '*',
                'day_of_month' => '*',
                'month' => '*',
                'day_of_week' => '*',
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonPath('errors.0.meta.rule', 'boolean');
    }

    /**
     * Test that a subuser without required permissions cannot create a schedule.
     */
    public function testSubuserCannotCreateScheduleWithoutPermissions()
    {
        [$user, $server] = $this->generateTestAccount([Permission::ACTION_SCHEDULE_UPDATE]);

        $this->actingAs($user)
            ->postJson("/api/client/servers/$server->uuid/schedules", [])
            ->assertForbidden();
    }

    public function permissionsDataProvider(): array
    {
        return [[[]], [[Permission::ACTION_SCHEDULE_CREATE]]];
    }
}
