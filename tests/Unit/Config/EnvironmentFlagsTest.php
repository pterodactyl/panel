<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Config\EnvironmentFlagsTest;

use Pterodactyl\Tests\TestCase;

uses(TestCase::class);

test('boolean settings read 1 and 0 from the environment as booleans', function (string $value, bool $expected): void {
    $keys = ['RECAPTCHA_ENABLED', 'PTERODACTYL_CLIENT_ALLOCATIONS_ENABLED', 'PTERODACTYL_TELEMETRY_ENABLED'];
    $previous = array_map(fn (string $key): mixed => $_SERVER[$key] ?? null, array_combine($keys, $keys));
    foreach ($keys as $key) {
        $_SERVER[$key] = $value;
    }

    try {
        $recaptcha = require config_path('recaptcha.php');
        $pterodactyl = require config_path('pterodactyl.php');

        expect($recaptcha['enabled'])->toBe($expected)
            ->and($pterodactyl['client_features']['allocations']['enabled'])->toBe($expected)
            ->and($pterodactyl['telemetry']['enabled'])->toBe($expected);
    } finally {
        foreach ($previous as $key => $original) {
            if ($original === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $original;
            }
        }
    }
})->with([['1', true], ['0', false], ['true', true], ['false', false]]);
