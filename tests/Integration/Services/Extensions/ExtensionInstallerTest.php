<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Extensions\ExtensionInstallerTest;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\File;
use Mockery;
use Pterodactyl\Contracts\Extensions\InstallsExtensions;
use Pterodactyl\Contracts\Extensions\RemovesExtensions;
use Pterodactyl\Contracts\Extensions\SetsExtensionEnabled;
use Pterodactyl\Events\Extensions\ExtensionDisabled;
use Pterodactyl\Events\Extensions\ExtensionDisabling;
use Pterodactyl\Events\Extensions\ExtensionEnabled;
use Pterodactyl\Events\Extensions\ExtensionEnabling;
use Pterodactyl\Events\Extensions\ExtensionInstalled;
use Pterodactyl\Events\Extensions\ExtensionInstalling;
use Pterodactyl\Events\Extensions\ExtensionRemoved;
use Pterodactyl\Events\Extensions\ExtensionRemoving;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Models\Extension;
use Pterodactyl\Services\Extensions\ExtensionAssetPublisher;
use Pterodactyl\Services\Extensions\ExtensionManifestValidator;
use Pterodactyl\Services\Extensions\ExtensionPackageLocator;
use Pterodactyl\Services\Extensions\ExtensionProviderLoader;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Extensions\ExtensionSettings;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use RuntimeException;
use ZipArchive;

use function pterodactylTestCase;

uses(IntegrationTestCase::class);
beforeEach(function (): void {
    $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-installer-'.uniqid();
    $this->sourceDirectory = $base.DIRECTORY_SEPARATOR.'source';
    $this->installDirectory = $base.DIRECTORY_SEPARATOR.'installed';
    $this->assetsDirectory = $base.DIRECTORY_SEPARATOR.'assets';
    File::ensureDirectoryExists($this->sourceDirectory);
    File::ensureDirectoryExists($this->installDirectory);
    File::ensureDirectoryExists($this->assetsDirectory);
    config(['extensions.directory' => $this->installDirectory, 'extensions.assets_directory' => $this->assetsDirectory]);
    Extension::query()->where('identifier', 'eventful')->delete();
    $this->app->make(ExtensionRepository::class)->flushDiscovery();
});
afterEach(function (): void {
    Extension::query()->where('identifier', 'eventful')->delete();
    File::deleteDirectory(dirname($this->sourceDirectory));
});
test('install enable disable and remove dispatch lifecycle events', function (): void {
    Event::fake([ExtensionInstalling::class, ExtensionInstalled::class, ExtensionEnabling::class, ExtensionEnabled::class, ExtensionDisabling::class, ExtensionDisabled::class, ExtensionRemoving::class, ExtensionRemoved::class]);
    $source = writeExtension('eventful');
    $installer = $this->app->make(InstallsExtensions::class);
    $state = $this->app->make(SetsExtensionEnabled::class);
    $remover = $this->app->make(RemovesExtensions::class);
    $installer->install($source, enable: true);
    $this->assertDatabaseHas('extensions', ['identifier' => 'eventful']);
    expect(File::isDirectory($this->installDirectory.DIRECTORY_SEPARATOR.'eventful'))->toBeTrue();
    $state->setEnabled('eventful', false);
    $remover->remove('eventful');
    $this->assertDatabaseMissing('extensions', ['identifier' => 'eventful']);
    expect(File::isDirectory($this->installDirectory.DIRECTORY_SEPARATOR.'eventful'))->toBeFalse();
    Event::assertDispatched(ExtensionInstalling::class, fn (ExtensionInstalling $event): bool => $event->manifest->id === 'eventful');
    Event::assertDispatched(ExtensionInstalled::class, fn (ExtensionInstalled $event): bool => $event->manifest->id === 'eventful');
    Event::assertDispatched(ExtensionEnabling::class, fn (ExtensionEnabling $event): bool => $event->manifest->id === 'eventful');
    Event::assertDispatched(ExtensionEnabled::class, fn (ExtensionEnabled $event): bool => $event->manifest->id === 'eventful');
    Event::assertDispatched(ExtensionDisabling::class, fn (ExtensionDisabling $event): bool => $event->manifest->id === 'eventful');
    Event::assertDispatched(ExtensionDisabled::class, fn (ExtensionDisabled $event): bool => $event->manifest->id === 'eventful');
    Event::assertDispatched(ExtensionRemoving::class, fn (ExtensionRemoving $event): bool => $event->manifest->id === 'eventful');
    Event::assertDispatched(ExtensionRemoved::class, fn (ExtensionRemoved $event): bool => $event->identifier === 'eventful');
});
function writeExtension(string $identifier): string
{
    return (function () use ($identifier): string {
        $path = $this->sourceDirectory.DIRECTORY_SEPARATOR.$identifier;
        File::ensureDirectoryExists($path);
        File::put($path.DIRECTORY_SEPARATOR.'extension.json', json_encode(['id' => $identifier, 'name' => 'Eventful', 'version' => '1.0.0'], JSON_THROW_ON_ERROR));

        return $path;
    })->call(pterodactylTestCase());
}

