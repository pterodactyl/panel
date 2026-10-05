<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Extensions\ServerOperationEventTest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Pterodactyl\Events\Server\OperationCompleted;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class);

test('operation listeners see committed snapshots and never see rolled back results', function (): void {
    $events = [];
    Event::listen(OperationCompleted::class, function (OperationCompleted $event) use (&$events): void {
        $events[] = $event;
    });
    $depth = DB::transactionLevel();
    try {
        DB::beginTransaction();
        Event::dispatch(new OperationCompleted('server-uuid', 'backup', true, 'backup-uuid'));
        expect($events)->toBeEmpty();
        DB::rollBack();
        expect($events)->toBeEmpty();
        DB::beginTransaction();
        Event::dispatch(new OperationCompleted('server-uuid', 'backup', false, 'backup-uuid'));
        expect($events)->toBeEmpty();
        DB::commit();
        expect($events)->toHaveCount(1);
        expect($events[0]->serverUuid)->toBe('server-uuid');
        expect($events[0]->successful)->toBeFalse();
        expect($events[0]->resourceUuid)->toBe('backup-uuid');
    } finally {
        while (DB::transactionLevel() > $depth) {
            DB::rollBack();
        }
        Event::forget(OperationCompleted::class);
    }
});
