<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Extensions\ExtensionControllerTest;

use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Pterodactyl\Models\Extension;
use Pterodactyl\Models\ExtensionSetting;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Extensions\ExtensionSettingDefinition;
use Pterodactyl\Services\Extensions\ExtensionSettingFiles;
use Pterodactyl\Services\Extensions\ExtensionSettingsDefinition;
use Pterodactyl\Services\Extensions\ExtensionSettingsRegistry;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;
use ZipArchive;

use function pterodactylTestCase;

uses(AdminApiIntegrationTestCase::class);
beforeEach(function (): void {
    $this->baseDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-admin-extensions-'.uniqid();
    $this->extensionsDirectory = $this->baseDirectory.DIRECTORY_SEPARATOR.'extensions';
    $this->assetsDirectory = $this->baseDirectory.DIRECTORY_SEPARATOR.'assets';
    File::ensureDirectoryExists($this->extensionsDirectory);
    File::ensureDirectoryExists($this->assetsDirectory);
    config(['extensions.enabled' => true, 'extensions.directory' => $this->extensionsDirectory, 'extensions.assets_directory' => $this->assetsDirectory]);
    repository()->flushDiscovery();
});
afterEach(function (): void {
    repository()->flushDiscovery();
    File::deleteDirectory($this->baseDirectory);
});
dataset('extensionEndpointsDataProvider', fn (): array => [['getJson', 'api.admin.extensions'], ['postJson', 'api.admin.extensions.install'], ['getJson', 'api.admin.extensions.settings', ['extension' => 'admin-fixture']], ['patchJson', 'api.admin.extensions.settings.update', ['extension' => 'admin-fixture']], ['postJson', 'api.admin.extensions.settings.file', ['extension' => 'admin-fixture', 'input' => 'logo']], ['deleteJson', 'api.admin.extensions.settings.file.clear', ['extension' => 'admin-fixture', 'input' => 'logo']], ['postJson', 'api.admin.extensions.enable', ['extension' => 'admin-fixture']], ['postJson', 'api.admin.extensions.disable', ['extension' => 'admin-fixture']], ['delete', 'api.admin.extensions.delete', ['extension' => 'admin-fixture']]]);
test('list extensions returns discovered extensions and metadata', function (): void {
    writeExtension('admin-fixture');
    Extension::query()->create(['identifier' => 'admin-fixture', 'version' => '1.0.0', 'enabled' => true]);
    $response = $this->getJson(route('api.admin.extensions'));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonPath('data.0.id', 'admin-fixture');
    $response->assertJsonPath('data.0.name', 'Admin Fixture');
    $response->assertJsonPath('data.0.enabled', true);
    $response->assertJsonPath('data.0.state', 'enabled');
    $response->assertJsonPath('meta.enabled', true);
    $response->assertJsonPath('meta.directory', $this->extensionsDirectory);
});
test('enable disable and remove extension', function (): void {
    writeExtension('admin-fixture');
    $enable = $this->postJson(route('api.admin.extensions.enable', ['extension' => 'admin-fixture']));
    $enable->assertStatus(Response::HTTP_OK);
    $enable->assertJsonPath('data.id', 'admin-fixture');
    $enable->assertJsonPath('data.enabled', true);
    $this->assertDatabaseHas('extensions', ['identifier' => 'admin-fixture', 'enabled' => true]);
    $disable = $this->postJson(route('api.admin.extensions.disable', ['extension' => 'admin-fixture']));
    $disable->assertStatus(Response::HTTP_OK);
    $disable->assertJsonPath('data.enabled', false);
    $this->assertDatabaseHas('extensions', ['identifier' => 'admin-fixture', 'enabled' => false]);
    $remove = $this->delete(route('api.admin.extensions.delete', ['extension' => 'admin-fixture']));
    $remove->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDirectoryDoesNotExist($this->extensionsDirectory.DIRECTORY_SEPARATOR.'admin-fixture');
    $this->assertDatabaseMissing('extensions', ['identifier' => 'admin-fixture']);
});
test('settings for extension without a definition', function (): void {
    writeExtension('admin-fixture');
    $response = $this->getJson(route('api.admin.extensions.settings', ['extension' => 'admin-fixture']));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonPath('data.registered', false);
    $response->assertJsonPath('data.schema', []);

    $update = $this->patchJson(route('api.admin.extensions.settings.update', ['extension' => 'admin-fixture']), ['settings' => ['anything' => 'value']]);
    $update->assertStatus(Response::HTTP_NOT_FOUND);
});
test('settings update keeps omitted required settings', function (): void {
    $settings = registerSettings();
    $settings->update(['api_key' => 'stored-secret']);

    $this->patchJson(route('api.admin.extensions.settings.update', ['extension' => 'admin-fixture']), ['settings' => ['limit' => 7]])
        ->assertOk()
        ->assertJsonPath('data.registered', true);

    expect($settings->get('limit'))->toBe(7)
        ->and($settings->get('api_key'))->toBe('stored-secret')
        ->and($settings->get('public_note'))->toBe('note');
});
test('settings update still validates submitted required settings', function (): void {
    $settings = registerSettings();

    $response = $this->patchJson(route('api.admin.extensions.settings.update', ['extension' => 'admin-fixture']), ['settings' => ['public_note' => '', 'limit' => 'many']])
        ->assertUnprocessable();

    expect(collect($response->json('errors'))->pluck('meta.source_field')->unique()->sort()->values()->all())->toBe(['limit', 'public_note'])
        ->and($settings->get('public_note'))->toBe('note')
        ->and($settings->get('limit'))->toBe(10);
});
test('settings update saves settings without validation rules', function (): void {
    $settings = registerSettings();

    $this->patchJson(route('api.admin.extensions.settings.update', ['extension' => 'admin-fixture']), ['settings' => ['greeting' => 'changed greeting']])
        ->assertOk();

    expect($settings->get('greeting'))->toBe('changed greeting');
});