test('asset rollback failures preserve the previous package and the original error', function (): void {
    $source = writeExtension('eventful');
    $this->app->make(InstallsExtensions::class)->install($source);
    File::put($source.'/extension.json', json_encode(['id' => 'eventful', 'name' => 'Eventful', 'version' => '2.0.0'], JSON_THROW_ON_ERROR));
    Exceptions::fake();
    $assets = Mockery::mock(ExtensionAssetPublisher::class)->makePartial();
    $assets->shouldReceive('publish')->once()->andThrow(new RuntimeException('asset publication failed'));
    $assets->shouldReceive('activate')->once()->andThrow(new RuntimeException('asset rollback failed'));
    $this->app->instance(ExtensionAssetPublisher::class, $assets);

    expect(fn () => $this->app->make(InstallsExtensions::class)->install($source))->toThrow(RuntimeException::class, 'asset publication failed');

    expect($this->app->make(ExtensionManifestValidator::class)->fromDirectory($this->installDirectory.'/eventful')->version)->toBe('1.0.0');
    $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'version' => '1.0.0', 'enabled' => false]);
    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'asset rollback failed');
    expect(glob($this->installDirectory.'/.previous-*'))->toBeEmpty();
});

test('a failed package restore retains the backup for manual recovery', function (): void {
    $source = writeExtension('eventful');
    $this->app->make(InstallsExtensions::class)->install($source);
    File::put($source.'/extension.json', json_encode(['id' => 'eventful', 'name' => 'Eventful', 'version' => '2.0.0'], JSON_THROW_ON_ERROR));
    Exceptions::fake();
    $filesystem = File::getFacadeRoot();
    File::partialMock()->shouldReceive('move')->andReturnUsing(static function (string $from, string $to) use ($filesystem): bool {
        if (str_contains($from, '.previous-')) {
            return false;
        }

        return $filesystem->move($from, $to);
    });
    $assets = Mockery::mock(ExtensionAssetPublisher::class)->makePartial();
    $assets->shouldReceive('publish')->once()->andThrow(new RuntimeException('asset publication failed'));
    $this->app->instance(ExtensionAssetPublisher::class, $assets);

    expect(fn () => $this->app->make(InstallsExtensions::class)->install($source))->toThrow(RuntimeException::class, 'asset publication failed');

    $backups = glob($this->installDirectory.'/.previous-*');
    expect($backups)->toHaveCount(1);
    expect($this->app->make(ExtensionManifestValidator::class)->fromDirectory($backups[0])->version)->toBe('1.0.0');
    $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'version' => '1.0.0']);
    Exceptions::assertReported(fn (RuntimeException $exception): bool => str_contains($exception->getMessage(), $backups[0]));
});

test('invalid archive roots leave no extracted files', function (bool $ambiguous): void {
    $this->app->useStoragePath(dirname($this->sourceDirectory).'/storage');
    $path = $this->sourceDirectory.'/invalid.zip';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);
    $zip->addFromString('readme.txt', 'No extension manifest');
    if ($ambiguous) {
        $zip->addFromString('first/extension.json', '{}');
        $zip->addFromString('second/extension.json', '{}');
    }

    $zip->close();

    expect(fn () => $this->app->make(ExtensionPackageLocator::class)->locate($path))->toThrow(InvalidExtensionException::class, 'Could not locate a single');

    expect(glob(storage_path('app/extensions-tmp/*')))->toBeEmpty();
})->with([false, true]);

