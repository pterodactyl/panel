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
use Pterodactyl\Exceptions\Extensions\ExtensionAlreadyInstalledException;
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
    $this->app->make(InstallsExtensions::class)->install($source, enable: true);
    File::put($source.'/extension.json', json_encode(['id' => 'eventful', 'name' => 'Eventful', 'version' => '2.0.0'], JSON_THROW_ON_ERROR));
    Exceptions::fake();
    $assets = Mockery::mock(ExtensionAssetPublisher::class)->makePartial();
    $assets->shouldReceive('publish')->once()->andThrow(new RuntimeException('asset publication failed'));
    $assets->shouldReceive('activate')->once()->andThrow(new RuntimeException('asset rollback failed'));
    $this->app->instance(ExtensionAssetPublisher::class, $assets);

    expect(fn () => $this->app->make(InstallsExtensions::class)->install($source, replace: true))->toThrow(RuntimeException::class, 'asset publication failed');

    expect($this->app->make(ExtensionManifestValidator::class)->fromDirectory($this->installDirectory.'/eventful')->version)->toBe('1.0.0');
    $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'version' => '1.0.0', 'enabled' => true]);
    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'asset rollback failed');
    expect(glob($this->installDirectory.'/.previous-*'))->toBeEmpty();
});

test('a failed package restore retains the backup for manual recovery', function (): void {
    $source = writeExtension('eventful');
    $this->app->make(InstallsExtensions::class)->install($source, enable: true);
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

    expect(fn () => $this->app->make(InstallsExtensions::class)->install($source, replace: true))->toThrow(RuntimeException::class, 'asset publication failed');

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

test('archives with too many entries are rejected before extraction', function (): void {
    $this->app->useStoragePath(dirname($this->sourceDirectory).'/storage');
    $path = $this->sourceDirectory.'/many.zip';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);
    $zip->addFromString('extension.json', '{}');
    for ($i = 0; $i < ExtensionPackageLocator::MAX_ARCHIVE_ENTRIES; $i++) {
        $zip->addFromString("files/{$i}.txt", '');
    }

    $zip->close();

    expect(fn () => $this->app->make(ExtensionPackageLocator::class)->locate($path))->toThrow(InvalidExtensionException::class, 'more than the allowed '.ExtensionPackageLocator::MAX_ARCHIVE_ENTRIES);

    expect(glob(storage_path('app/extensions-tmp/*')))->toBeEmpty();
});
test('archives that expand beyond the size limit are rejected before extraction', function (): void {
    $this->app->useStoragePath(dirname($this->sourceDirectory).'/storage');
    $sparse = $this->sourceDirectory.'/zeros.bin';
    $handle = fopen($sparse, 'wb');
    ftruncate($handle, ExtensionPackageLocator::MAX_ARCHIVE_UNCOMPRESSED_BYTES + 1);
    fclose($handle);

    $path = $this->sourceDirectory.'/bomb.zip';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);
    $zip->addFromString('extension.json', '{}');
    $zip->addFile($sparse, 'zeros.bin');
    $zip->close();
    unlink($sparse);

    expect(filesize($path))->toBeLessThan(ExtensionPackageLocator::MAX_ARCHIVE_UNCOMPRESSED_BYTES / 100);
    expect(fn () => $this->app->make(ExtensionPackageLocator::class)->locate($path))->toThrow(InvalidExtensionException::class, 'expands to more than the allowed');

    expect(glob(storage_path('app/extensions-tmp/*')))->toBeEmpty();
});

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

    expect(fn () => $installer->install($source, replace: true))->toThrow(InvalidExtensionException::class, 'built file is missing');
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

    expect(fn () => $installer->install($source, replace: true))->toThrow(InvalidExtensionException::class, 'ships Tailwind utilities in dist/index.css (.flex) without its "ev" prefix');
    expect(fn () => $installer->install(writeStyled('eventful', 'ev', 'ext', '2.0.0'), replace: true))->toThrow(InvalidExtensionException::class, 'ships Tailwind utilities in dist/index.css (.ext\:flex) without its "ev" prefix');
    expect(fn () => $installer->install(writeStyled('eventful', null, 'ev', '2.0.0'), replace: true))->toThrow(InvalidExtensionException::class, 'declares no Tailwind prefix - add "prefix": "eventful" to "ui" in extension.json');
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

        $installer->install(writeStyled('eventful', 'ev', 'ev', '1.1.0'), enable: true, replace: true);
        expect(fn () => $installer->install(writeStyled('eventful', 'shared', 'shared', '2.0.0'), replace: true))
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
    $replacement = $installer->install($source, replace: true);

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

    expect(fn () => $installer->install($source, replace: true))->toThrow(InvalidExtensionException::class, 'migrations failed');
    $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'version' => '1.0.0', 'enabled' => true]);
    expect((new ExtensionManifestValidator)->fromDirectory($this->installDirectory.'/eventful')->version)->toBe('1.0.0');
    Extension::query()->where('identifier', 'eventful')->delete();
    File::deleteDirectory($this->installDirectory.'/eventful');
    $this->app->make(ExtensionRepository::class)->flushDiscovery();
    expect(fn () => $installer->install($source, enable: true))->toThrow(InvalidExtensionException::class, 'migrations failed');
    $this->assertDatabaseMissing('extensions', ['identifier' => 'eventful']);
    expect($this->installDirectory.'/eventful')->not->toBeDirectory();
});