test('blank secret updates preserve credentials and save other settings', function (?string $value): void {
    $settings = registerSettings();
    $settings->update(['api_key' => 'stored-secret']);

    $this->patchJson(route('api.admin.extensions.settings.update', ['extension' => 'admin-fixture']), ['settings' => ['api_key' => $value, 'limit' => 7]])
        ->assertOk()
        ->assertJsonPath('data.schema.2.value', '********');

    expect($settings->get('api_key'))->toBe('stored-secret');
    expect($settings->get('limit'))->toBe(7);
})->with(['', null, '   ']);

test('secrets of any field type survive a save of the form they were read from', function (): void {
    writeExtension('admin-fixture');
    $settings = new ExtensionSettingsDefinition(repository()->settings('admin-fixture'), [
        ExtensionSettingDefinition::make('region_token', 'region_token', '', ['string'])->secret()->field('text'),
        ExtensionSettingDefinition::make('api_key', 'api_key', '')->field('password'),
        ExtensionSettingDefinition::make('greeting', 'greeting', 'hello'),
    ]);
    $this->app->make(ExtensionSettingsRegistry::class)->register('admin-fixture', $settings);
    $route = route('api.admin.extensions.settings.update', ['extension' => 'admin-fixture']);
    $this->patchJson($route, ['settings' => ['region_token' => 'stored-token', 'api_key' => 'stored-key']])->assertOk();

    // The form sends every value it was given back, masks included.
    $schema = $this->getJson(route('api.admin.extensions.settings', ['extension' => 'admin-fixture']))->assertOk()->json('data.schema');
    $this->patchJson($route, ['settings' => [...array_column($schema, 'value', 'input'), 'greeting' => 'changed']])
        ->assertOk()
        ->assertJsonPath('data.schema.0.value', ExtensionSettingDefinition::MASK)
        ->assertJsonPath('data.schema.1.value', ExtensionSettingDefinition::MASK);

    expect($settings->get('region_token'))->toBe('stored-token')
        ->and($settings->get('api_key'))->toBe('stored-key')
        ->and($settings->get('greeting'))->toBe('changed');
    // A password field is encrypted at rest even without ->secret().
    expect(ExtensionSetting::query()->where('extension', 'admin-fixture')->where('key', 'api_key')->value('value'))->not->toContain('stored-key');
    expect(ExtensionSetting::query()->where('extension', 'admin-fixture')->where('key', 'api_key')->value('is_secret'))->toBeTrue();
});