test('completion observer failures leave a successful install intact', function (): void {
    $source = writeExtension('eventful');
    Exceptions::fake();
    Event::listen(ExtensionInstalled::class, static function (): void {
        throw new RuntimeException('installation observer failed');
    });

    $installed = $this->app->make(InstallsExtensions::class)->install($source);

    expect($installed->version)->toBe('1.0.0');
    expect($installed->directory)->toBeDirectory();
    $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'version' => '1.0.0']);
    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'installation observer failed');
});

test('state changes restart workers and notify observers only after commit', function (): void {
    $source = writeExtension('eventful');
    $this->app->make(InstallsExtensions::class)->install($source);
    Cache::forget('illuminate:queue:restart');
    Event::fake([ExtensionEnabled::class]);
    $state = $this->app->make(SetsExtensionEnabled::class);

    DB::transaction(function () use ($state): void {
        $state->setEnabled('eventful', true);

        expect(Cache::get('illuminate:queue:restart'))->toBeNull();
        Event::assertNotDispatched(ExtensionEnabled::class);
    });

    expect(Cache::get('illuminate:queue:restart'))->toBeInt();
    $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'enabled' => true]);
    Event::assertDispatchedTimes(ExtensionEnabled::class, 1);
});

test('rolled back state changes emit no completion notifications or restart signals', function (): void {
    $source = writeExtension('eventful');
    $this->app->make(InstallsExtensions::class)->install($source);
    Cache::forget('illuminate:queue:restart');
    Event::fake([ExtensionEnabled::class]);
    $state = $this->app->make(SetsExtensionEnabled::class);

    expect(fn () => DB::transaction(function () use ($state): void {
        $state->setEnabled('eventful', true);

        throw new RuntimeException('caller rolled back');
    }))->toThrow(RuntimeException::class, 'caller rolled back');

    expect(Cache::get('illuminate:queue:restart'))->toBeNull();
    $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'enabled' => false]);
    Event::assertNotDispatched(ExtensionEnabled::class);
});

test('provider recovery clears errors in one write and leaves healthy timestamps alone', function (): void {
    $repository = $this->app->make(ExtensionRepository::class);
    $installer = $this->app->make(InstallsExtensions::class);
    $identifiers = [];
    try {
        for ($i = 0; $i < 10; $i++) {
            $identifier = 'batch-plugin-'.$i;
            $identifiers[] = $identifier;
            $installer->install(writeExtension($identifier), enable: true);
        }

        $timestamp = now()->subDay()->startOfSecond();
        Extension::query()->whereIn('identifier', $identifiers)->update(['error' => null, 'updated_at' => $timestamp]);
        Extension::query()->whereIn('identifier', array_slice($identifiers, 0, 2))->update(['error' => 'previous failure']);
        $enabled = $repository->enabled();
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $this->app->make(ExtensionProviderLoader::class)->registerProviders($enabled);
            $writes = array_filter(DB::getQueryLog(), fn (array $query): bool => str_starts_with(mb_strtolower($query['query']), 'update'));
            expect($writes)->toHaveCount(1);
        } finally {
            DB::disableQueryLog();
        }

        expect(Extension::query()->whereIn('identifier', $identifiers)->whereNotNull('error')->count())->toBe(0);
        expect(Extension::query()->where('identifier', $identifiers[9])->firstOrFail()->updated_at->equalTo($timestamp))->toBeTrue();
    } finally {
        Extension::query()->whereIn('identifier', $identifiers)->delete();
    }
});

