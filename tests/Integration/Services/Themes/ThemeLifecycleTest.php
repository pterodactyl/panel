<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Themes\ThemeLifecycleTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Pterodactyl\Contracts\Extensions\InstallsExtensions;
use Pterodactyl\Contracts\Extensions\RemovesExtensions;
use Pterodactyl\Contracts\Extensions\SetsExtensionEnabled;
use Pterodactyl\Contracts\Themes\ActivatesThemes;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Services\Extensions\ExtensionAssetPublisher;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Themes\ThemeRepository;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

use function pterodactylTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    $this->baseDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-theme-lifecycle-'.uniqid();
    config([
        'extensions.enabled' => true,
        'extensions.directory' => $this->baseDirectory.'/installed',
        'extensions.assets_directory' => $this->baseDirectory.'/assets',
    ]);
    File::ensureDirectoryExists($this->baseDirectory.'/installed');
    app(ExtensionRepository::class)->flushDiscovery();
});

afterEach(function (): void {
    app(ExtensionRepository::class)->flushDiscovery();
    File::deleteDirectory($this->baseDirectory);
});

/**
 * A built theme package: a stylesheet-only theme unless files say otherwise.
 *
 * @param  array<string, mixed>  $theme
 * @param  array<string, string>  $files  dist files keyed by name
 */
function themePackage(string $id, array $theme = [], array $files = []): string
{
    return (function () use ($id, $theme, $files): string {
        $path = $this->baseDirectory.'/source/'.$id;
        File::ensureDirectoryExists($path.'/dist');
        File::put($path.'/extension.json', json_encode([
            'id' => $id,
            'type' => 'theme',
            'name' => ucfirst($id),
            'version' => '1.0.0',
            'theme' => ['stylesheet' => 'dist/theme.css', ...$theme],
        ], JSON_THROW_ON_ERROR));
        foreach (['theme.css' => ':root{--accent:red}', ...$files] as $name => $contents) {
            File::put($path.'/dist/'.$name, $contents);
        }

        return $path;
    })->call(pterodactylTestCase());
}

test('a stylesheet-only theme publishes, activates, lists and resets from the console', function (): void {
    app(InstallsExtensions::class)->install(themePackage('aurora'), enable: true);

    expect(app(ExtensionAssetPublisher::class)->currentVersion('aurora'))->not->toBeNull();
    $this->artisan('p:theme:activate', ['id' => 'aurora'])->expectsOutputToContain('Theme "aurora" is now active.')->assertSuccessful();
    expect(app(ThemeRepository::class)->active())->toBe('aurora');

    $this->artisan('p:theme:list')->expectsTable(['ID', 'Name', 'Extends', 'State'], [
        ['classic', 'Classic', '', 'built in'],
        ['aurora', 'Aurora', 'classic', 'active'],
    ])->assertSuccessful();

    $this->artisan('p:theme:activate', ['id' => 'missing'])->expectsOutputToContain('"missing" is not an installed theme.')->assertFailed();
    $this->artisan('p:theme:reset')->assertSuccessful();
    expect(app(ThemeRepository::class)->active())->toBe('classic');
});

test('the active theme cannot be disabled or removed', function (): void {
    app(InstallsExtensions::class)->install(themePackage('aurora'), enable: true);
    app(ActivatesThemes::class)->activate('aurora');

    expect(fn () => app(SetsExtensionEnabled::class)->setEnabled('aurora', false))->toThrow(InvalidExtensionException::class, 'Theme "aurora" is active');
    expect(fn () => app(RemovesExtensions::class)->remove('aurora'))->toThrow(InvalidExtensionException::class, 'Theme "aurora" is active');

    app(ActivatesThemes::class)->activate('classic');
    app(SetsExtensionEnabled::class)->setEnabled('aurora', false);
    $this->assertDatabaseHas('extensions', ['identifier' => 'aurora', 'enabled' => false]);
});

test('a child theme needs its parent enabled, and the chain resolves parent first', function (): void {
    $installer = app(InstallsExtensions::class);
    $installer->install(themePackage('base'));
    $installer->install(themePackage('child', ['extends' => 'base', 'schemes' => ['light']], ['theme.json' => '{"parents":["base"]}']));

    expect(fn () => app(SetsExtensionEnabled::class)->setEnabled('child', true))->toThrow(InvalidExtensionException::class, 'Theme "child" extends "base", which is not an enabled theme.');

    app(SetsExtensionEnabled::class)->setEnabled('base', true);
    app(SetsExtensionEnabled::class)->setEnabled('child', true);
    $themes = app(ThemeRepository::class);

    expect(array_map(fn ($theme): string => $theme->id, $themes->chain('child')))->toBe(['base', 'child']);
    expect($themes->schemes($themes->chain('child')))->toBe(['light']);
    // Without a declaration anywhere in the chain, a theme offers classic's dark scheme only.
    expect($themes->schemes($themes->chain('base')))->toBe(['dark']);
    expect(fn () => app(SetsExtensionEnabled::class)->setEnabled('base', false))->toThrow(InvalidExtensionException::class, 'Disable dependent extension "child" before "base".');
    expect($themes->activationProblem('base'))->toBeNull();
});

