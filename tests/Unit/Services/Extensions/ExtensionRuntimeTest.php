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

test('development reload follows asset activation and rollback, tells stylesheet-only builds apart and stays absent outside debug mode', function (): void {
    $directory = sys_get_temp_dir().'/ptero-development-'.uniqid();
    config(['extensions.assets_directory' => $directory, 'app.debug' => true]);
    $assets = resolve(\Pterodactyl\Services\Extensions\ExtensionAssetPublisher::class);
    [$first, $second, $restyled] = [str_repeat('a', 64), str_repeat('b', 64), str_repeat('c', 64)];
    foreach ([$first => ['one', 'a{}'], $second => ['two', 'a{}'], $restyled => ['one', 'b{}']] as $version => [$script, $style]) {
        File::ensureDirectoryExists("{$directory}/probe/{$version}");
        File::put("{$directory}/probe/{$version}/client.js", $script);
        File::put("{$directory}/probe/{$version}/theme.css", $style);
    }

    $marker = fn (): array => File::json($directory.'/probe/_development');
    try {
        $assets->activate('probe', $first);
        expect($assets->developmentPayload('probe'))->toBeNull();
        $assets->watchDevelopment('probe');
        $scripts = $marker()['scripts'];
        expect($scripts)->toMatch('/^[a-f0-9]{64}$/');

        // Only the stylesheet changed, so the page can swap it in.
        $assets->activate('probe', $restyled);
        expect($marker())->toBe(['version' => $restyled, 'scripts' => $scripts]);
        $assets->activate('probe', $second);
        expect($marker()['version'])->toBe($second)->and($marker()['scripts'])->not->toBe($scripts);
        $assets->activate('probe', $first);
        expect($marker())->toBe(['version' => $first, 'scripts' => $scripts]);
        expect($assets->developmentPayload('probe'))->toBe(['url' => '/assets/extensions/probe/_development', 'version' => $first, 'scripts' => $scripts]);
        config(['app.debug' => false]);
        expect($assets->developmentPayload('probe'))->toBeNull();
        $assets->stopDevelopment('probe');
        expect($directory.'/probe/_development')->not->toBeFile();
    } finally {
        File::deleteDirectory($directory);
    }
});
