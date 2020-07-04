<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server\Schedule;

use Pterodactyl\Models\Schedule;
use Pterodactyl\Helpers\Utilities;
use Pterodactyl\Models\Permission;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class UpdateServerScheduleTest extends ClientApiIntegrationTestCase
{
    /**
     * Test that a schedule can be updated.
     *
     * @param array $permissions
     * @dataProvider permissionsDataProvider
     */
    public function testScheduleCanBeUpdated($permissions)
    {
        [$user, $server] = $this->generateTestAccount($permissions);

        /** @var \Pterodactyl\Models\Schedule $schedule */
        $schedule = factory(Schedule::class)->create(['server_id' => $server->id]);
        $expected = Utilities::getScheduleNextRunDate('5', '*', '*', '*');

        $response = $this->actingAs($user)
            ->postJson("/api/client/servers/{$server->uuid}/schedules/{$schedule->id}", [
                'name' => 'Updated Schedule Name',
                'minute' => '5',
                'hour' => '*',
                'day_of_week' => '*',
                'day_of_month' => '*',
                'is_active' => false,
            ]);

        $schedule = $schedule->refresh();

        $response->assertOk();
        $this->assertSame('Updated Schedule Name', $schedule->name);
        $this->assertFalse($schedule->is_active);
        $this->assertJsonTransformedWith($response->json('attributes'), $schedule);

        $this->assertSame($expected->toIso8601String(), $schedule->next_run_at->toIso8601String());
    }

    /**
     * Test that an error is returned if the schedule exists but does not belong to this
     * specific server instance.
     */
    public function testErrorIsReturnedIfScheduleDoesNotBelongToServer()
    {
        [$user, $server] = $this->generateTestAccount();
        [, $server2] = $this->generateTestAccount(['user_id' => $user->id]);

        $schedule = factory(Schedule::class)->create(['server_id' => $server2->id]);

        $this->actingAs($user)
            ->postJson("/api/client/servers/{$server->uuid}/schedules/{$schedule->id}")
            ->assertNotFound();
    }

    /**
     * Test that an error is returned if the subuser does not have permission to modify a
     * server schedule.
     */
    public function testErrorIsReturnedIfSubuserDoesNotHavePermissionToModifySchedule()
    {
        [$user, $server] = $this->generateTestAccount([Permission::ACTION_SCHEDULE_CREATE]);

        $schedule = factory(Schedule::class)->create(['server_id' => $server->id]);

        $this->actingAs($user)
            ->postJson("/api/client/servers/{$server->uuid}/schedules/{$schedule->id}")
            ->assertForbidden();
    }

    /**
     * @return array
     */
    public function permissionsDataProvider(): array
    {
        return [[[]], [[Permission::ACTION_SCHEDULE_UPDATE]]];
    }
}
