<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Helpers\AssetHashServiceTest;

use Illuminate\Filesystem\FilesystemManager;
use Pterodactyl\Services\Helpers\AssetHashService;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);
test('reads native vite entries for the panel and extension runtime', function () {
    // The service memoizes the manifest statically; clear any manifest another
    // test in the same process may have loaded so the fixture below is honored.
    (function () {
        static::$manifest = null;
    })->bindTo(null, AssetHashService::class)();
    config()->set('pterodactyl.assets.use_hash', true);
    $manifest = [AssetHashService::MAIN_ENTRY => ['file' => 'bundle.abc.js', 'integrity' => 'sha384-main', 'css' => ['main.abc.css'], 'cssIntegrity' => ['main.abc.css' => 'sha384-css']], 'resources/scripts/ext-runtime/react.ts' => ['file' => 'ext-runtime.react.abc.js', 'integrity' => 'sha384-react'], 'invalid.ts' => ['file' => false]];
    $root = sys_get_temp_dir().'/pterodactyl-asset-hash-'.uniqid();
    mkdir($root.'/assets', 0777, true);
    file_put_contents($root.'/'.AssetHashService::MANIFEST_PATH, json_encode($manifest, JSON_THROW_ON_ERROR));
    try {
        $service = new AssetHashService($this->app->make(FilesystemManager::class), $root);
        expect($service->url(AssetHashService::MAIN_ENTRY))->toBe('/assets/bundle.abc.js');
        $this->assertStringContainsString('href="/assets/main.abc.css"', $service->cssImports(AssetHashService::MAIN_ENTRY));
        $this->assertStringContainsString('integrity="sha384-css"', $service->cssImports(AssetHashService::MAIN_ENTRY));
        $this->assertStringContainsString('src="/assets/bundle.abc.js"', $service->js(AssetHashService::MAIN_ENTRY));
        $this->assertStringContainsString('integrity="sha384-main"', $service->js(AssetHashService::MAIN_ENTRY));
        expect($service->url('invalid.ts'))->toBe('invalid.ts');
        $map = $service->importMap();
        $this->assertStringContainsString('"react":"/assets/ext-runtime.react.abc.js"', $map);
        $this->assertStringContainsString('"/assets/ext-runtime.react.abc.js":"sha384-react"', $map);
    } finally {
        unlink($root.'/'.AssetHashService::MANIFEST_PATH);
        rmdir($root.'/assets');
        rmdir($root);
    }
});