test('simple settings are validated against their field type', function (array $input): void {
    $settings = registerSettings();

    $response = $this->patchJson(route('api.admin.extensions.settings.update', ['extension' => 'admin-fixture']), ['settings' => $input])
        ->assertUnprocessable();

    expect($response->json('errors.0.meta.source_field'))->toBe(array_key_first($input))
        ->and($settings->get('limit'))->toBe(10)
        ->and($settings->get('greeting'))->toBe('default greeting');
})->with([
    'number as a string' => [['limit' => '7']],
    'text as an object' => [['greeting' => ['nested' => 'value']]],
    'text as a boolean' => [['greeting' => true]],
    'secret as a list' => [['api_key' => ['private-token']]],
]);

test('malformed settings return 422 with a field error', function (mixed $settings): void {
    registerSettings();

    $response = $this->patchJson(route('api.admin.extensions.settings.update', ['extension' => 'admin-fixture']), ['settings' => $settings])
        ->assertUnprocessable();

    expect($response->json('errors.0.meta.source_field'))->toBe('settings');
    $this->assertDatabaseMissing('extension_settings', ['extension' => 'admin-fixture']);
})->with(['scalar' => ['broken'], 'null' => [null], 'numeric keys' => [['value']], 'excessive depth' => [array_reduce(range(1, 11), fn (array $value): array => ['nested' => $value], ['value' => 'too deep'])]]);