test('extension settings batch reads preserve values and subsequent mutations', function (): void {
    $settings = [];
    for ($i = 0; $i < 10; $i++) {
        $setting = new ExtensionSettings('batch-settings-'.$i);
        $setting->set('visible', 'value-'.$i);
        $settings[] = new ExtensionSettings('batch-settings-'.$i);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();
    try {
        ExtensionSettings::preload($settings);
        foreach ($settings as $i => $setting) {
            expect($setting->get('visible'))->toBe('value-'.$i);
            expect($setting->get('missing', 'default'))->toBe('default');
        }

        expect(DB::getQueryLog())->toHaveCount(1);
    } finally {
        DB::disableQueryLog();
        foreach ($settings as $setting) {
            $setting->forget('visible');
        }
    }

    expect($settings[0]->get('visible', 'gone'))->toBe('gone');
});

test('a replacement without its build preserves the installed package and asset URL', function (): void {
    $source = writeExtension('eventful');
    File::put($source.'/extension.json', json_encode(['id' => 'eventful', 'name' => 'Eventful', 'version' => '1.0.0', 'ui' => ['entry' => 'dist/client.js']], JSON_THROW_ON_ERROR));
    File::ensureDirectoryExists($source.'/dist/chunks');
    File::put($source.'/dist/client.js', 'export const version = 1;');
    File::put($source.'/dist/chunks/lazy.js', 'export const lazy = 1;');
    $installer = $this->app->make(InstallsExtensions::class);
    $assets = $this->app->make(ExtensionAssetPublisher::class);
    $original = $installer->install($source, enable: true);
    $url = $assets->entryUrl($original);
    $version = $assets->currentVersion('eventful');
    File::put($source.'/extension.json', json_encode(['id' => 'eventful', 'name' => 'Eventful', 'version' => '2.0.0', 'ui' => ['entry' => 'dist/client.js']], JSON_THROW_ON_ERROR));
    File::deleteDirectory($source.'/dist');

    expect(fn () => $installer->install($source))->toThrow(InvalidExtensionException::class, 'built file is missing');
    expect(File::get($this->installDirectory.'/eventful/dist/client.js'))->toContain('version = 1');
    expect($assets->entryUrl($original))->toBe($url);
    expect($assets->publishedPath('eventful').'/'.$version.'/chunks/lazy.js')->toBeFile();
    $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'version' => '1.0.0', 'enabled' => true]);
});

/** A built frontend package whose stylesheet is compiled with the given Tailwind prefix. */
function writeStyled(string $identifier, ?string $prefix, string $built, string $version = '1.0.0'): string
{
    $source = writeExtension($identifier);
    File::put($source.'/extension.json', json_encode(['id' => $identifier, 'name' => $identifier, 'version' => $version, 'ui' => array_filter(['entry' => 'dist/client.js', 'prefix' => $prefix])], JSON_THROW_ON_ERROR));
    File::ensureDirectoryExists($source.'/dist');
    File::put($source.'/dist/client.js', 'export const version = 1;');
    File::put($source.'/dist/index.css', "@layer utilities{.{$built}\\:flex{display:flex}}");

    return $source;
}

test('a build without its declared tailwind prefix is neither installed nor enabled', function (): void {
    $installer = $this->app->make(InstallsExtensions::class);
    $assets = $this->app->make(ExtensionAssetPublisher::class);
    $installer->install(writeStyled('eventful', 'ev', 'ev'));
    $version = $assets->currentVersion('eventful');
    $source = writeStyled('eventful', 'ev', 'ev', '2.0.0');
    File::put($source.'/dist/index.css', '@layer utilities{.flex{display:flex}}');

    expect(fn () => $installer->install($source))->toThrow(InvalidExtensionException::class, 'ships Tailwind utilities in dist/index.css (.flex) without its "ev" prefix');
    expect(fn () => $installer->install(writeStyled('eventful', 'ev', 'ext', '2.0.0')))->toThrow(InvalidExtensionException::class, 'ships Tailwind utilities in dist/index.css (.ext\:flex) without its "ev" prefix');
    expect(fn () => $installer->install(writeStyled('eventful', null, 'ev', '2.0.0')))->toThrow(InvalidExtensionException::class, 'declares no Tailwind prefix - add "prefix": "eventful" to "ui" in extension.json');
    expect(File::get($this->installDirectory.'/eventful/dist/index.css'))->toContain('.ev\:flex');
    $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'version' => '1.0.0', 'enabled' => false]);

    // A package that reached the extensions directory another way is stopped when it is enabled.
    Event::fake([ExtensionEnabling::class]);
    File::put($this->installDirectory.'/eventful/dist/index.css', '@layer utilities{.flex{display:flex}}');
    expect(fn () => $this->app->make(SetsExtensionEnabled::class)->setEnabled('eventful', true))->toThrow(InvalidExtensionException::class, 'without its "ev" prefix');
    Event::assertNotDispatched(ExtensionEnabling::class);
    expect($assets->currentVersion('eventful'))->toBe($version);
    $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'enabled' => false]);
});

