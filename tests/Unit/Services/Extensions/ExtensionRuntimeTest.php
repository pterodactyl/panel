<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Extensions\ExtensionRuntimeTest;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Services\Extensions\ExtensionLock;
use Pterodactyl\Services\Extensions\ExtensionRuntimeRefresher;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);

test('package operations exclude competing writers and nested activation retains the lock', function (): void {
    $directory = sys_get_temp_dir().'/ptero-lock-'.uniqid();
    config(['extensions.directory' => $directory]);
    $first = new ExtensionLock;
    $second = new ExtensionLock;
    try {
        $first->acquire('first');
        $first->acquire('nested');
        expect(fn () => $second->acquire('second'))->toThrow(InvalidExtensionException::class, 'Another operation');
        $first->release();
        expect(fn () => $second->acquire('second'))->toThrow(InvalidExtensionException::class, 'Another operation');
        $first->release();
        $second->acquire('second');
        $second->release();
    } finally {
        File::deleteDirectory($directory);
    }
});

test('activation invalidates cached routes and signals existing workers to restart', function (): void {
    $this->freezeTime();
    $bootstrap = $this->app->bootstrapPath();
    $directory = sys_get_temp_dir().'/ptero-routes-'.uniqid();
    File::ensureDirectoryExists($directory.'/cache');
    $this->app->useBootstrapPath($directory);
    File::put($this->app->getCachedRoutesPath(), '<?php return [];');
    try {
        resolve(ExtensionRuntimeRefresher::class)->refresh();
        expect($this->app->getCachedRoutesPath())->not->toBeFile();
        expect(Cache::get('illuminate:queue:restart'))->toBe(now()->getTimestamp());
    } finally {
        $this->app->useBootstrapPath($bootstrap);
        File::deleteDirectory($directory);
    }
});

test('development reload follows asset activation and rollback and stays absent outside debug mode', function (): void {
    $directory = sys_get_temp_dir().'/ptero-development-'.uniqid();
    config(['extensions.assets_directory' => $directory, 'app.debug' => true]);
    $assets = resolve(\Pterodactyl\Services\Extensions\ExtensionAssetPublisher::class);
    File::ensureDirectoryExists($directory.'/probe');
    $first = str_repeat('a', 64);
    $second = str_repeat('b', 64);
    try {
        $assets->activate('probe', $first);
        expect($assets->developmentPayload('probe'))->toBeNull();
        $assets->watchDevelopment('probe');
        $assets->activate('probe', $second);
        expect(File::get($directory.'/probe/_development'))->toBe($second);
        $assets->activate('probe', $first);
        expect(File::get($directory.'/probe/_development'))->toBe($first);
        expect($assets->developmentPayload('probe'))->toBe(['url' => '/assets/extensions/probe/_development', 'version' => $first]);
        config(['app.debug' => false]);
        expect($assets->developmentPayload('probe'))->toBeNull();
        $assets->stopDevelopment('probe');
        expect($directory.'/probe/_development')->not->toBeFile();
    } finally {
        File::deleteDirectory($directory);
    }
});
