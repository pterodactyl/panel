<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Console\Commands\Themes\ThemeCommandsTest;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Pterodactyl\Contracts\Themes\AppliesThemes;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use RuntimeException;

uses(IntegrationTestCase::class);

test('theme commands publish and reset tokens and assets through their actions', function (): void {
    $directory = sys_get_temp_dir().'/panel-theme-'.Str::random(12);
    $originalPublicPath = $this->app->publicPath();
    $this->app->usePublicPath($directory.'/public');
    config()->set('extensions.themes_directory', $directory.'/themes');
    File::ensureDirectoryExists($directory.'/themes/example/assets');
    File::put($directory.'/themes/example/theme.json', '{"id":"example","name":"Example","version":"1.0.0"}');
    File::put($directory.'/themes/example/tokens.css', ':root { --accent: red; }');
    File::put($directory.'/themes/example/assets/logo.txt', 'theme asset');

    try {
        $this->artisan('p:theme:apply', ['id' => 'example'])->assertExitCode(0);

        expect(File::get(public_path('assets/theme.css')))->toBe(':root { --accent: red; }');
        expect(File::get(public_path('assets/theme/logo.txt')))->toBe('theme asset');

        $this->artisan('p:theme:apply', ['id' => 'missing'])->assertExitCode(1);
        expect(File::get(public_path('assets/theme.css')))->toBe(':root { --accent: red; }');

        $this->artisan('p:theme:reset')->assertExitCode(0);
        expect(File::exists(public_path('assets/theme.css')))->toBeFalse();
        expect(File::isDirectory(public_path('assets/theme')))->toBeFalse();
    } finally {
        $this->app->usePublicPath($originalPublicPath);
        File::deleteDirectory($directory);
    }
});

test('a theme asset staging failure leaves the active stylesheet and assets intact', function (): void {
    $directory = sys_get_temp_dir().'/panel-theme-'.Str::random(12);
    $originalPublicPath = $this->app->publicPath();
    $this->app->usePublicPath($directory.'/public');
    config()->set('extensions.themes_directory', $directory.'/themes');
    File::ensureDirectoryExists($directory.'/themes/example/assets');
    File::ensureDirectoryExists(public_path('assets/theme'));
    File::put($directory.'/themes/example/theme.json', '{"id":"example"}');
    File::put($directory.'/themes/example/tokens.css', 'new tokens');
    File::put(public_path('assets/theme.css'), 'original tokens');
    File::put(public_path('assets/theme/logo.txt'), 'original asset');
    File::partialMock()->shouldReceive('copyDirectory')->once()->andReturn(false);

    try {
        expect(fn () => $this->app->make(AppliesThemes::class)->apply('example'))
            ->toThrow(InvalidExtensionException::class, 'Unable to stage theme assets');
        expect(File::get(public_path('assets/theme.css')))->toBe('original tokens');
        expect(File::get(public_path('assets/theme/logo.txt')))->toBe('original asset');
        expect(glob(public_path('assets/.staging-*')))->toBeEmpty();
    } finally {
        $this->app->usePublicPath($originalPublicPath);
        File::deleteDirectory($directory);
    }
});

test('a theme activation failure restores both original paths', function (): void {
    $directory = sys_get_temp_dir().'/panel-theme-'.Str::random(12);
    $originalPublicPath = $this->app->publicPath();
    $this->app->usePublicPath($directory.'/public');
    config()->set('extensions.themes_directory', $directory.'/themes');
    File::ensureDirectoryExists($directory.'/themes/example/assets');
    File::ensureDirectoryExists(public_path('assets/theme'));
    File::put($directory.'/themes/example/theme.json', '{"id":"example"}');
    File::put($directory.'/themes/example/tokens.css', 'new tokens');
    File::put($directory.'/themes/example/assets/logo.txt', 'new asset');
    File::put(public_path('assets/theme.css'), 'original tokens');
    File::put(public_path('assets/theme/logo.txt'), 'original asset');
    $filesystem = File::getFacadeRoot();
    $publishedAssets = public_path('assets/theme');
    File::partialMock()->shouldReceive('move')->andReturnUsing(static function (string $from, string $to) use ($filesystem, $publishedAssets): bool {
        if ($to === $publishedAssets && str_contains($from, '.staging-')) {
            return false;
        }

        return $filesystem->move($from, $to);
    });

    try {
        expect(fn () => $this->app->make(AppliesThemes::class)->apply('example'))
            ->toThrow(RuntimeException::class, 'Unable to move');
        expect(File::get(public_path('assets/theme.css')))->toBe('original tokens');
        expect(File::get(public_path('assets/theme/logo.txt')))->toBe('original asset');
        expect(glob(public_path('assets/.previous-*')))->toBeEmpty();
        expect(glob(public_path('assets/.staging-*')))->toBeEmpty();
    } finally {
        $this->app->usePublicPath($originalPublicPath);
        File::deleteDirectory($directory);
    }
});
