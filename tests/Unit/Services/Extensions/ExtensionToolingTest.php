<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Extensions\ExtensionToolingTest;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Mockery;
use Pterodactyl\Contracts\Extensions\InstallsExtensions;
use Pterodactyl\Services\Extensions\ExtensionManifestValidator;
use Pterodactyl\Tests\TestCase;
use ZipArchive;

uses(TestCase::class);
beforeEach(function (): void {
    $this->directory = sys_get_temp_dir().'/ptero-tooling-'.uniqid();
    File::ensureDirectoryExists($this->directory.'/src');
    File::ensureDirectoryExists($this->directory.'/dist/chunks');
    File::put($this->directory.'/extension.json', json_encode(['id' => 'probe', 'name' => 'Probe', 'version' => '1.0.0', 'autoload' => ['Probe\\' => 'src'], 'ui' => ['entry' => 'dist/client.js']], JSON_THROW_ON_ERROR));
    File::put($this->directory.'/dist/client.js', 'export default {};');
    File::put($this->directory.'/dist/chunks/lazy.js', 'export default 1;');
    File::put($this->directory.'/src/Example.php', '<?php');
});
afterEach(function (): void {
    File::deleteDirectory($this->directory);
});

test('doctor reports missing builds and valid runtime packages', function (): void {
    $this->artisan('p:extension:doctor', ['path' => $this->directory])->assertSuccessful();
    File::delete($this->directory.'/dist/client.js');
    $this->artisan('p:extension:doctor', ['path' => $this->directory])->assertFailed();
});

test('doctor reports navigation icons the panel does not ship without failing the package', function (): void {
    File::put($this->directory.'/extension.json', json_encode(['id' => 'probe', 'name' => 'Probe', 'version' => '1.0.0', 'autoload' => ['Probe\\' => 'src'], 'ui' => ['entry' => 'dist/client.js', 'screens' => [
        ['id' => 'known', 'area' => 'admin', 'path' => 'known', 'nav' => ['label' => 'Known', 'icon' => 'life-buoy']],
        ['id' => 'unknown', 'area' => 'admin', 'path' => 'unknown', 'nav' => ['label' => 'Unknown', 'icon' => 'not-an-icon']],
        ['id' => 'plain', 'area' => 'admin', 'path' => 'plain', 'nav' => ['label' => 'Plain']],
    ]]], JSON_THROW_ON_ERROR));

    $this->artisan('p:extension:doctor', ['path' => $this->directory])
        ->expectsOutputToContain('Navigation icon "not-an-icon" is not a lucide icon this panel ships')
        ->doesntExpectOutputToContain('life-buoy')
        ->assertSuccessful();
});

test('doctor and pack reject a stylesheet that is not built with the declared tailwind prefix', function (): void {
    $manifest = ['id' => 'probe', 'name' => 'Probe', 'version' => '1.0.0', 'autoload' => ['Probe\\' => 'src'], 'ui' => ['entry' => 'dist/client.js', 'prefix' => 'pb']];
    File::put($this->directory.'/extension.json', json_encode($manifest, JSON_THROW_ON_ERROR));
    File::put($this->directory.'/dist/index.css', '@layer theme{:root,:host{--pb-text-sm:.875rem}}@layer utilities{.pb\:flex{display:flex}@media (width>=64rem){.pb\:lg\:hidden{display:none}}}.probe-banner{display:flex}');
    $this->artisan('p:extension:doctor', ['path' => $this->directory])->assertSuccessful();
    $archive = $this->directory.'/probe.pteroext';
    $this->artisan('p:extension:pack', ['path' => $this->directory, '--output' => $archive])->assertSuccessful();

    File::put($this->directory.'/dist/index.css', '@layer theme{:root,:host{--spacing:.25rem}}@layer utilities{.flex{display:flex}}');
    $this->artisan('p:extension:doctor', ['path' => $this->directory])
        ->expectsOutputToContain('ships Tailwind utilities and theme variables in dist/index.css (.flex, --spacing) without its "pb" prefix')
        ->assertFailed();
    $this->artisan('p:extension:pack', ['path' => $this->directory, '--output' => $archive, '--force' => true])
        ->expectsOutputToContain('build Tailwind with `prefix(pb)`')
        ->assertFailed();

    // The prefix every extension shared before each declared its own is as foreign as none.
    File::put($this->directory.'/dist/index.css', '@layer utilities{.ext\:flex{display:flex}}');
    $this->artisan('p:extension:doctor', ['path' => $this->directory])
        ->expectsOutputToContain('ships Tailwind utilities in dist/index.css (.ext\:flex) without its "pb" prefix')
        ->assertFailed();

    unset($manifest['ui']['prefix']);
    File::put($this->directory.'/extension.json', json_encode($manifest, JSON_THROW_ON_ERROR));
    File::put($this->directory.'/dist/index.css', '@layer utilities{.pb\:flex{display:flex}}');
    $this->artisan('p:extension:doctor', ['path' => $this->directory])
        ->expectsOutputToContain('declares no Tailwind prefix - add "prefix": "probe" to "ui" in extension.json')
        ->assertFailed();
});

