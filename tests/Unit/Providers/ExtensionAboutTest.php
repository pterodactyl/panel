<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Providers\ExtensionAboutTest;

use Illuminate\Support\Facades\File;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);
beforeEach(function (): void {
    $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-about-'.uniqid();
    File::ensureDirectoryExists($this->directory.'/ledger');
    File::ensureDirectoryExists($this->directory.'/broken');
    File::put($this->directory.'/ledger/extension.json', json_encode(['id' => 'ledger', 'name' => 'Ledger', 'version' => '1.4.0'], JSON_THROW_ON_ERROR));
    File::put($this->directory.'/broken/extension.json', '{');
    config(['extensions.directory' => $this->directory]);
    $this->app->make(ExtensionRepository::class)->flushDiscovery();
});
afterEach(function (): void {
    File::deleteDirectory($this->directory);
    $this->app->make(ExtensionRepository::class)->flushDiscovery();
});

test('about lists each installed extension with its version and state', function (): void {
    $this->artisan('about', ['--only' => 'extensions'])
        ->expectsOutputToContain('Extensions')
        ->expectsOutputToContain('1.4.0 (not registered)')
        ->expectsOutputToContain('invalid manifest')
        ->assertSuccessful();
});

test('about reports extensions as off when the panel disables them', function (): void {
    config(['extensions.enabled' => false]);

    $this->artisan('about', ['--only' => 'extensions', '--json' => true])
        ->expectsOutputToContain('{"extensions":{"status":"OFF"}}')
        ->assertSuccessful();
});
