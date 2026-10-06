<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Http\ExtensionFrontendConfigTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Pterodactyl\Models\Extension;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Extensions\ExtensionSettingDefinition;
use Pterodactyl\Services\Extensions\ExtensionSettingsDefinition;
use Pterodactyl\Services\Extensions\ExtensionSettingsRegistry;
use Pterodactyl\Tests\Integration\Http\HttpTestCase;

uses(HttpTestCase::class, DatabaseTransactions::class);
beforeEach(function (): void {
    $this->baseDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-frontend-config-'.uniqid();
    $extensions = $this->baseDirectory.DIRECTORY_SEPARATOR.'extensions';
    File::ensureDirectoryExists($extensions.DIRECTORY_SEPARATOR.'config-probe');
    File::put($extensions.DIRECTORY_SEPARATOR.'config-probe'.DIRECTORY_SEPARATOR.'extension.json', json_encode([
        'id' => 'config-probe',
        'name' => 'Config Probe',
        'version' => '1.0.0',
        'ui' => ['entry' => 'dist/client.js'],
    ], JSON_THROW_ON_ERROR));
    config(['extensions.enabled' => true, 'extensions.directory' => $extensions, 'extensions.assets_directory' => $this->baseDirectory.DIRECTORY_SEPARATOR.'assets']);
    Extension::query()->create(['identifier' => 'config-probe', 'version' => '1.0.0', 'enabled' => true]);

    $repository = $this->app->make(ExtensionRepository::class);
    $repository->flushDiscovery();
    $this->app->make(ExtensionSettingsRegistry::class)->register('config-probe', new ExtensionSettingsDefinition($repository->settings('config-probe'), [
        ExtensionSettingDefinition::make('public_note', 'public_note', 'frontend-config-marker', ['required', 'string'])->frontend(),
        ExtensionSettingDefinition::make('registration', 'registration', 'guest-config-marker', ['required', 'string'])->frontend()->public(),
        ExtensionSettingDefinition::make('accent', 'accent', '#3b82f6')->color()->frontend()->public(),
        ExtensionSettingDefinition::make('api_token', 'api_token', 'secret-config-marker')->secret(),
    ]));
});
afterEach(function (): void {
    $this->app->make(ExtensionRepository::class)->flushDiscovery();
    File::deleteDirectory($this->baseDirectory);
});
test('guests load extension bundles with only their public frontend settings', function (string $path): void {
    $this->app->make(ExtensionRepository::class)->settings('config-probe')->set('accent', '#abcdef');
    $this->get($path)->assertOk()
        ->assertSee('"id":"config-probe"', false)
        ->assertSee('"config":{"registration":"guest-config-marker","accent":"#abcdef"}', false)
        ->assertDontSee('frontend-config-marker', false)
        ->assertDontSee('secret-config-marker', false);
})->with(['/auth/login', '/']);
test('setting values cannot change how the inline bootstrap script is parsed', function (): void {
    $hostile = '</script><!--<script>&\'"';
    $this->app->make(ExtensionRepository::class)->settings('config-probe')->set('registration', $hostile);

    $html = (string) $this->get('/auth/login')->assertOk()->assertSee('"id":"config-probe"', false)->getContent();

    expect($html)->not->toContain('<!--<script')->not->toContain('</script><!--')
        ->toContain('"registration":'.json_encode($hostile, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR));
});
test('guests of an extension without public settings receive an empty config', function (): void {
    $repository = $this->app->make(ExtensionRepository::class);
    $this->app->make(ExtensionSettingsRegistry::class)->register('config-probe', new ExtensionSettingsDefinition($repository->settings('config-probe'), [
        ExtensionSettingDefinition::make('public_note', 'public_note', 'frontend-config-marker', ['required', 'string'])->frontend(),
    ]));
    $this->get('/auth/login')->assertOk()
        ->assertSee('"id":"config-probe"', false)
        ->assertSee('"config":{}', false)
        ->assertDontSee('frontend-config-marker', false);
});
test('signed in users receive extension frontend settings', function (): void {
    $this->actingAs(User::factory()->create())->get('/')->assertOk()
        ->assertSee('"id":"config-probe"', false)
        ->assertSee('"public_note":"frontend-config-marker"', false)
        ->assertSee('"registration":"guest-config-marker"', false)
        ->assertDontSee('secret-config-marker', false);
});
