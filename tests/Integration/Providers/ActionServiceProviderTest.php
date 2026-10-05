<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Providers\ActionServiceProviderTest;

use Illuminate\Support\Facades\File;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use ReflectionClass;

uses(IntegrationTestCase::class);

test('every action resolves through its contracts without sharing operation state', function () {
    $files = File::allFiles(app_path('Actions'));
    expect($files)->not->toBeEmpty();

    foreach ($files as $file) {
        $class = 'Pterodactyl\\Actions\\'.str_replace('/', '\\', $file->getRelativePathname());
        $class = mb_substr($class, 0, -4);
        $reflection = new ReflectionClass($class);
        $contracts = array_filter(
            $reflection->getInterfaceNames(),
            fn (string $contract): bool => str_starts_with($contract, 'Pterodactyl\\Contracts\\'),
        );

        expect($contracts)->not->toBeEmpty();

        foreach ($contracts as $contract) {
            expect($this->app->bound($contract))->toBeTrue();

            $operation = $this->app->make($contract);
            expect($operation)->toBeInstanceOf($class);
            expect($this->app->make($contract))->not->toBe($operation);
        }
    }
});