test('two enabled extensions cannot build with the same tailwind prefix', function (): void {
    $installer = $this->app->make(InstallsExtensions::class);
    $enable = $this->app->make(SetsExtensionEnabled::class);
    try {
        $installer->install(writeStyled('prefix-owner', 'shared', 'shared'), enable: true);

        // Install with --enable, enable later and upgrade while enabled all go through the same comparison.
        expect(fn () => $installer->install(writeStyled('eventful', 'shared', 'shared'), enable: true))
            ->toThrow(InvalidExtensionException::class, 'Extension "eventful" cannot use Tailwind prefix "shared": enabled extension "prefix-owner" also declares it.');
        $installer->install(writeStyled('eventful', 'shared', 'shared'));
        Event::fake([ExtensionEnabling::class, ExtensionEnabled::class]);
        expect(fn () => $enable->setEnabled('eventful', true))
            ->toThrow(InvalidExtensionException::class, 'Extension "eventful" cannot use Tailwind prefix "shared": enabled extension "prefix-owner" also declares it.');
        Event::assertNothingDispatched();
        $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'enabled' => false]);

        $installer->install(writeStyled('eventful', 'ev', 'ev', '1.1.0'), enable: true);
        expect(fn () => $installer->install(writeStyled('eventful', 'shared', 'shared', '2.0.0')))
            ->toThrow(InvalidExtensionException::class, 'cannot use Tailwind prefix "shared": enabled extension "prefix-owner" also declares it.');
        $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'version' => '1.1.0', 'enabled' => true]);
        expect(File::get($this->installDirectory.'/eventful/dist/index.css'))->toContain('.ev\:flex');
        expect(collect($this->app->make(ExtensionRepository::class)->frontendPayload(false))->pluck('prefix', 'id')->all())->toBe(['eventful' => 'ev', 'prefix-owner' => 'shared']);
    } finally {
        Extension::query()->where('identifier', 'prefix-owner')->delete();
    }
});

test('successful upgrades preserve old chunks and point new sessions at new content', function (): void {
    $source = writeExtension('eventful');
    File::put($source.'/extension.json', json_encode(['id' => 'eventful', 'name' => 'Eventful', 'version' => '1.0.0', 'ui' => ['entry' => 'dist/client.js']], JSON_THROW_ON_ERROR));
    File::ensureDirectoryExists($source.'/dist/chunks');
    File::put($source.'/dist/client.js', 'export const version = 1;');
    File::put($source.'/dist/chunks/lazy.js', 'export const lazy = 1;');
    $installer = $this->app->make(InstallsExtensions::class);
    $assets = $this->app->make(ExtensionAssetPublisher::class);
    $original = $installer->install($source, enable: true);
    $url = $assets->entryUrl($original);
    $oldVersion = $assets->currentVersion('eventful');
    File::put($source.'/dist/client.js', 'export const version = 2;');
    $replacement = $installer->install($source);

    expect($assets->entryUrl($replacement))->not->toBe($url);
    expect(File::get($assets->publishedPath('eventful').'/'.$oldVersion.'/chunks/lazy.js'))->toContain('lazy = 1');
    expect(File::get($assets->publishedPath('eventful').'/'.$assets->currentVersion('eventful').'/client.js'))->toContain('version = 2');
    $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'enabled' => true]);
});

