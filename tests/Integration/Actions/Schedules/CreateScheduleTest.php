<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Schedules\CreateScheduleTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Pterodactyl\Contracts\Schedules\CreatesSchedules;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

test('the contract accepts the flags the boolean rule allows, as an extension may pass them', function (): void {
    $schedule = $this->app->make(CreatesSchedules::class)->create($this->createServerModel(), [
        'name' => 'Nightly',
        'minute' => '0',
        'hour' => '3',
        'day_of_month' => '*',
        'month' => '*',
        'day_of_week' => '*',
        'is_active' => 1,
        'only_when_online' => '0',
    ]);

    expect($schedule->is_active)->toBeTrue()
        ->and($schedule->only_when_online)->toBeFalse();
});