test('enabling refuses a migration Laravel would treat as already run', function (): void {
    $source = writeExtension('eventful');
    $core = basename((string) (glob(database_path('migrations').'/*_*.php') ?: [''])[0]);
    File::ensureDirectoryExists($source.'/database/migrations');
    File::put($source.'/database/migrations/'.$core, '<?php');
    Artisan::shouldReceive('call')->never();

    expect(fn () => $this->app->make(InstallsExtensions::class)->install($source, enable: true))->toThrow(InvalidExtensionException::class, 'the panel has a migration with the same name');
    $this->assertDatabaseMissing('extensions', ['identifier' => 'eventful']);
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

    $installed = $installer->install($source, replace: true);

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

    expect(fn () => $installer->install($source, replace: true))->toThrow(RuntimeException::class, 'upgrade rejected');

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

        expect(fn () => $installer->install($source, replace: true))->toThrow(InvalidExtensionException::class, 'dashboard.serverCard');

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

test('a package with an installed id replaces nothing until the replacement is confirmed', function (): void {
    $installer = $this->app->make(InstallsExtensions::class);
    $source = writeExtension('eventful');
    $installer->install($source, enable: true);
    File::put($source.'/extension.json', json_encode(['id' => 'eventful', 'name' => 'Eventful', 'version' => '2.0.0'], JSON_THROW_ON_ERROR));
    File::ensureDirectoryExists($source.'/database/migrations');
    Event::fake([ExtensionInstalling::class]);
    Artisan::shouldReceive('call')->never();

    try {
        $installer->install($source);
        $this->fail('The replacement was not refused.');
    } catch (ExtensionAlreadyInstalledException $exception) {
        expect([$exception->identifier, $exception->installedVersion, $exception->version, $exception->enabled])->toBe(['eventful', '1.0.0', '2.0.0', true]);
    }

    Event::assertNothingDispatched();
    expect((new ExtensionManifestValidator)->fromDirectory($this->installDirectory.'/eventful')->version)->toBe('1.0.0');
    $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'version' => '1.0.0', 'enabled' => true]);

    // Reinstalling the installed copy in place, as the scaffolded workflow does, replaces nothing.
    File::deleteDirectory($source.'/database');
    File::put($this->installDirectory.'/eventful/extension.json', json_encode(['id' => 'eventful', 'name' => 'Eventful', 'version' => '1.0.1'], JSON_THROW_ON_ERROR));
    expect($installer->install($this->installDirectory.'/eventful')->version)->toBe('1.0.1');
});

