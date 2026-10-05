<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Console\SchedulerTest;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Console\Scheduler;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Setting;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonConfiguration;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

test('telemetry is scheduled and sent only when enabled', function (bool $enabled) {
    config()->set('pterodactyl.telemetry.enabled', $enabled);
    $uuid = '00000000-0000-4000-8000-000000000001';
    Setting::put('app:telemetry:uuid', $uuid);
    $node = Node::factory()->for(Location::factory())->create();
    $daemon = new FakeDaemonConfiguration;
    Http::fake(['https://telemetry.pterodactyl.io' => Http::response([], 200)]);

    $schedule = new Schedule;
    $this->app->make(Scheduler::class)($schedule);
    $events = array_values(array_filter(
        $schedule->events(),
        fn ($event): bool => $event->description === 'Collect Telemetry',
    ));

    expect($events)->toHaveCount($enabled ? 1 : 0);

    if (! $enabled) {
        Http::assertNothingSent();
        $daemon->assertNothingHappened();

        return;
    }

    expect($events[0]->expression)->toBe('1 0 * * *');
    expect($events[0]->withoutOverlapping)->toBeTrue();
    $events[0]->run($this->app);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://telemetry.pterodactyl.io'
        && $request['id'] === $uuid
        && in_array($node->uuid, array_column($request['nodes'], 'id'), true));
    Http::assertSentCount(2);
    $daemon->assertSystemInformationFetched();
})->with([true, false]);
