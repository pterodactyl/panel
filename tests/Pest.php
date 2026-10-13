<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Pest\Expectation;
use Pest\Support\HigherOrderTapProxy;
use PHPUnit\Framework\TestCase;

function pterodactylTestCase(): TestCase
{
    $test = test();

    if (! $test instanceof HigherOrderTapProxy) {
        throw new LogicException('No Pest test case is currently running.');
    }

    return $test->target;
}

/*
 * Compares API exchanges with tests/Fixtures/api/{name}.json. Run with UPDATE_FIXTURES=1 to
 * record the fixtures instead.
 */
expect()->extend('toMatchApiFixture', function (string $name): Expectation {
    $path = __DIR__.'/Fixtures/api/'.$name.'.json';
    $actual = json_encode($this->value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";

    if (env('UPDATE_FIXTURES')) {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $actual);
    }

    expect($path)->toBeFile("The {$name} API fixture is missing; run the test with UPDATE_FIXTURES=1 to record it.");
    expect($actual)->toBe(File::get($path));

    return $this;
});
