<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Themes\ThemeControllerTest;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Pterodactyl\Models\Extension;
use Pterodactyl\Models\Setting;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Themes\ThemeRepository;
use Pterodactyl\Services\Themes\ThemeResolver;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;

use function pterodactylTestCase;

uses(AdminApiIntegrationTestCase::class);

beforeEach(function (): void {
    $this->baseDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-admin-themes-'.uniqid();
    config([
        'extensions.enabled' => true,
        'extensions.directory' => $this->baseDirectory.'/extensions',
        'extensions.assets_directory' => $this->baseDirectory.'/assets',
    ]);
    writeTheme('aurora', [
        'accent' => ['type' => 'color', 'label' => 'Accent', 'default' => '#8b5cf6', 'token' => '--accent'],
        'radius' => ['type' => 'number', 'label' => 'Radius', 'default' => 0.5, 'min' => 0, 'max' => 2, 'step' => 0.25, 'unit' => 'rem', 'token' => '--radius'],
        'navigation' => ['type' => 'select', 'label' => 'Navigation', 'options' => ['side' => 'Sidebar', 'top' => 'Top bar'], 'default' => 'side', 'attribute' => 'data-aurora-nav'],
        'compact' => ['type' => 'toggle', 'label' => 'Compact', 'default' => false, 'attribute' => 'data-density'],
        'tagline' => ['type' => 'text', 'label' => 'Tagline', 'maxLength' => 10],
        'logo' => ['type' => 'image', 'label' => 'Logo', 'token' => '--brand-logo'],
    ]);
    writeTheme('nebula', [], enabled: false);
});

afterEach(function (): void {
    app(ExtensionRepository::class)->flushDiscovery();
    File::deleteDirectory($this->baseDirectory);
});

/** @param array<string, array<string, mixed>> $settings */
function writeTheme(string $id, array $settings = [], bool $enabled = true): void
{
    (function () use ($id, $settings, $enabled): void {
        $directory = $this->baseDirectory.'/extensions/'.$id;
        File::ensureDirectoryExists($directory);
        File::put($directory.'/extension.json', json_encode(array_filter([
            'id' => $id,
            'type' => 'theme',
            'name' => ucfirst($id),
            'version' => '1.0.0',
            'theme' => array_filter(['settings' => $settings]),
        ]), JSON_THROW_ON_ERROR));
        Extension::query()->create(['identifier' => $id, 'version' => '1.0.0', 'enabled' => $enabled]);
        $this->app->make(ExtensionRepository::class)->flushDiscovery();
    })->call(pterodactylTestCase());
}

/** @return array<string, array<string, mixed>> setting attributes keyed by setting name */
function settingsOf(array $response): array
{
    return array_column(array_column($response['data'], 'attributes'), null, 'key');
}