test('migration failure leaves a new extension inactive and restores an enabled upgrade', function (): void {
    $source = writeExtension('eventful');
    $installer = $this->app->make(InstallsExtensions::class);
    $installer->install($source, enable: true);
    File::put($source.'/extension.json', json_encode(['id' => 'eventful', 'name' => 'Eventful', 'version' => '2.0.0'], JSON_THROW_ON_ERROR));
    File::ensureDirectoryExists($source.'/database/migrations');
    Artisan::shouldReceive('call')->with('migrate', Mockery::type('array'))->twice()->andReturn(1);

    expect(fn () => $installer->install($source))->toThrow(InvalidExtensionException::class, 'migrations failed');
    $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'version' => '1.0.0', 'enabled' => true]);
    expect((new ExtensionManifestValidator)->fromDirectory($this->installDirectory.'/eventful')->version)->toBe('1.0.0');
    Extension::query()->where('identifier', 'eventful')->delete();
    File::deleteDirectory($this->installDirectory.'/eventful');
    $this->app->make(ExtensionRepository::class)->flushDiscovery();
    expect(fn () => $installer->install($source, enable: true))->toThrow(InvalidExtensionException::class, 'migrations failed');
    $this->assertDatabaseMissing('extensions', ['identifier' => 'eventful']);
    expect($this->installDirectory.'/eventful')->not->toBeDirectory();
});

test('completion observer failures do not undo committed upgrades', function (string $event): void {
    $source = writeExtension('eventful');
    $installer = $this->app->make(InstallsExtensions::class);
    $installer->install($source, enable: true);
    File::put($source.'/extension.json', json_encode(['id' => 'eventful', 'name' => 'Eventful', 'version' => '2.0.0'], JSON_THROW_ON_ERROR));
    Exceptions::fake();
    Event::listen($event, function (): never {
        throw new RuntimeException('activation listener failed');
    });

    $installed = $installer->install($source);

    $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'version' => '2.0.0', 'enabled' => true]);
    expect($this->app->make(ExtensionManifestValidator::class)->fromDirectory($installed->directory)->version)->toBe('2.0.0');
    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'activation listener failed');
})->with([ExtensionEnabled::class, ExtensionInstalled::class]);

test('pre-activation observers can reject an upgrade without changing the installed package', function (string $event): void {
    $source = writeExtension('eventful');
    $installer = $this->app->make(InstallsExtensions::class);
    $installer->install($source, enable: true);
    File::put($source.'/extension.json', json_encode(['id' => 'eventful', 'name' => 'Eventful', 'version' => '2.0.0'], JSON_THROW_ON_ERROR));
    Event::listen($event, static function (): never {
        throw new RuntimeException('upgrade rejected');
    });

    expect(fn () => $installer->install($source))->toThrow(RuntimeException::class, 'upgrade rejected');

    $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'version' => '1.0.0', 'enabled' => true]);
    expect($this->app->make(ExtensionManifestValidator::class)->fromDirectory($this->installDirectory.'/eventful')->version)->toBe('1.0.0');
})->with([ExtensionInstalling::class, ExtensionEnabling::class]);

test('disabled packages can be installed before their dependencies and cannot be enabled early', function (): void {
    $source = writeExtension('eventful');
    File::put($source.'/extension.json', json_encode(['id' => 'eventful', 'name' => 'Eventful', 'version' => '1.0.0', 'requires' => ['extensions' => ['missing-dependency' => '^1.0']]], JSON_THROW_ON_ERROR));
    $this->app->make(InstallsExtensions::class)->install($source);

    $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'version' => '1.0.0', 'enabled' => false]);
    expect(fn () => $this->app->make(SetsExtensionEnabled::class)->setEnabled('eventful', true))->toThrow(InvalidExtensionException::class, 'requires enabled extension');
    $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'enabled' => false]);
});

/** @param list<string> $components */
function writePresentation(string $identifier, array $components, string $version = '1.0.0'): string
{
    $source = writeExtension($identifier);
    File::put($source.'/extension.json', json_encode(['id' => $identifier, 'name' => $identifier, 'version' => $version, 'requires' => ['sdk' => '*'], 'ui' => ['entry' => 'dist/client.js', 'components' => $components]], JSON_THROW_ON_ERROR));
    File::ensureDirectoryExists($source.'/dist');
    File::put($source.'/dist/client.js', 'export default {setup() {}};');

    return $source;
}