test('a settings update requires the settings envelope and accepts an empty partial update', function (): void {
    $settings = registerSettings();
    $settings->update(['api_key' => 'stored-secret']);

    $this->patchJson(route('api.admin.extensions.settings.update', ['extension' => 'admin-fixture']), [])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', 'settings');
    $this->patchJson(route('api.admin.extensions.settings.update', ['extension' => 'admin-fixture']), ['settings' => []])->assertOk();

    expect($settings->get('api_key'))->toBe('stored-secret');
});
test('settings for uninstalled extension returns not found', function (): void {
    $response = $this->getJson(route('api.admin.extensions.settings', ['extension' => 'missing']));
    $response->assertStatus(Response::HTTP_NOT_FOUND);
});
test('install requires extension package', function (): void {
    $response = $this->postJson(route('api.admin.extensions.install'), []);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonStructure(['errors' => [['code', 'detail', 'meta' => ['source_field', 'rule']]]]);

    $error = collect($response->json('errors'))->firstWhere('meta.source_field', 'package');
    expect($error)->not->toBeNull();
    expect($error['meta']['rule'])->toBe('required');
});
test('install rejects a package with an invalid manifest', function (): void {
    $this->post(route('api.admin.extensions.install'), ['package' => extensionPackage(['id' => 'Bad_Probe', 'name' => 'Bad', 'version' => '1.0.0'])], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'InvalidExtensionException')
        ->assertJsonPath('errors.0.detail', fn (string $detail): bool => str_contains($detail, 'Extension id "Bad_Probe" must match'));

    $this->assertDatabaseMissing('extensions', ['identifier' => 'Bad_Probe']);
});
test('install accepts multipart boolean strings for enable', function (string $enable): void {
    $this->post(route('api.admin.extensions.install'), ['package' => extensionPackage(['id' => 'Bad_Probe', 'name' => 'Bad', 'version' => '1.0.0']), 'enable' => $enable], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'InvalidExtensionException');
})->with(['true', 'false', '1', '0']);
test('install asks before replacing an installed extension and replaces it once confirmed', function (bool $registered): void {
    writeExtension('admin-fixture');
    if ($registered) {
        Extension::query()->create(['identifier' => 'admin-fixture', 'version' => '1.0.0', 'enabled' => true]);
    }

    $package = fn (): UploadedFile => extensionPackage(['id' => 'admin-fixture', 'name' => 'Replacement', 'version' => '2.0.0']);

    $this->post(route('api.admin.extensions.install'), ['package' => $package(), 'enable' => 'false'], ['Accept' => 'application/json'])
        ->assertStatus(Response::HTTP_CONFLICT)
        ->assertJsonPath('errors.0.code', 'ExtensionAlreadyInstalledException')
        ->assertJsonPath('errors.0.detail', 'Extension "admin-fixture" v1.0.0 is already installed; replacing it with v2.0.0 must be confirmed.')
        ->assertJsonPath('errors.0.meta.identifier', 'admin-fixture')
        ->assertJsonPath('errors.0.meta.installed_version', '1.0.0')
        ->assertJsonPath('errors.0.meta.version', '2.0.0')
        ->assertJsonPath('errors.0.meta.enabled', $registered);
    expect(File::json($this->extensionsDirectory.'/admin-fixture/extension.json')['version'])->toBe('1.0.0');

    $this->post(route('api.admin.extensions.install'), ['package' => $package(), 'replace' => 'true'], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.version', '2.0.0')
        ->assertJsonPath('data.enabled', $registered);
    expect(File::json($this->extensionsDirectory.'/admin-fixture/extension.json')['version'])->toBe('2.0.0');
})->with(['enabled record' => [true], 'unregistered folder' => [false]]);
test('install rejects a non boolean replace value', function (): void {
    $response = $this->post(route('api.admin.extensions.install'), ['package' => extensionPackage(['id' => 'admin-fixture', 'name' => 'Fixture', 'version' => '1.0.0']), 'replace' => 'maybe'], ['Accept' => 'application/json']);
    $response->assertUnprocessable();

    expect(collect($response->json('errors'))->firstWhere('meta.source_field', 'replace')['meta']['rule'] ?? null)->toBe('boolean');
});
test('install rejects a non boolean enable value', function (): void {
    $response = $this->post(route('api.admin.extensions.install'), ['package' => extensionPackage(['id' => 'admin-fixture', 'name' => 'Fixture', 'version' => '1.0.0']), 'enable' => 'maybe'], ['Accept' => 'application/json']);
    $response->assertUnprocessable();

    $error = collect($response->json('errors'))->firstWhere('meta.source_field', 'enable');
    expect($error)->not->toBeNull();
    expect($error['meta']['rule'])->toBe('boolean');
});
test('non admin forbidden', function (string $method, string $routeName, array $parameters = []): void {
    writeExtension('admin-fixture');
    $this->actingAsNonAdmin();
    $response = $this->{$method}(route($routeName, $parameters));
    $this->assertAccessDeniedJson($response);
})->with('extensionEndpointsDataProvider');
function repository(): ExtensionRepository
{
    return (fn () => $this->app->make(ExtensionRepository::class))->call(pterodactylTestCase());
}

function registerSettings(): ExtensionSettingsDefinition
{
    return (function (): ExtensionSettingsDefinition {
        writeExtension('admin-fixture');
        $definition = new ExtensionSettingsDefinition(repository()->settings('admin-fixture'), [
            ExtensionSettingDefinition::make('limit', 'limit', 10, ['required', 'integer'])->field('number'),
            ExtensionSettingDefinition::make('public_note', 'public_note', 'note', ['required', 'string']),
            ExtensionSettingDefinition::make('api_key', 'api_key', '', ['required', 'string'])->secret(),
            ExtensionSettingDefinition::make('greeting', 'greeting', 'default greeting'),
            ExtensionSettingDefinition::make('accent', 'accent', '#3b82f6')->color(),
            ExtensionSettingDefinition::make('links', 'links', [])->list(['max:20'], 2),
            ExtensionSettingDefinition::make('tags', 'tags', [])->multiselect([['value' => 'survival', 'label' => 'Survival'], ['value' => 'creative', 'label' => 'Creative']]),
            ExtensionSettingDefinition::make('logo', 'logo', null)->file(maxKilobytes: 8)->frontend()->public(),
        ]);
        $this->app->make(ExtensionSettingsRegistry::class)->register('admin-fixture', $definition);

        return $definition;
    })->call(pterodactylTestCase());
}

/**
 * @param  array<string, string>  $manifest
 */
function extensionPackage(array $manifest): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'ptero-package-').'.zip';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('extension.json', json_encode($manifest, JSON_THROW_ON_ERROR));
    $zip->close();

    return new UploadedFile($path, 'bad-probe.pteroext', 'application/zip', null, true);
}

