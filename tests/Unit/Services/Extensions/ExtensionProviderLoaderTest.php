<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Extensions\ExtensionProviderLoaderTest;

use Illuminate\Support\Facades\File;
use Mockery;
use Pterodactyl\Exceptions\Service\Location\HasActiveNodesException;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionManifestValidator;
use Pterodactyl\Services\Extensions\ExtensionProviderLoader;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Tests\TestCase;
use ReflectionClass;
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

test('extension class loaders never replace a class the panel loads', function (): void {
    $directory = sys_get_temp_dir().'/ptero-shadow-'.uniqid();
    $decoy = '<?php namespace Pterodactyl\Exceptions\Service\Location; final class HasActiveNodesException {}';
    foreach (['src', 'bundled'] as $source) {
        File::ensureDirectoryExists($directory.'/'.$source.'/Exceptions/Service/Location');
        File::put($directory.'/'.$source.'/Exceptions/Service/Location/HasActiveNodesException.php', $decoy);
    }

    // What Composer's generated autoload.php does: register its loader in front of all others.
    File::ensureDirectoryExists($directory.'/vendor');
    File::put($directory.'/vendor/autoload.php', '<?php $loader = new \Composer\Autoload\ClassLoader; $loader->addPsr4("Pterodactyl\\\\", __DIR__."/../bundled"); $loader->register(true); return $loader;');

    // A manifest that skipped validation, claiming the panel namespace for itself.
    $manifest = ExtensionManifest::fromValidatedData($directory, ['id' => 'shadow', 'name' => 'Shadow', 'version' => '1.0.0'], ['Pterodactyl\\' => 'src'], null, 'native', null);
    $repository = Mockery::mock(ExtensionRepository::class);
    $repository->shouldReceive('clearErrors')->once()->with(['shadow']);
    try {
        (new ExtensionProviderLoader($this->app, $repository))->registerProviders(collect(['shadow' => $manifest]));

        expect((new ReflectionClass(HasActiveNodesException::class))->getFileName())->toBe(realpath(app_path('Exceptions/Service/Location/HasActiveNodesException.php')));
    } finally {
        File::deleteDirectory($directory);
    }
});
