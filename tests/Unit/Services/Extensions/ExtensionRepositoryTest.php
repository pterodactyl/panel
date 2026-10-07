<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Extensions\ExtensionRepositoryTest;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Pterodactyl\Events\Extensions\ExtensionLoadFailed;
use Pterodactyl\Facades\Extensions;
use Pterodactyl\Services\Extensions\ExtensionManager;
use Pterodactyl\Services\Extensions\ExtensionManifestValidator;
use Pterodactyl\Services\Extensions\ExtensionProviderLoader;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Tests\TestCase;

use function pterodactylTestCase;

uses(TestCase::class);
beforeEach(function () {
    $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-extensions-'.uniqid();
    File::ensureDirectoryExists($this->directory);
    config(['extensions.directory' => $this->directory]);
});
afterEach(function () {
    File::deleteDirectory($this->directory);
});
test('discovers valid packages and records invalid ones', function () {
    writeExtension('alpha', ['id' => 'alpha', 'name' => 'Alpha', 'version' => '1.0.0']);
    writeExtension('broken', ['id' => 'broken', 'name' => '', 'version' => '1.0.0']);
    // Directory name not matching the manifest id is rejected.
    writeExtension('renamed', ['id' => 'other', 'name' => 'Other', 'version' => '1.0.0']);
    $repository = repository();
    $discovered = $repository->discovered();
    expect($discovered->keys()->all())->toBe(['alpha']);
    expect($repository->discoveryErrors())->toHaveKey('broken');
    expect($repository->discoveryErrors())->toHaveKey('renamed');
});
test('nothing is enabled without install records', function () {
    writeExtension('alpha', ['id' => 'alpha', 'name' => 'Alpha', 'version' => '1.0.0']);
    $repository = repository();
    // No extensions table in the unit test database — records() degrades to
    // empty, so nothing is enabled and nothing throws.
    expect($repository->enabled()->isEmpty())->toBeTrue();
    expect($repository->frontendPayload(authenticated: true))->toBe([]);
});
test('provider boot failures are caught', function () {
    Event::fake([ExtensionLoadFailed::class]);
    writeExtension('broken', ['id' => 'broken', 'name' => 'Broken', 'version' => '1.0.0', 'provider' => 'BrokenExtension\Provider', 'autoload' => ['BrokenExtension\\' => 'src']]);
    $src = $this->directory.DIRECTORY_SEPARATOR.'broken'.DIRECTORY_SEPARATOR.'src';
    File::ensureDirectoryExists($src);
    file_put_contents($src.DIRECTORY_SEPARATOR.'Provider.php', <<<'PHP'
    <?php

    namespace BrokenExtension;

    use Pterodactyl\Extensions\ExtensionProvider;

    class Provider extends ExtensionProvider
    {
        public function boot(): void
        {
            throw new \RuntimeException('boot failed');
        }
    }
    PHP);
    $manifest = (new ExtensionManifestValidator)->fromDirectory($this->directory.DIRECTORY_SEPARATOR.'broken');
    $repository = repository();
    (new ExtensionProviderLoader(pterodactylTestCase()->app, $repository))->registerProviders(collect([$manifest->id => $manifest]));
    Event::assertDispatched(ExtensionLoadFailed::class, fn (ExtensionLoadFailed $event) => $event->identifier === 'broken' && $event->phase === 'boot' && str_contains($event->reason, 'boot failed'));
});
test('the facade and the deprecated manager hand out the same settings as the repository', function () {
    $settings = repository()->settings('alpha');

    expect(Extensions::settings('alpha'))->toBe($settings);
    expect(Extensions::isAvailable('alpha'))->toBeFalse();
    expect(app(ExtensionManager::class)->settings('alpha'))->toBe($settings);
});
function repository(): ExtensionRepository
{
    return (function () {
        $this->app->forgetInstance(ExtensionRepository::class);
        Extensions::clearResolvedInstance(ExtensionRepository::class);
        $repository = $this->app->make(ExtensionRepository::class);
        $repository->flushDiscovery();

        return $repository;
    })->call(pterodactylTestCase());
}
function writeExtension(string $directory, array $manifest): void
{
    (function () use ($directory, $manifest) {
        $path = $this->directory.DIRECTORY_SEPARATOR.$directory;
        File::ensureDirectoryExists($path);
        file_put_contents($path.DIRECTORY_SEPARATOR.'extension.json', json_encode($manifest));
    })->call(pterodactylTestCase());
}