test('the install command replaces an installed extension only once that is confirmed', function (): void {
    $source = writeExtension('eventful');
    $this->artisan('p:extension:install', ['path' => $source, '--enable' => true])->assertSuccessful();
    File::put($source.'/extension.json', json_encode(['id' => 'eventful', 'name' => 'Eventful', 'version' => '2.0.0'], JSON_THROW_ON_ERROR));
    $question = 'Extension "eventful" is already installed. Replace v1.0.0 with v2.0.0? It stays enabled and its migrations run.';

    $this->artisan('p:extension:install', ['path' => $source, '--no-interaction' => true])
        ->expectsOutputToContain('Pass --replace to replace it.')
        ->assertFailed();
    $this->artisan('p:extension:install', ['path' => $source])->expectsConfirmation($question, 'no')->assertFailed();
    $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'version' => '1.0.0', 'enabled' => true]);

    $this->artisan('p:extension:install', ['path' => $source])->expectsConfirmation($question, 'yes')->assertSuccessful();
    $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'version' => '2.0.0', 'enabled' => true]);

    File::put($source.'/extension.json', json_encode(['id' => 'eventful', 'name' => 'Eventful', 'version' => '3.0.0'], JSON_THROW_ON_ERROR));
    $this->artisan('p:extension:install', ['path' => $source, '--replace' => true, '--no-interaction' => true])->assertSuccessful();
    $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'version' => '3.0.0', 'enabled' => true]);
});

test('disabled installs publish no assets and failed activations remove the build they published', function (): void {
    $installer = $this->app->make(InstallsExtensions::class);
    $assets = $this->app->make(ExtensionAssetPublisher::class);
    $source = writePresentation('eventful', []);
    File::put($source.'/dist/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

    $installer->install($source);
    expect($assets->publishedPath('eventful'))->not->toBeDirectory();

    $this->app->make(SetsExtensionEnabled::class)->setEnabled('eventful', true);
    $published = $assets->currentVersion('eventful');
    expect($assets->publishedPath('eventful').'/'.$published.'/logo.svg')->toBeFile();

    writePresentation('eventful', [], '2.0.0');
    File::put($source.'/dist/client.js', 'export default {setup() {}, version: 2};');
    $rejectUpdate = true;
    DB::listen(static function (QueryExecuted $query) use (&$rejectUpdate): void {
        if ($rejectUpdate && str_starts_with(mb_strtolower($query->sql), 'update `extensions`')) {
            $rejectUpdate = false;
            throw new RuntimeException('metadata update failed');
        }
    });

    expect(fn () => $installer->install($source, replace: true))->toThrow(RuntimeException::class, 'metadata update failed');
    expect($assets->currentVersion('eventful'))->toBe($published);
    expect(array_map(basename(...), glob($assets->publishedPath('eventful').'/*', GLOB_ONLYDIR)))->toBe([$published]);
});

test('directory installs refuse symbolic links and leave node_modules behind', function (): void {
    $installer = $this->app->make(InstallsExtensions::class);
    $source = writePresentation('eventful', []);
    File::put(dirname($this->sourceDirectory).'/panel.env', 'APP_KEY=secret');
    File::ensureDirectoryExists($source.'/resources/lang');
    symlink(dirname($this->sourceDirectory).'/panel.env', $source.'/resources/lang/en.json');

    expect(fn () => $installer->install($source))->toThrow(InvalidExtensionException::class, 'resources/lang/en.json is a symbolic link');
    expect($this->installDirectory.'/eventful')->not->toBeDirectory();
    expect(glob($this->installDirectory.'/.staging-*'))->toBeEmpty();
    $this->assertDatabaseMissing('extensions', ['identifier' => 'eventful']);

    unlink($source.'/resources/lang/en.json');
    File::ensureDirectoryExists($source.'/node_modules/tool');
    File::put($source.'/node_modules/tool/cli.js', '');
    File::ensureDirectoryExists($source.'/node_modules/.bin');
    symlink('../tool/cli.js', $source.'/node_modules/.bin/tool');

    $installer->install($source);
    expect($this->installDirectory.'/eventful/dist/client.js')->toBeFile();
    expect($this->installDirectory.'/eventful/node_modules')->not->toBeDirectory();
});

test('removing an extension linked to its source removes only the link', function (): void {
    $source = writeExtension('eventful');
    File::put($source.'/src.php', '<?php');
    symlink($source, $this->installDirectory.'/eventful');
    $this->app->make(InstallsExtensions::class)->install($source);

    $this->app->make(RemovesExtensions::class)->remove('eventful');

    expect(is_link($this->installDirectory.'/eventful') || file_exists($this->installDirectory.'/eventful'))->toBeFalse();
    expect($source.'/extension.json')->toBeFile();
    expect($source.'/src.php')->toBeFile();
    expect(glob($this->installDirectory.'/.previous-*'))->toBeEmpty();
    $this->assertDatabaseMissing('extensions', ['identifier' => 'eventful']);
});

test('archives are limited by the bytes they really expand to, not the sizes they declare', function (): void {
    $this->app->useStoragePath(dirname($this->sourceDirectory).'/storage');
    $sparse = $this->sourceDirectory.'/zeros.bin';
    $handle = fopen($sparse, 'wb');
    ftruncate($handle, ExtensionPackageLocator::MAX_ARCHIVE_UNCOMPRESSED_BYTES + 1);
    fclose($handle);

    $path = $this->sourceDirectory.'/bomb.zip';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);
    $zip->addFromString('extension.json', '{}');
    $zip->addFile($sparse, 'zeros.bin');
    $zip->close();
    unlink($sparse);

    // Claim the entry inflates to a single byte in both its local and central directory headers.
    $bytes = File::get($path);
    $local = mb_strpos($bytes, 'zeros.bin', 0, '8bit') - 30;
    $central = mb_strrpos($bytes, 'zeros.bin', 0, '8bit') - 46;
    expect(mb_substr($bytes, $local, 4, '8bit'))->toBe("PK\x03\x04")
        ->and(mb_substr($bytes, $central, 4, '8bit'))->toBe("PK\x01\x02");
    File::put($path, substr_replace(substr_replace($bytes, pack('V', 1), $local + 22, 4), pack('V', 1), $central + 24, 4));
    $zip = new ZipArchive;
    $zip->open($path);
    expect($zip->statIndex(1)['size'])->toBe(1);
    $zip->close();

    expect(fn () => $this->app->make(ExtensionPackageLocator::class)->locate($path))->toThrow(InvalidExtensionException::class, 'expands to more than the allowed');

    expect(glob(storage_path('app/extensions-tmp/*')))->toBeEmpty();
});

