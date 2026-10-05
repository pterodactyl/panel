<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Console\Commands\Environment\AppSettingsCommandTest;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Pterodactyl\Tests\TestCase;

use function pterodactylTestCase;

uses(TestCase::class);
test('telemetry option is written as passed', function (string $option, string $expected): void {
    $environment = runSetup(['--telemetry' => $option]);
    expect($environment)->toContain("PTERODACTYL_TELEMETRY_ENABLED={$expected}");
})->with([['false', 'false'], ['true', 'true']]);
test('settings ui option is written as passed', function (string $option, string $expected): void {
    $environment = runSetup(['--settings-ui' => $option]);
    expect($environment)->toContain("APP_ENVIRONMENT_ONLY={$expected}");
})->with([['false', 'true'], ['true', 'false']]);
/**
 * Runs p:environment:setup against a temporary base path and returns the .env file it wrote.
 *
 * @param  array<string, string>  $options
 */
function runSetup(array $options): string
{
    $directory = storage_path('framework/testing/'.Str::random(12));
    File::ensureDirectoryExists($directory);
    File::put("{$directory}/.env", "APP_NAME=Pterodactyl\n");
    $basePath = base_path();

    try {
        (function () use ($options, $directory): void {
            $command = $this->artisan('p:environment:setup', [
                '--author' => 'admin@example.com',
                '--url' => 'https://panel.example.com',
                '--timezone' => 'UTC',
                '--cache' => 'file',
                '--session' => 'file',
                '--queue' => 'sync',
                '--settings-ui' => 'true',
                '--telemetry' => 'false',
                '--no-interaction' => true,
                ...$options,
            ]);
            $this->app->setBasePath($directory);
            $command->assertSuccessful()->run();
        })->call(pterodactylTestCase());

        return File::get("{$directory}/.env");
    } finally {
        app()->setBasePath($basePath);
        File::deleteDirectory($directory);
    }
}
