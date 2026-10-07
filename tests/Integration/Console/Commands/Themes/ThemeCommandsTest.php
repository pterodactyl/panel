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
    File::put($directory.'/themes/example/assets/logo.txt', 'new asset');
    File::put(public_path('assets/theme.css'), 'original tokens');
    File::put(public_path('assets/theme/logo.txt'), 'original asset');
    $filesystem = File::getFacadeRoot();
    File::partialMock()->shouldReceive('copy')->andReturnUsing(static fn (string $from, string $to): bool => ! str_ends_with($from, 'logo.txt') && $filesystem->copy($from, $to));

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

test('theme assets are published only when every file is a regular browser asset', function (string $problem, string $message): void {
    $directory = sys_get_temp_dir().'/panel-theme-'.Str::random(12);
    $originalPublicPath = $this->app->publicPath();
    $this->app->usePublicPath($directory.'/public');
    config()->set('extensions.themes_directory', $directory.'/themes');
    $theme = $directory.'/themes/example';
    File::ensureDirectoryExists($theme.'/assets');
    File::ensureDirectoryExists(public_path('assets/theme'));
    File::put($theme.'/theme.json', '{"id":"example"}');
    File::put($theme.'/tokens.css', 'new tokens');
    File::put($theme.'/assets/logo.txt', 'new asset');
    File::put($directory.'/secret.env', 'APP_KEY=secret');
    File::put(public_path('assets/theme.css'), 'original tokens');
    File::put(public_path('assets/theme/logo.txt'), 'original asset');
    match ($problem) {
        'linked file' => symlink($directory.'/secret.env', $theme.'/assets/secret.txt'),
        'linked folder' => File::deleteDirectory($theme.'/assets') && symlink($directory, $theme.'/assets'),
        'linked tokens' => File::delete($theme.'/tokens.css') && symlink($directory.'/secret.env', $theme.'/tokens.css'),
        'script' => File::put($theme.'/assets/shell.php', '<?php echo 1;'),
        'dotfile' => File::put($theme.'/assets/.htaccess', 'Options +ExecCGI'),
    };

    try {
        expect(fn () => $this->app->make(AppliesThemes::class)->apply('example'))
            ->toThrow(InvalidExtensionException::class, $message);
        expect(File::get(public_path('assets/theme.css')))->toBe('original tokens');
        expect(File::allFiles(public_path('assets/theme')))->toHaveCount(1);
        expect(File::get(public_path('assets/theme/logo.txt')))->toBe('original asset');
        expect(glob(public_path('assets/.staging-*')))->toBeEmpty();
    } finally {
        $this->app->usePublicPath($originalPublicPath);
        File::deleteDirectory($directory);
    }
})->with([
    'linked file' => ['linked file', 'Theme "example" ships assets/secret.txt, which is a symbolic link'],
    'linked folder' => ['linked folder', 'Theme "example" ships assets, which is a symbolic link'],
    'linked tokens' => ['linked tokens', 'Theme "example" ships tokens.css as a symbolic link'],
    'script' => ['script', 'Theme "example" ships assets/shell.php, which is not a browser asset'],
    'dotfile' => ['dotfile', 'Theme "example" ships assets/.htaccess, which is a dotfile'],
]);

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