test('rejects enabling a competing replacement before migrations or activation events', function (): void {
    $installer = $this->app->make(InstallsExtensions::class);
    try {
        $installer->install(writePresentation('card-owner', ['dashboard.serverCard']), enable: true);
        $installer->install(writePresentation('eventful', ['dashboard.serverCard']));
        File::ensureDirectoryExists($this->installDirectory.'/eventful/database/migrations');
        Event::fake([ExtensionEnabling::class, ExtensionEnabled::class]);
        Artisan::shouldReceive('call')->never();

        expect(fn () => $this->app->make(SetsExtensionEnabled::class)->setEnabled('eventful', true))->toThrow(InvalidExtensionException::class, 'card-owner');

        $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'enabled' => false]);
        $this->assertDatabaseHas('extensions', ['identifier' => 'card-owner', 'enabled' => true]);
        Event::assertNothingDispatched();
        expect($this->app->make(ExtensionRepository::class)->frontendPayload(false)[0]['components'])->toBe(['dashboard.serverCard']);
    } finally {
        Extension::query()->where('identifier', 'card-owner')->delete();
    }
});

test('rejects an enabled upgrade with conflicting replacements and preserves its package', function (): void {
    $installer = $this->app->make(InstallsExtensions::class);
    try {
        $installer->install(writePresentation('card-owner', ['dashboard.serverCard']), enable: true);
        $installer->install(writeExtension('eventful'), enable: true);
        $source = writePresentation('eventful', ['dashboard.serverCard'], '2.0.0');
        Event::fake([ExtensionInstalling::class, ExtensionEnabling::class]);

        expect(fn () => $installer->install($source))->toThrow(InvalidExtensionException::class, 'dashboard.serverCard');

        $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'version' => '1.0.0', 'enabled' => true]);
        expect((new ExtensionManifestValidator)->fromDirectory($this->installDirectory.'/eventful')->components)->toBe([]);
        Event::assertNothingDispatched();
    } finally {
        Extension::query()->where('identifier', 'card-owner')->delete();
    }
});

test('failed removal restores the package and published assets with its metadata', function (): void {
    $source = writeExtension('eventful');
    $this->app->make(InstallsExtensions::class)->install($source);
    $assets = $this->app->make(ExtensionAssetPublisher::class)->publishedPath('eventful');
    File::ensureDirectoryExists($assets);
    File::put($assets.'/client.js', 'original asset');
    $rejectDeletion = true;
    DB::listen(static function (QueryExecuted $query) use (&$rejectDeletion): void {
        if ($rejectDeletion && str_starts_with(mb_strtolower($query->sql), 'delete from `extensions`')) {
            $rejectDeletion = false;
            throw new RuntimeException('metadata removal failed');
        }
    });

    expect(fn () => $this->app->make(RemovesExtensions::class)->remove('eventful'))
        ->toThrow(RuntimeException::class, 'metadata removal failed');

    $this->assertDatabaseHas('extensions', ['identifier' => 'eventful']);
    expect($this->installDirectory.'/eventful/extension.json')->toBeFile();
    expect(File::get($assets.'/client.js'))->toBe('original asset');
    expect(glob($this->installDirectory.'/.previous-*'))->toBeEmpty();
});

test('file editor and file browser replacements reach the frontend and stay exclusive per component', function (): void {
    $installer = $this->app->make(InstallsExtensions::class);
    try {
        $installer->install(writePresentation('code-editor', ['server.files.editor']), enable: true);
        $installer->install(writePresentation('tree-browser', ['server.files.manager']), enable: true);
        $installer->install(writePresentation('eventful', ['server.files.details', 'server.files.editor']));

        expect(fn () => $this->app->make(SetsExtensionEnabled::class)->setEnabled('eventful', true))->toThrow(InvalidExtensionException::class, 'code-editor');

        $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'enabled' => false]);
        expect(collect($this->app->make(ExtensionRepository::class)->frontendPayload(false))->pluck('components', 'id')->all())->toBe([
            'code-editor' => ['server.files.editor'],
            'tree-browser' => ['server.files.manager'],
        ]);
    } finally {
        Extension::query()->whereIn('identifier', ['code-editor', 'tree-browser'])->delete();
    }
});
