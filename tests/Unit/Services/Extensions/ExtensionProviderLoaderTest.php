<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Extensions\ExtensionProviderLoaderTest;

use Illuminate\Support\Facades\File;
use Mockery;
use Pterodactyl\Services\Extensions\ExtensionManifestValidator;
use Pterodactyl\Services\Extensions\ExtensionProviderLoader;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Tests\TestCase;
use Throwable;

uses(TestCase::class);

test('Composer packages load once and malformed vendor bootstraps leave healthy extensions available', function (): void {
    $directory = sys_get_temp_dir().'/ptero-vendor-'.uniqid();
    $namespace = 'VendorProbe'.str_replace('.', '', uniqid('', true));
    $counter = $directory.'/loaded';
    File::ensureDirectoryExists($directory.'/good/vendor');
    File::ensureDirectoryExists($directory.'/bad/vendor');
    File::ensureDirectoryExists($directory.'/good/dependency');
    File::put($directory.'/good/dependency/Included.php', '<?php namespace '.$namespace.'; final class Included {}');
    File::put($directory.'/good/vendor/autoload.php', '<?php $loader = new \Composer\Autoload\ClassLoader; $loader->addPsr4('.var_export($namespace.'\\', true).', __DIR__."/../dependency"); file_put_contents('.var_export($counter, true).', "x", FILE_APPEND); return $loader;');
    File::put($directory.'/bad/vendor/autoload.php', '<?php return false;');
    foreach (['good', 'bad'] as $id) {
        File::put($directory.'/'.$id.'/extension.json', json_encode(['id' => $id, 'name' => $id, 'version' => '1.0.0'], JSON_THROW_ON_ERROR));
    }
    $validator = new ExtensionManifestValidator;
    $manifests = collect(['bad' => $validator->fromDirectory($directory.'/bad'), 'good' => $validator->fromDirectory($directory.'/good')]);
    $repository = Mockery::mock(ExtensionRepository::class);
    $repository->shouldReceive('recordFailure')->twice()->with('bad', Mockery::type('string'), Mockery::type(Throwable::class), 'register');
    $repository->shouldReceive('clearErrors')->twice()->with(['good']);
    try {
        $loader = new ExtensionProviderLoader($this->app, $repository);
        $loader->registerProviders($manifests);
        $loader->registerProviders($manifests);
        expect(class_exists($namespace.'\\Included'))->toBeTrue();
        expect(File::get($counter))->toBe('x');
    } finally {
        File::deleteDirectory($directory);
    }
});