test('packs runtime sources and lazy assets without developer files or hidden secrets', function (): void {
    File::put($this->directory.'/.env', 'private');
    File::ensureDirectoryExists($this->directory.'/src/.private');
    File::put($this->directory.'/src/.private/secret.txt', 'private');
    File::ensureDirectoryExists($this->directory.'/node_modules');
    File::put($this->directory.'/node_modules/package.js', 'development');
    $archive = $this->directory.'/probe.pteroext';
    $this->artisan('p:extension:pack', ['path' => $this->directory, '--output' => $archive])->assertSuccessful();
    $zip = new ZipArchive;
    expect($zip->open($archive))->toBeTrue();
    try {
        expect($zip->getFromName('extension.json'))->toContain('probe');
        expect($zip->getFromName('dist/chunks/lazy.js'))->toBe('export default 1;');
        expect($zip->getFromName('src/Example.php'))->toBe('<?php');
        expect($zip->locateName('.env'))->toBeFalse();
        expect($zip->locateName('src/.private/secret.txt'))->toBeFalse();
        expect($zip->locateName('node_modules/package.js'))->toBeFalse();
    } finally {
        $zip->close();
    }
    $this->artisan('p:extension:pack', ['path' => $this->directory, '--output' => $archive])->assertFailed();
});

test('a failed development build never publishes the incomplete package', function (): void {
    Process::fake(['*' => Process::result(exitCode: 1, errorOutput: 'build failed')]);
    $installer = Mockery::mock(InstallsExtensions::class);
    $installer->shouldNotReceive('install');
    $this->app->instance(InstallsExtensions::class, $installer);
    $this->artisan('p:extension:dev', ['path' => $this->directory])->assertFailed();
});

test('development publishes after a successful build', function (): void {
    Process::fake(['*' => Process::result(output: 'built in 100ms')]);
    $manifest = resolve(ExtensionManifestValidator::class)->fromDirectory($this->directory);
    $installer = Mockery::mock(InstallsExtensions::class);
    $installer->shouldReceive('install')->once()->with($this->directory, false, true)->andReturn($manifest);
    $this->app->instance(InstallsExtensions::class, $installer);
    $this->artisan('p:extension:dev', ['path' => $this->directory])->assertSuccessful();
});

test('generates literal screen ids and only declared frontend configuration keys', function (): void {
    $this->app->make(\Pterodactyl\Services\Extensions\ExtensionPermissionRegistry::class)->register('probe', 'Probe', ['view' => 'View progress']);
    $definition = new \Pterodactyl\Services\Extensions\ExtensionSettingsDefinition(new \Pterodactyl\Services\Extensions\ExtensionSettings('probe'), [
        \Pterodactyl\Services\Extensions\ExtensionSettingDefinition::make('flag', 'enabled', true)->frontend()->public()->frontendType('boolean'),
        \Pterodactyl\Services\Extensions\ExtensionSettingDefinition::make('accent', 'accent', '#3b82f6')->color()->frontend(),
        \Pterodactyl\Services\Extensions\ExtensionSettingDefinition::make('links', 'links', [])->list()->frontend(),
        \Pterodactyl\Services\Extensions\ExtensionSettingDefinition::make('token', 'token', 'private')->secret(),
    ]);
    $this->app->make(\Pterodactyl\Services\Extensions\ExtensionSettingsRegistry::class)->register('probe', $definition);
    $manifest = File::json($this->directory.'/extension.json');
    $manifest['ui']['screens'] = [['id' => 'main', 'area' => 'account', 'path' => 'probe']];
    File::put($this->directory.'/extension.json', json_encode($manifest, JSON_THROW_ON_ERROR));
    $this->artisan('p:extension:types', ['path' => $this->directory])->assertSuccessful();
    $source = File::get($this->directory.'/src/client/extension-types.ts');
    expect($source)->toContain('ExtensionScreenId = "main"', 'readonly "enabled": boolean', 'ExtensionPermission = "ext.probe.view"');
    expect($source)->toContain('readonly "accent": string;', 'readonly "links": readonly ExtensionConfigValue[];', 'ExtensionPublicConfigKey = "enabled";', 'ExtensionPublicConfig = Pick<ExtensionConfig, ExtensionPublicConfigKey>');
    expect($source)->not->toContain('token', 'private');
});