function png(): string
{
    return (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=', true);
}

test('lists the built-in theme and every installed theme', function (): void {
    $response = $this->getJson(route('api.admin.themes'))->assertOk()
        ->assertJsonPath('object', 'list')
        ->assertJsonPath('meta', ['active' => 'classic', 'preview' => null, 'safe_mode' => false]);
    $themes = array_column(array_column($response->json('data'), 'attributes'), null, 'id');

    expect(array_keys($themes))->toBe(['classic', 'aurora', 'nebula']);
    expect($themes['classic'])->toMatchArray(['builtin' => true, 'active' => true, 'enabled' => true, 'extends' => null, 'problem' => null, 'settings_count' => 0]);
    expect($themes['aurora'])->toMatchArray(['builtin' => false, 'active' => false, 'extends' => 'classic', 'schemes' => ['dark'], 'settings_count' => 6, 'problem' => null]);
    expect($themes['nebula'])->toMatchArray(['enabled' => false, 'problem' => 'Theme "nebula" is not enabled or failed to load.']);
});

test('activates a usable theme and resets to classic', function (): void {
    $this->postJson(route('api.admin.themes.activate', ['theme' => 'aurora']))->assertOk()
        ->assertJsonPath('object', 'theme')
        ->assertJsonPath('attributes.active', true);
    $this->assertDatabaseHas('settings', ['key' => ThemeRepository::SETTING, 'value' => 'aurora']);
    $this->assertDatabaseHas('activity_logs', ['event' => 'admin:theme.activate']);

    $this->postJson(route('api.admin.themes.activate', ['theme' => 'nebula']))->assertUnprocessable()
        ->assertJsonPath('errors.0.detail', 'Theme "nebula" is not enabled or failed to load.');
    $this->postJson(route('api.admin.themes.activate', ['theme' => 'missing']))->assertNotFound();
    expect(Setting::fetch(ThemeRepository::SETTING))->toBe('aurora');

    $this->postJson(route('api.admin.themes.reset'))->assertOk()->assertJsonPath('attributes.id', 'classic');
    $this->assertDatabaseMissing('settings', ['key' => ThemeRepository::SETTING]);
    $this->assertDatabaseHas('activity_logs', ['event' => 'admin:theme.reset']);
});

test('previews a theme for this session only', function (): void {
    // The browser's session cookie makes these requests stateful, as the panel's own are.
    $this->withCredentials()->withCookie(config()->string('session.cookie'), str_repeat('a', 40));

    $this->postJson(route('api.admin.themes.preview', ['theme' => 'aurora']))->assertOk()->assertJsonPath('attributes.previewing', true);
    expect(session(ThemeResolver::PREVIEW))->toBe('aurora');
    $this->getJson(route('api.admin.themes'))->assertJsonPath('meta.preview', 'aurora')->assertJsonPath('meta.active', 'classic');
    $this->assertDatabaseMissing('settings', ['key' => ThemeRepository::SETTING]);

    $this->postJson(route('api.admin.themes.preview', ['theme' => 'nebula']))->assertUnprocessable();
    $this->deleteJson(route('api.admin.themes.preview.stop'))->assertNoContent();
    expect(session()->has(ThemeResolver::PREVIEW))->toBeFalse();
});

test('returns each setting with its value and what it writes', function (): void {
    $settings = settingsOf($this->getJson(route('api.admin.themes.settings', ['theme' => 'aurora']))->assertOk()->json());

    expect($settings['accent'])->toMatchArray(['type' => 'color', 'default' => '#8b5cf6', 'value' => '#8b5cf6', 'token' => '--accent', 'attribute' => null]);
    expect($settings['radius'])->toMatchArray(['min' => 0, 'max' => 2, 'step' => 0.25, 'unit' => 'rem']);
    expect($settings['navigation']['options'])->toBe([['value' => 'side', 'label' => 'Sidebar'], ['value' => 'top', 'label' => 'Top bar']]);
    expect($settings['tagline']['max_length'])->toBe(10);
    expect($settings['logo'])->toMatchArray(['value' => null, 'max_kilobytes' => 1024])->and($settings['logo']['accept'])->toContain('image/svg+xml');
    $this->getJson(route('api.admin.themes.settings', ['theme' => 'classic']))->assertOk()->assertJsonPath('data', []);
});

test('saves settings that pass their type and restores defaults from null', function (): void {
    $route = route('api.admin.themes.settings.update', ['theme' => 'aurora']);
    $saved = settingsOf($this->patchJson($route, ['settings' => ['accent' => '#ABCDEF', 'radius' => 1.25, 'navigation' => 'top', 'compact' => true, 'tagline' => 'Hello']])->assertOk()->json());

    expect(array_column($saved, 'value', 'key'))->toMatchArray(['accent' => '#abcdef', 'radius' => 1.25, 'navigation' => 'top', 'compact' => true, 'tagline' => 'Hello']);
    $this->assertDatabaseHas('extension_settings', ['extension' => 'aurora', 'key' => 'theme.navigation', 'value' => '"top"']);
    $this->assertDatabaseHas('activity_logs', ['event' => 'admin:theme.settings']);

    $reset = settingsOf($this->patchJson($route, ['settings' => ['accent' => null, 'navigation' => null]])->assertOk()->json());
    expect($reset['accent']['value'])->toBe('#8b5cf6')->and($reset['navigation']['value'])->toBe('side')->and($reset['radius']['value'])->toBe(1.25);
});

test('a child theme that overrides a setting lists and saves it with the declaration it changes', function (): void {
    File::ensureDirectoryExists($this->baseDirectory.'/extensions/midnight');
    File::put($this->baseDirectory.'/extensions/midnight/extension.json', json_encode([
        'id' => 'midnight', 'type' => 'theme', 'name' => 'Midnight', 'version' => '1.0.0',
        'theme' => ['extends' => 'aurora', 'settings' => ['accent' => ['default' => '#ff4fd8']]],
    ], JSON_THROW_ON_ERROR));
    Extension::query()->create(['identifier' => 'midnight', 'version' => '1.0.0', 'enabled' => true]);
    app(ExtensionRepository::class)->flushDiscovery();
    $route = route('api.admin.themes.settings.update', ['theme' => 'midnight']);

    $settings = settingsOf($this->getJson(route('api.admin.themes.settings', ['theme' => 'midnight']))->assertOk()->json());
    expect(array_keys($settings))->toBe(['accent'])
        ->and($settings['accent'])->toMatchArray(['type' => 'color', 'label' => 'Accent', 'default' => '#ff4fd8', 'value' => '#ff4fd8', 'token' => '--accent']);

    $this->patchJson($route, ['settings' => ['accent' => 'red']])->assertUnprocessable();
    expect(settingsOf($this->patchJson($route, ['settings' => ['accent' => '#00ff00']])->assertOk()->json())['accent']['value'])->toBe('#00ff00');
    $this->assertDatabaseHas('extension_settings', ['extension' => 'midnight', 'key' => 'theme.accent', 'value' => '"#00ff00"']);
});

test('rejects setting values that do not fit their type', function (array $settings, string $field): void {
    $this->patchJson(route('api.admin.themes.settings.update', ['theme' => 'aurora']), ['settings' => $settings])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', $field);

    $this->assertDatabaseMissing('extension_settings', ['extension' => 'aurora']);
})->with([
    'a colour name' => [['accent' => 'red'], 'accent'],
    'a colour with css' => [['accent' => '#fff;}body{display:none'], 'accent'],
    'an unknown option' => [['navigation' => 'bottom'], 'navigation'],
    'a number above max' => [['radius' => 3], 'radius'],
    'a number off step' => [['radius' => 0.3], 'radius'],
    'a number as text' => [['radius' => '1'], 'radius'],
    'a toggle as text' => [['compact' => 'yes'], 'compact'],
    'text too long' => [['tagline' => 'eleven char'], 'tagline'],
    'an image through the form' => [['logo' => '/extension-files/aurora/x.png'], 'logo'],
]);

test('stores image settings like extension files and clears them', function (): void {
    Storage::fake('local');
    $upload = route('api.admin.themes.settings.image', ['theme' => 'aurora', 'setting' => 'logo']);

    $url = settingsOf($this->post($upload, ['file' => UploadedFile::fake()->createWithContent('logo.php', png())], ['Accept' => 'application/json'])->assertOk()->json())['logo']['value'];
    expect($url)->toStartWith('/extension-files/aurora/')->toEndWith('.png');
    Storage::disk('local')->assertExists(mb_ltrim((string) $url, '/'));
    $this->assertDatabaseHas('activity_logs', ['event' => 'admin:theme.image']);

    $this->post(route('api.admin.themes.settings.image', ['theme' => 'aurora', 'setting' => 'accent']), ['file' => UploadedFile::fake()->createWithContent('logo.png', png())], ['Accept' => 'application/json'])->assertNotFound();
    $this->post($upload, ['file' => UploadedFile::fake()->createWithContent('logo.png', '<?php echo 1;')], ['Accept' => 'application/json'])->assertUnprocessable();

    expect(settingsOf($this->deleteJson(route('api.admin.themes.settings.image.clear', ['theme' => 'aurora', 'setting' => 'logo']))->assertOk()->json())['logo']['value'])->toBeNull();
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

test('non admins cannot see or change themes', function (string $method, string $route, array $parameters): void {
    $this->actingAsNonAdmin();

    $this->assertAccessDeniedJson($this->{$method}(route($route, $parameters)));
})->with([
    ['getJson', 'api.admin.themes', []],
    ['getJson', 'api.admin.themes.settings', ['theme' => 'aurora']],
    ['postJson', 'api.admin.themes.activate', ['theme' => 'aurora']],
    ['postJson', 'api.admin.themes.reset', []],
    ['postJson', 'api.admin.themes.preview', ['theme' => 'aurora']],
    ['deleteJson', 'api.admin.themes.preview.stop', []],
    ['patchJson', 'api.admin.themes.settings.update', ['theme' => 'aurora']],
    ['postJson', 'api.admin.themes.settings.image', ['theme' => 'aurora', 'setting' => 'logo']],
    ['deleteJson', 'api.admin.themes.settings.image.clear', ['theme' => 'aurora', 'setting' => 'logo']],
]);