test('a child stylesheet must be built over the stylesheets of the themes it extends', function (): void {
    $installer = app(InstallsExtensions::class);
    $installer->install(themePackage('base'), enable: true);
    $unbuilt = themePackage('child', ['extends' => 'base']);

    $this->artisan('p:extension:doctor', ['path' => $unbuilt])->expectsOutputToContain('was not built over the stylesheet of "base"')->assertFailed();
    expect(fn () => $installer->install($unbuilt, enable: true))->toThrow(InvalidExtensionException::class, 'Theme "child" extends "base", but its stylesheet was not built over the stylesheet of "base".');

    $built = themePackage('child', ['extends' => 'base'], ['theme.json' => '{"parents":["base"]}']);
    $this->artisan('p:extension:doctor', ['path' => $built])->assertSuccessful();
    $installer->install($built, enable: true);
    expect(app(ThemeRepository::class)->activationProblem('child'))->toBeNull();
});

test('a child theme setting replaces the setting of the same name of its parent', function (): void {
    $installer = app(InstallsExtensions::class);
    $installer->install(themePackage('base', ['settings' => [
        'accent' => ['type' => 'color', 'label' => 'Accent', 'default' => '#111111', 'token' => '--base-accent'],
        'density' => ['type' => 'select', 'label' => 'Density', 'options' => ['cosy' => 'Cosy', 'compact' => 'Compact'], 'default' => 'cosy', 'attribute' => 'data-density'],
    ]]), enable: true);
    $child = themePackage('child', ['extends' => 'base', 'settings' => [
        'accent' => ['type' => 'color', 'label' => 'Accent', 'default' => '#ff4fd8', 'token' => '--base-accent'],
        'stars' => ['type' => 'toggle', 'label' => 'Stars', 'default' => true],
    ]], ['theme.json' => '{"parents":["base"]}']);
    $installer->install($child, enable: true);
    app(ActivatesThemes::class)->activate('child');

    $this->get('/auth/login')->assertOk()->assertSee('--base-accent:#ff4fd8', false)->assertSee('data-density="cosy"', false);

    $this->artisan('p:extension:types', ['path' => $child])->assertSuccessful();
    expect(File::get($child.'/src/theme-types.ts'))->toContain(<<<'TS'
        export interface ThemeSettings {
            readonly "accent": string;
            readonly "density": "cosy" | "compact";
            readonly "stars": boolean;
        }
        TS);
});

test('an incompatible active theme is reported and renders as classic', function (): void {
    app(InstallsExtensions::class)->install(themePackage('aurora'), enable: true);
    app(ActivatesThemes::class)->activate('aurora');
    File::put($this->baseDirectory.'/installed/aurora/extension.json', json_encode([
        'id' => 'aurora', 'type' => 'theme', 'name' => 'Aurora', 'version' => '1.0.0', 'requires' => ['panel' => '^99.0'], 'theme' => ['stylesheet' => 'dist/theme.css'],
    ], JSON_THROW_ON_ERROR));
    app(ExtensionRepository::class)->flushDiscovery();

    expect(app(ThemeRepository::class)->activationProblem('aurora'))->toBe('Theme "aurora" is not enabled or failed to load.');
    $this->assertDatabaseHas('extensions', ['identifier' => 'aurora', 'error_phase' => 'compatibility']);
    $this->get('/auth/login')->assertOk()->assertSee('data-theme="classic"', false);
});

test('a theme may load fonts and images from other origins', function (): void {
    $path = themePackage('aurora', files: [
        'theme.css' => '@import url("https://fonts.example.com/inter.css");@font-face{src:url(//cdn.example.com/a.woff2)}',
    ]);

    $this->artisan('p:extension:doctor', ['path' => $path])->assertSuccessful();
    app(InstallsExtensions::class)->install($path, enable: true);

    app(ExtensionRepository::class)->flushDiscovery();

    expect(app(ExtensionRepository::class)->enabled()->has('aurora'))->toBeTrue();
});

test('types gives a theme typed settings for useThemeSettings', function (): void {
    $path = themePackage('aurora', [
        'settings' => [
            'accent' => ['type' => 'color', 'label' => 'Accent', 'default' => '#8b5cf6', 'token' => '--accent'],
            'navigation' => ['type' => 'select', 'label' => 'Navigation', 'options' => ['side' => 'Sidebar', 'top' => 'Top bar'], 'default' => 'side'],
            'compact' => ['type' => 'toggle', 'label' => 'Compact', 'default' => false],
            'radius' => ['type' => 'number', 'label' => 'Radius', 'default' => 0.5],
            'logo' => ['type' => 'image', 'label' => 'Logo'],
        ],
    ]);

    $this->artisan('p:extension:types', ['path' => $path])->assertSuccessful();

    expect(File::get($path.'/src/theme-types.ts'))->toContain(<<<'TS'
        export interface ThemeSettings {
            readonly "accent": string;
            readonly "navigation": "side" | "top";
            readonly "compact": boolean;
            readonly "radius": number;
            readonly "logo": string | null;
        }
        TS);
});