function writeExtension(string $identifier): void
{
    (function () use ($identifier): void {
        $path = $this->extensionsDirectory.DIRECTORY_SEPARATOR.$identifier;
        File::ensureDirectoryExists($path);
        File::put($path.DIRECTORY_SEPARATOR.'extension.json', json_encode(['id' => $identifier, 'name' => 'Admin Fixture', 'version' => '1.0.0'], JSON_THROW_ON_ERROR));
        repository()->flushDiscovery();
    })->call(pterodactylTestCase());
}

test('secret settings are masked by the admin endpoint and encrypted on disk', function (): void {
    $settings = registerSettings();
    $this->patchJson(route('api.admin.extensions.settings.update', ['extension' => 'admin-fixture']), ['settings' => ['api_key' => 'private-token']])
        ->assertOk()
        ->assertJsonPath('data.schema.2.value', '********');
    $this->getJson(route('api.admin.extensions.settings', ['extension' => 'admin-fixture']))
        ->assertOk()
        ->assertJsonPath('data.schema.2.value', '********');
    expect($settings->get('api_key'))->toBe('private-token');
    expect(ExtensionSetting::query()->where('extension', 'admin-fixture')->where('key', 'api_key')->value('value'))->not->toContain('private-token');
});

function settingFile(string $bytes, string $name = 'logo.png', string $mime = 'image/png'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'ptero-setting-file-');
    file_put_contents($path, $bytes);

    return new UploadedFile($path, $name, $mime, null, true);
}

