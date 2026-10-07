<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Extensions\ExtensionProviderRegistrationTest;

use Illuminate\Support\Facades\File;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Extensions\ExtensionProvider;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);
beforeEach(function (): void {
    $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-provider-registration-'.uniqid();
    File::ensureDirectoryExists($this->directory.'/standalone');
    File::put($this->directory.'/standalone/extension.json', json_encode(['id' => 'standalone', 'name' => 'Standalone', 'version' => '1.2.0', 'provider' => '\\'.StandaloneProvider::class], JSON_THROW_ON_ERROR));
    config(['extensions.directory' => $this->directory]);
    $this->app->make(ExtensionRepository::class)->flushDiscovery();
});
afterEach(function (): void {
    File::deleteDirectory($this->directory);
    $this->app->make(ExtensionRepository::class)->flushDiscovery();
});

test('laravel registers an extension provider by class the way it registers any other', function (): void {
    $provider = $this->app->register(StandaloneProvider::class);

    expect($provider)->toBeInstanceOf(StandaloneProvider::class);
    expect($provider->extensionId())->toBe('standalone');
    expect($provider->booted)->toBeTrue();
});

test('a provider no installed extension declares cannot be constructed without its manifest', function (): void {
    expect(fn () => new UndeclaredProvider($this->app))->toThrow(InvalidExtensionException::class, 'No installed extension declares '.UndeclaredProvider::class.' as its provider.');
});

final class StandaloneProvider extends ExtensionProvider
{
    public bool $booted = false;

    public function boot(): void
    {
        $this->booted = true;
    }

    public function extensionId(): string
    {
        return $this->id();
    }
}

final class UndeclaredProvider extends ExtensionProvider {}