test('archive entries that would land outside the package are refused', function (string $name): void {
    $this->app->useStoragePath(dirname($this->sourceDirectory).'/storage');
    $path = $this->sourceDirectory.'/escape.zip';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);
    $zip->addFromString('extension.json', '{}');
    $zip->addFromString($name, 'escaped');
    $zip->close();

    expect(fn () => $this->app->make(ExtensionPackageLocator::class)->locate($path))->toThrow(InvalidExtensionException::class, 'contains an entry outside the package');

    expect(glob(storage_path('app/extensions-tmp/*')))->toBeEmpty();
    expect(storage_path('app/escaped.txt'))->not->toBeFile();
})->with(['../../escaped.txt', '/tmp/escaped.txt', 'dist/..\\..\\..\\escaped.txt']);

test('changes are refused up front when an extension directory is not writable', function (): void {
    if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
        $this->markTestSkipped('Directory permissions do not apply to root.');
    }

    $source = writeExtension('eventful');
    $this->app->make(InstallsExtensions::class)->install($source, enable: true);
    chmod($this->installDirectory, 0555);

    try {
        $message = 'The panel cannot write to '.$this->installDirectory.'.';
        expect(fn () => $this->app->make(InstallsExtensions::class)->install($source, replace: true))->toThrow(InvalidExtensionException::class, $message)
            ->and(fn () => $this->app->make(SetsExtensionEnabled::class)->setEnabled('eventful', false))->toThrow(InvalidExtensionException::class, $message)
            ->and(fn () => $this->app->make(RemovesExtensions::class)->remove('eventful'))->toThrow(InvalidExtensionException::class, $message);
    } finally {
        chmod($this->installDirectory, 0755);
    }

    $this->assertDatabaseHas('extensions', ['identifier' => 'eventful', 'enabled' => true]);
    expect(glob($this->installDirectory.'/.staging-*'))->toBeEmpty();
});