function png(): string
{
    return (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=', true);
}

test('typed settings are validated and normalized by the update endpoint', function (): void {
    $settings = registerSettings();
    $route = route('api.admin.extensions.settings.update', ['extension' => 'admin-fixture']);

    $this->patchJson($route, ['settings' => ['accent' => 'red; background: url(//evil.example)']])->assertUnprocessable();
    $this->patchJson($route, ['settings' => ['links' => ['one', 'two', 'three']]])->assertUnprocessable();
    $this->patchJson($route, ['settings' => ['tags' => ['survival', 'hardcore']]])->assertUnprocessable();
    $this->patchJson($route, ['settings' => ['logo' => 'ffffffffffffffffffffffffffffffffffffffff.png']])->assertUnprocessable();

    $this->patchJson($route, ['settings' => ['accent' => '#ABCDEF', 'links' => ['Docs | /docs'], 'tags' => ['creative', 'survival']]])
        ->assertOk()
        ->assertJsonPath('data.schema.4.value', '#abcdef')
        ->assertJsonPath('data.schema.5.value', ['Docs | /docs'])
        ->assertJsonPath('data.schema.5.constraints.max_items', 2)
        ->assertJsonPath('data.schema.6.value', ['survival', 'creative'])
        ->assertJsonPath('data.schema.7.field', 'file')
        ->assertJsonPath('data.schema.7.visibility', 'public')
        ->assertJsonPath('data.schema.7.constraints.accept', ExtensionSettingFiles::IMAGES);
    expect($settings->get('accent'))->toBe('#abcdef');
});
test('file settings are uploaded replaced served and cleared', function (): void {
    Storage::fake('local');
    $settings = registerSettings();
    $route = route('api.admin.extensions.settings.file', ['extension' => 'admin-fixture', 'input' => 'logo']);

    $first = $this->post($route, ['file' => settingFile(png(), 'evil.php', 'application/x-php')], ['Accept' => 'application/json'])
        ->assertOk()
        ->json('data.schema.7.value');
    expect($first)->toStartWith('/extension-files/admin-fixture/')->toEndWith('.png');
    expect($settings->get('logo'))->toBe($first);

    // Served without a session: public, immutable, typed from the stored name and never sniffed.
    $this->app['auth']->forgetGuards();
    $served = $this->get($first)->assertOk();
    expect($served->getContent())->toBe(png());
    expect($served->headers->get('Content-Type'))->toBe('image/png');
    expect($served->headers->get('X-Content-Type-Options'))->toBe('nosniff');
    expect($served->headers->get('Cache-Control'))->toContain('public')->toContain('immutable');
    expect($served->headers->get('Content-Security-Policy'))->toContain('sandbox');
    expect($served->headers->getCookies())->toBe([]);
    $this->get('/extension-files/admin-fixture/'.str_repeat('0', 40).'.png')->assertNotFound();
    $this->get('/extension-files/other-extension/'.basename((string) $first))->assertNotFound();

    $this->actingAs($this->getAdminUser());
    $second = $this->post($route, ['file' => settingFile(png())], ['Accept' => 'application/json'])->assertOk()->json('data.schema.7.value');
    expect($second)->not->toBe($first);
    Storage::disk('local')->assertMissing(mb_ltrim((string) $first, '/'));
    Storage::disk('local')->assertExists(mb_ltrim((string) $second, '/'));

    $this->deleteJson(route('api.admin.extensions.settings.file.clear', ['extension' => 'admin-fixture', 'input' => 'logo']))
        ->assertOk()
        ->assertJsonPath('data.schema.7.value', null);
    expect(Storage::disk('local')->allFiles())->toBe([]);
    $this->assertDatabaseMissing('extension_settings', ['extension' => 'admin-fixture', 'key' => 'logo']);
});
test('file uploads are rejected by content size and target', function (): void {
    Storage::fake('local');
    registerSettings();
    $route = route('api.admin.extensions.settings.file', ['extension' => 'admin-fixture', 'input' => 'logo']);
    $headers = ['Accept' => 'application/json'];

    $this->post($route, ['file' => settingFile('<?php echo 1;')], $headers)->assertUnprocessable();
    $this->post($route, ['file' => settingFile('<svg xmlns="http://www.w3.org/2000/svg"></svg>', 'logo.svg', 'image/svg+xml')], $headers)->assertUnprocessable();
    $this->post($route, ['file' => settingFile(png().str_repeat("\0", 9 * 1024))], $headers)->assertUnprocessable();
    $this->post($route, [], $headers)->assertUnprocessable();
    $this->post(route('api.admin.extensions.settings.file', ['extension' => 'admin-fixture', 'input' => 'greeting']), ['file' => settingFile(png())], $headers)->assertNotFound();
    $this->post(route('api.admin.extensions.settings.file', ['extension' => 'missing', 'input' => 'logo']), ['file' => settingFile(png())], $headers)->assertNotFound();
    expect(Storage::disk('local')->allFiles())->toBe([]);
});
test('removing an extension deletes its uploaded files, settings, secrets and subuser grants', function (): void {
    Storage::fake('local');
    registerSettings();
    Extension::query()->create(['identifier' => 'admin-fixture', 'version' => '1.0.0', 'enabled' => false]);
    $this->post(route('api.admin.extensions.settings.file', ['extension' => 'admin-fixture', 'input' => 'logo']), ['file' => settingFile(png())], ['Accept' => 'application/json'])->assertOk();
    $this->patchJson(route('api.admin.extensions.settings.update', ['extension' => 'admin-fixture']), ['settings' => ['greeting' => 'changed', 'api_key' => 'private-token']])->assertOk();
    $subuser = Subuser::factory()->create(['permissions' => ['control.console', 'ext.admin-fixture.view', 'ext.admin-fixture-two.view']]);
    repository()->settings('admin-fixture')->forUser($subuser->user)->set('theme', 'dark');
    expect(Storage::disk('local')->allFiles())->toHaveCount(1);

    $this->delete(route('api.admin.extensions.delete', ['extension' => 'admin-fixture']))->assertStatus(Response::HTTP_NO_CONTENT);
    expect(Storage::disk('local')->allFiles())->toBe([]);
    $this->assertDatabaseMissing('extension_settings', ['extension' => 'admin-fixture']);
    expect($subuser->refresh()->permissions)->toBe(['control.console', 'ext.admin-fixture-two.view']);
});
