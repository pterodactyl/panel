<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Extensions\ExtensionManifestTest;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Mockery;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Services\Extensions\ExtensionAssetPublisher;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionManifestValidator;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Extensions\ExtensionSettingFiles;
use Pterodactyl\Services\Extensions\ExtensionSettingsRegistry;
use Pterodactyl\Tests\TestCase;
use RuntimeException;

use function pterodactylTestCase;

uses(TestCase::class);
beforeEach(function (): void {
    $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-manifest-'.uniqid();
    File::ensureDirectoryExists($this->directory);
});
afterEach(function (): void {
    File::deleteDirectory($this->directory);
});
dataset('invalidManifestProvider', fn (): array => ['missing name' => [['name' => ''], 'field "name"'], 'uppercase id' => [['id' => 'BadId'], 'must match'], 'reserved id' => [['id' => 'pterodactyl'], 'reserved'], 'ui without entry' => [['ui' => ['mode' => 'native']], '"entry"'], 'absolute ui entry' => [['ui' => ['entry' => '/etc/passwd']], 'relative path'], 'traversal ui entry' => [['ui' => ['entry' => '../../owned.js']], 'relative path'], 'unsupported ui entry' => [['ui' => ['entry' => 'dist/main.js']], 'must be "dist/client.js"'], 'unknown ui mode' => [['ui' => ['entry' => 'dist/client.js', 'mode' => 'sandboxed']], 'Unsupported ui.mode'], 'autoload traversal' => [['autoload' => ['X\\' => '../src']], 'relative paths'], 'autoload bad prefix' => [['autoload' => ['NoTrailingSlash' => 'src']], 'autoload']]);
test('parses a complete manifest', function (): void {
    writeManifest(['id' => 'example-extension', 'name' => 'Example Extension', 'version' => '1.2.3', 'description' => 'Example', 'author' => 'Pterodactyl', 'provider' => 'ExampleExtension\Provider', 'autoload' => ['ExampleExtension\\' => 'src'], 'ui' => ['entry' => 'dist/client.js', 'mode' => 'native']]);
    $manifest = (new ExtensionManifestValidator)->fromDirectory($this->directory);
    expect($manifest->id)->toBe('example-extension');
    expect($manifest->hasUi())->toBeTrue();
    expect($manifest->uiEntry)->toBe('dist/client.js');
    expect(resolve(ExtensionAssetPublisher::class)->entryUrl($manifest))->toBe('/assets/extensions/example-extension/client.js?v=123');
    expect($manifest->autoload)->toBe(['ExampleExtension\\' => 'src']);
    expect($manifest->path('routes', 'client.php'))->toBe($this->directory.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'client.php');
});
test('minimal manifest needs no ui or provider', function (): void {
    writeManifest(['id' => 'mini', 'name' => 'Mini', 'version' => '1.0.0']);
    $manifest = (new ExtensionManifestValidator)->fromDirectory($this->directory);
    expect($manifest->hasUi())->toBeFalse();
    expect($manifest->provider)->toBeNull();
    expect($manifest->uiMode)->toBe('native');
});
test('rejects invalid manifests', function (array $overrides, string $messageFragment): void {
    writeManifest(array_merge(['id' => 'valid-id', 'name' => 'Valid', 'version' => '1.0.0'], $overrides));
    $this->expectException(InvalidExtensionException::class);
    $this->expectExceptionMessageMatches('/'.preg_quote($messageFragment, '/').'/');
    (new ExtensionManifestValidator)->fromDirectory($this->directory);
})->with('invalidManifestProvider');
test('missing and malformed files', function (string $subdirectory, ?string $contents): void {
    $directory = $this->directory.DIRECTORY_SEPARATOR.$subdirectory;
    if ($contents !== null) {
        File::ensureDirectoryExists($directory);
        file_put_contents($directory.DIRECTORY_SEPARATOR.ExtensionManifest::FILENAME, $contents);
    }

    $this->expectException(InvalidExtensionException::class);
    (new ExtensionManifestValidator)->fromDirectory($directory);
})->with([
    'missing directory' => ['nope', null],
    'malformed json' => ['broken', '{'],
    'empty manifest' => ['empty', '{}'],
]);
function writeManifest(array $data): void
{
    (function () use ($data): void {
        file_put_contents($this->directory.DIRECTORY_SEPARATOR.ExtensionManifest::FILENAME, json_encode($data, JSON_PRETTY_PRINT));
    })->call(pterodactylTestCase());
}

test('preserves typed screen metadata for bootstrap before loading javascript', function (): void {
    $screen = ['id' => 'votes', 'area' => 'server', 'path' => 'votes/$tab', 'nav' => ['label' => 'Votes', 'exact' => true, 'params' => ['tab' => 'overview'], 'order' => 10, 'icon' => 'chart-no-axes-combined', 'badge' => 'Beta', 'group' => 'Community'], 'permission' => ['file.read'], 'when' => ['eggFeatures' => ['any' => ['eula']], 'eggTags' => ['all' => ['minecraft']], 'match' => 'any', 'runtime' => true]];
    writeManifest(['id' => 'screen-test', 'name' => 'Screens', 'version' => '1.0.0', 'ui' => ['entry' => 'dist/client.js', 'screens' => [$screen]]]);
    expect((new ExtensionManifestValidator)->fromDirectory($this->directory)->screens)->toBe([$screen]);
});

test('rejects malformed screen metadata', function (array $screens): void {
    writeManifest(['id' => 'screen-test', 'name' => 'Screens', 'version' => '1.0.0', 'ui' => ['entry' => 'dist/client.js', 'screens' => $screens]]);
    expect(fn (): ExtensionManifest => (new ExtensionManifestValidator)->fromDirectory($this->directory))->toThrow(InvalidExtensionException::class);
})->with([
    'reserved parameter' => [[['id' => 'main', 'area' => 'server', 'path' => 'votes/$id']]],
    'duplicate parameter' => [[['id' => 'main', 'area' => 'server', 'path' => 'votes/$tab/$tab']]],
    'empty navigation' => [[['id' => 'main', 'area' => 'server', 'path' => 'votes', 'nav' => []]]],
    'unknown area' => [[['id' => 'main', 'area' => 'root', 'path' => 'votes']]],
    'absolute path' => [[['id' => 'main', 'area' => 'server', 'path' => '/votes']]],
    'traversal' => [[['id' => 'main', 'area' => 'server', 'path' => 'votes/../files']]],
    'splat' => [[['id' => 'main', 'area' => 'server', 'path' => '$']]],
    'duplicate id' => [[['id' => 'main', 'area' => 'server', 'path' => 'a'], ['id' => 'main', 'area' => 'account', 'path' => 'b']]],
    'invalid permission' => [[['id' => 'main', 'area' => 'server', 'path' => 'votes', 'permission' => [123]]]],
    'missing navigation label' => [[['id' => 'main', 'area' => 'server', 'path' => 'votes', 'nav' => ['exact' => true]]]],
    'non boolean exact' => [[['id' => 'main', 'area' => 'server', 'path' => 'votes', 'nav' => ['label' => 'Votes', 'exact' => 'true']]]],
    'icon that is not a lucide name' => [[['id' => 'main', 'area' => 'server', 'path' => 'votes', 'nav' => ['label' => 'Votes', 'icon' => 'LifeBuoy']]]],
    'icon with a trailing hyphen' => [[['id' => 'main', 'area' => 'server', 'path' => 'votes', 'nav' => ['label' => 'Votes', 'icon' => 'life-']]]],
]);

test('accepts screen conditions', function (string $area, array $when): void {
    $screen = ['id' => 'main', 'area' => $area, 'path' => 'probe', 'when' => $when];
    writeManifest(['id' => 'screen-test', 'name' => 'Screens', 'version' => '1.0.0', 'ui' => ['entry' => 'dist/client.js', 'screens' => [$screen]]]);

    expect((new ExtensionManifestValidator)->fromDirectory($this->directory)->screens)->toBe([$screen]);
})->with([
    'egg features' => ['server', ['eggFeatures' => ['any' => ['eula', 'java_version']]]],
    'egg tags' => ['server', ['eggTags' => ['all' => ['minecraft'], 'any' => ['paper', 'purpur']]]],
    'either rule' => ['server', ['eggFeatures' => ['any' => ['eula']], 'eggTags' => ['any' => ['minecraft']], 'match' => 'any']],
    'egg rule with a predicate' => ['server', ['eggTags' => ['any' => ['minecraft']], 'runtime' => true]],
    'account predicate' => ['account', ['runtime' => true]],
    'admin predicate' => ['admin', ['runtime' => true]],
]);

test('rejects malformed screen conditions', function (string $area, mixed $when, string $message): void {
    writeManifest(['id' => 'screen-test', 'name' => 'Screens', 'version' => '1.0.0', 'ui' => ['entry' => 'dist/client.js', 'screens' => [
        ['id' => 'main', 'area' => $area, 'path' => 'probe', 'when' => $when],
    ]]]);

    expect(fn (): ExtensionManifest => (new ExtensionManifestValidator)->fromDirectory($this->directory))
        ->toThrow(InvalidExtensionException::class, $message);
})->with([
    'empty rule' => ['server', [], 'must declare an egg rule'],
    'disabled predicate only' => ['account', ['runtime' => false], 'must declare an egg rule'],
    'empty matcher' => ['server', ['eggTags' => []], 'must list "any" or "all" values'],
    'empty value list' => ['server', ['eggTags' => ['any' => []]], 'eggTags.any'],
    'matcher as a list' => ['server', ['eggFeatures' => ['eula']], 'eggFeatures'],
    'unknown matcher key' => ['server', ['eggFeatures' => ['none' => ['eula']]], 'eggFeatures'],
    'non string value' => ['server', ['eggTags' => ['all' => [42]]], 'eggTags.all.0'],
    'unknown combinator' => ['server', ['eggFeatures' => ['any' => ['eula']], 'eggTags' => ['any' => ['minecraft']], 'match' => 'either'], 'match'],
    'combinator with one rule' => ['server', ['eggFeatures' => ['any' => ['eula']], 'match' => 'any'], 'requires both'],
    'non boolean predicate flag' => ['server', ['runtime' => 'yes'], 'runtime'],
    'unknown key' => ['server', ['eggs' => [1]], 'when'],
    'egg features outside the server area' => ['account', ['eggFeatures' => ['any' => ['eula']]], 'only supported on server screens'],
    'egg tags outside the server area' => ['admin', ['eggTags' => ['any' => ['minecraft']]], 'only supported on server screens'],
]);

test('accepts the icon names the SDK publishes', function (): void {
    $names = File::json(base_path('packages/sdk/icons.json'));

    expect($names)->toContain('life-buoy', 'grid-2x2', 'arrow-down-0-1');
    expect(array_filter($names, fn (string $name): bool => preg_match(ExtensionManifest::ICON_REGEX, $name) !== 1 || mb_strlen($name) > 64))->toBe([]);
});

test('only server screens accept a permission list', function (string $area): void {
    writeManifest(['id' => 'screen-test', 'name' => 'Screens', 'version' => '1.0.0', 'ui' => ['entry' => 'dist/client.js', 'screens' => [
        ['id' => 'gated', 'area' => $area, 'path' => 'probe', 'nav' => ['label' => 'Probe'], 'permission' => ['backup.delete']],
    ]]]);

    expect(fn (): ExtensionManifest => (new ExtensionManifestValidator)->fromDirectory($this->directory))
        ->toThrow(InvalidExtensionException::class, 'Screen "permission" is only supported on server screens.');
})->with(['account', 'admin']);

test('serializes screen descriptors into bootstrap without evaluating an extension bundle', function (): void {
    $screen = ['id' => 'main', 'area' => 'account', 'path' => 'example', 'nav' => ['label' => 'Example']];
    writeManifest(['id' => 'example', 'name' => 'Example', 'version' => '1.0.0', 'ui' => ['entry' => 'dist/client.js', 'prefix' => 'ex', 'screens' => [$screen]]]);
    $manifest = (new ExtensionManifestValidator)->fromDirectory($this->directory);
    $repository = Mockery::mock(ExtensionRepository::class, [
        new ExtensionManifestValidator,
        resolve(ExtensionAssetPublisher::class),
        resolve(Dispatcher::class),
        resolve(ExtensionSettingsRegistry::class),
        resolve(\Pterodactyl\Services\Extensions\ExtensionCompatibility::class),
    ])->makePartial();
    $repository->shouldReceive('enabled')->once()->andReturn(collect(['example' => $manifest]));
    $repository->shouldReceive('settings')->with('example')->once()->andThrow(new RuntimeException('settings not ready'));
    $payload = $repository->frontendPayload(authenticated: true);
    expect($payload[0]['screens'])->toBe([$screen]);
    expect($payload[0]['prefix'])->toBe('ex');
    expect($payload[0]['entry'])->toBe('/assets/extensions/example/client.js?v=100');
    expect($this->directory.'/dist/client.js')->not->toBeFile();
});
test('leaves settings out of the bootstrap payload when config is not included', function (): void {
    writeManifest(['id' => 'example', 'name' => 'Example', 'version' => '1.0.0', 'ui' => ['entry' => 'dist/client.js']]);
    $manifest = (new ExtensionManifestValidator)->fromDirectory($this->directory);
    $repository = Mockery::mock(ExtensionRepository::class, [
        new ExtensionManifestValidator,
        resolve(ExtensionAssetPublisher::class),
        resolve(Dispatcher::class),
        resolve(ExtensionSettingsRegistry::class),
        resolve(\Pterodactyl\Services\Extensions\ExtensionCompatibility::class),
    ])->makePartial();
    $repository->shouldReceive('enabled')->once()->andReturn(collect(['example' => $manifest]));
    $repository->shouldNotReceive('settings');
    $payload = $repository->frontendPayload(authenticated: false);
    expect($payload[0]['id'])->toBe('example');
    expect($payload[0]['prefix'])->toBeNull();
    expect((array) $payload[0]['config'])->toBe([]);
});

test('preserves resource tab parents in validated screen metadata', function (): void {
    $screens = [
        ['id' => 'node', 'area' => 'admin', 'parent' => 'admin.node', 'path' => 'probe', 'nav' => ['label' => 'Probe']],
        ['id' => 'server', 'area' => 'admin', 'parent' => 'admin.server', 'path' => 'probe'],
        ['id' => 'egg', 'area' => 'admin', 'parent' => 'admin.egg', 'path' => 'probe'],
        ['id' => 'user', 'area' => 'admin', 'parent' => 'admin.user', 'path' => 'probe'],
    ];
    writeManifest(['id' => 'probe', 'name' => 'Probe', 'version' => '1.0.0', 'ui' => ['entry' => 'dist/client.js', 'screens' => $screens]]);

    expect((new ExtensionManifestValidator)->fromDirectory($this->directory)->screens)->toBe($screens);
});

test('rejects unsupported resource tab parents', function (string $area, string $parent): void {
    writeManifest(['id' => 'probe', 'name' => 'Probe', 'version' => '1.0.0', 'ui' => ['entry' => 'dist/client.js', 'screens' => [
        ['id' => 'main', 'area' => $area, 'parent' => $parent, 'path' => 'probe'],
    ]]]);

    expect(fn (): ExtensionManifest => (new ExtensionManifestValidator)->fromDirectory($this->directory))->toThrow(InvalidExtensionException::class);
})->with([['admin', 'admin.unknown'], ['server', 'admin.node'], ['account', 'admin.node']]);

test('rejects malformed version requirements before enabling code', function (array $requires): void {
    writeManifest(['id' => 'probe', 'name' => 'Probe', 'version' => '1.0.0', 'requires' => $requires]);
    expect(fn (): ExtensionManifest => (new ExtensionManifestValidator)->fromDirectory($this->directory))->toThrow(InvalidExtensionException::class);
})->with([
    [['panel' => 'garbage']], [['sdk' => '']], [['extensions' => ['probe' => '^1.0']]],
    [['extensions' => ['Bad_Id' => '^1.0']]], [['extensions' => ['dependency' => false]]], [['unknown' => '*']],
]);

test('rejects navigation missing defaults or declaring parameters outside its route', function (array $params): void {
    writeManifest(['id' => 'probe', 'name' => 'Probe', 'version' => '1.0.0', 'ui' => ['entry' => 'dist/client.js', 'screens' => [
        ['id' => 'main', 'area' => 'account', 'path' => 'probe/$tab', 'nav' => ['label' => 'Probe', 'params' => $params]],
    ]]]);
    expect(fn (): ExtensionManifest => (new ExtensionManifestValidator)->fromDirectory($this->directory))->toThrow(InvalidExtensionException::class);
})->with([[[]], [['other' => 'overview']], [['tab' => 'overview', 'other' => 'extra']]]);

test('preserves declared component replacements for bootstrap', function (): void {
    writeManifest(['id' => 'views', 'name' => 'Views', 'version' => '1.0.0', 'requires' => ['sdk' => '>=2.0.0-beta.3 <3.0'], 'ui' => ['entry' => 'dist/client.js', 'components' => ExtensionManifest::COMPONENT_NAMES]]);

    expect((new ExtensionManifestValidator)->fromDirectory($this->directory)->components)->toBe(['dashboard.serverCard', 'server.files.details', 'server.files.editor', 'server.files.manager']);
});

test('rejects unsupported duplicate or malformed replacement declarations', function (array $components): void {
    writeManifest(['id' => 'views', 'name' => 'Views', 'version' => '1.0.0', 'requires' => ['sdk' => '*'], 'ui' => ['entry' => 'dist/client.js', 'components' => $components]]);

    expect(fn (): ExtensionManifest => (new ExtensionManifestValidator)->fromDirectory($this->directory))->toThrow(InvalidExtensionException::class);
})->with([
    'unknown name' => [['components/dashboard/ServerRow']],
    'duplicate name' => [['dashboard.serverCard', 'dashboard.serverCard']],
    'not a string' => [[123]],
    'not a list' => [['name' => 'dashboard.serverCard']],
]);

test('requires an SDK constraint when declaring replacements', function (): void {
    writeManifest(['id' => 'views', 'name' => 'Views', 'version' => '1.0.0', 'ui' => ['entry' => 'dist/client.js', 'components' => ['dashboard.serverCard']]]);

    expect(fn (): ExtensionManifest => (new ExtensionManifestValidator)->fromDirectory($this->directory))->toThrow(InvalidExtensionException::class, 'requires.sdk');
});

test('matches the SDK manifest component catalog', function (): void {
    $schema = File::json(base_path('packages/sdk/manifest.schema.json'));

    expect($schema['properties']['ui']['properties']['components']['items']['enum'])->toBe(ExtensionManifest::COMPONENT_NAMES);
    expect('/'.$schema['properties']['ui']['properties']['screens']['items']['properties']['nav']['properties']['icon']['pattern'].'/')->toBe(ExtensionManifest::ICON_REGEX);
});

test('preserves the declared tailwind prefix', function (string $prefix): void {
    writeManifest(['id' => 'styled', 'name' => 'Styled', 'version' => '1.0.0', 'ui' => ['entry' => 'dist/client.js', 'prefix' => $prefix]]);

    expect((new ExtensionManifestValidator)->fromDirectory($this->directory)->uiPrefix)->toBe($prefix);
})->with(['hw', 'ext', 'backups', 'twelveletter']);

test('a tailwind prefix is optional until the build ships tailwind layers', function (): void {
    writeManifest(['id' => 'styled', 'name' => 'Styled', 'version' => '1.0.0', 'ui' => ['entry' => 'dist/client.js']]);
    expect((new ExtensionManifestValidator)->fromDirectory($this->directory)->uiPrefix)->toBeNull();
    writeManifest(['id' => 'styled', 'name' => 'Styled', 'version' => '1.0.0']);
    expect((new ExtensionManifestValidator)->fromDirectory($this->directory)->uiPrefix)->toBeNull();
});

test('rejects malformed and reserved tailwind prefixes', function (mixed $prefix, string $message): void {
    writeManifest(['id' => 'styled', 'name' => 'Styled', 'version' => '1.0.0', 'ui' => ['entry' => 'dist/client.js', 'prefix' => $prefix]]);

    expect(fn (): ExtensionManifest => (new ExtensionManifestValidator)->fromDirectory($this->directory))->toThrow(InvalidExtensionException::class, 'Manifest "ui.prefix" '.$message);
})->with([
    'null' => [null, 'must match /^[a-z]{2,12}$/D.'],
    'not a string' => [12, 'must match /^[a-z]{2,12}$/D.'],
    'empty' => ['', 'must match /^[a-z]{2,12}$/D.'],
    'one letter' => ['h', 'must match /^[a-z]{2,12}$/D.'],
    'thirteen letters' => ['thirteenlette', 'must match /^[a-z]{2,12}$/D.'],
    'uppercase' => ['Hw', 'must match /^[a-z]{2,12}$/D.'],
    'digit' => ['hw2', 'must match /^[a-z]{2,12}$/D.'],
    'hyphen' => ['hello-world', 'must match /^[a-z]{2,12}$/D.'],
    'trailing colon' => ['hw:', 'must match /^[a-z]{2,12}$/D.'],
    'trailing newline' => ["hw\n", 'must match /^[a-z]{2,12}$/D.'],
    'breakpoint' => ['sm', 'is a Tailwind variant, theme namespace or panel name'],
    'state variant' => ['hover', 'is a Tailwind variant, theme namespace or panel name'],
    'dark variant' => ['dark', 'is a Tailwind variant, theme namespace or panel name'],
    'group marker' => ['group', 'is a Tailwind variant, theme namespace or panel name'],
    'theme namespace' => ['color', 'is a Tailwind variant, theme namespace or panel name'],
    'panel token' => ['terminal', 'is a Tailwind variant, theme namespace or panel name'],
    'tailwind internals' => ['tw', 'is a Tailwind variant, theme namespace or panel name'],
    'panel name' => ['panel', 'is a Tailwind variant, theme namespace or panel name'],
]);

test('the reserved tailwind prefixes match the SDK manifest schema', function (): void {
    $prefix = File::json(base_path('packages/sdk/manifest.schema.json'))['properties']['ui']['properties']['prefix'];
    $reserved = ExtensionManifest::RESERVED_UI_PREFIXES;
    $sorted = $reserved;
    sort($sorted);

    expect($prefix['not']['enum'])->toBe($reserved);
    expect('/'.$prefix['pattern'].'/D')->toBe(ExtensionManifest::UI_PREFIX_REGEX);
    expect($reserved)->toBe(array_values(array_unique($sorted)));
    // Everything listed could otherwise be declared, and the names the task was decided on are covered.
    expect(array_filter($reserved, fn (string $name): bool => preg_match(ExtensionManifest::UI_PREFIX_REGEX, $name) !== 1))->toBe([]);
    expect($reserved)->toContain('sm', 'md', 'lg', 'xl', 'dark', 'hover', 'focus', 'active', 'group', 'peer', 'has', 'not', 'in', 'first', 'last', 'odd', 'even', 'print');
});

test('preserves claimed root path prefixes', function (): void {
    writeManifest(['id' => 'redirect', 'name' => 'Redirect', 'version' => '1.0.0', 'routes' => ['root' => ['go', 'short-links']]]);

    expect((new ExtensionManifestValidator)->fromDirectory($this->directory)->rootPrefixes)->toBe(['go', 'short-links']);
    writeManifest(['id' => 'redirect', 'name' => 'Redirect', 'version' => '1.0.0']);
    expect((new ExtensionManifestValidator)->fromDirectory($this->directory)->rootPrefixes)->toBe([]);
});

test('rejects malformed, reserved and core root path prefixes', function (mixed $routes, string $message): void {
    Route::get('/health-probe/status', fn (): string => 'ok');
    Route::get('/taken-by-extension/status', fn (): string => 'ok')->name('extensions.other.root.taken-by-extension.status');
    writeManifest(['id' => 'redirect', 'name' => 'Redirect', 'version' => '1.0.0', 'routes' => $routes]);

    expect(fn (): ExtensionManifest => (new ExtensionManifestValidator)->fromDirectory($this->directory))->toThrow(InvalidExtensionException::class, $message);
})->with([
    'not an object' => ['go', '"routes" must be an object'],
    'unknown key' => [['web' => ['go']], 'routes'],
    'not a list' => [['root' => ['a' => 'go']], 'routes.root'],
    'uppercase' => [['root' => ['Go']], 'must match'],
    'nested path' => [['root' => ['go/links']], 'must match'],
    'parameter' => [['root' => ['{slug}']], 'must match'],
    'duplicate' => [['root' => ['go', 'go']], 'must be unique'],
    'too many' => [['root' => ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i']], 'routes.root'],
    'spa route' => [['root' => ['server']], '"/server" is reserved'],
    'spa admin area' => [['root' => ['panel']], '"/panel" is reserved'],
    'api prefix' => [['root' => ['api']], '"/api" is reserved'],
    'extension prefix' => [['root' => ['extensions']], '"/extensions" is reserved'],
    'registered core route' => [['root' => ['health-probe']], '"/health-probe" is reserved'],
    'public directory' => [['root' => ['favicons']], '"/favicons" is reserved'],
]);

test('root path prefixes of other extensions are left to the enable-time comparison', function (): void {
    Route::get('/taken-by-extension/status', fn (): string => 'ok')->name('extensions.other.root.taken-by-extension.status');
    writeManifest(['id' => 'redirect', 'name' => 'Redirect', 'version' => '1.0.0', 'routes' => ['root' => ['taken-by-extension']]]);

    expect((new ExtensionManifestValidator)->fromDirectory($this->directory)->rootPrefixes)->toBe(['taken-by-extension']);
});

test('reserves every top-level route of the SPA and matches the SDK manifest schema', function (): void {
    $schema = File::json(base_path('packages/sdk/manifest.schema.json'));
    $items = $schema['properties']['routes']['properties']['root']['items'];

    expect($items['not']['enum'])->toBe(ExtensionManifest::RESERVED_ROOT_PREFIXES);
    expect('/'.$items['pattern'].'/')->toBe(ExtensionManifest::ROOT_PREFIX_REGEX);

    // Client-side routes mounted directly under the root or the authenticated layout.
    preg_match_all('/getParentRoute: \(\) => (?:rootRoute|authenticatedRoute),\s+path: \'([a-z][a-z0-9-]*)/', File::get(resource_path('scripts/router/routeTree.ts')), $matches);
    expect($matches[1])->not->toBeEmpty();
    expect(array_diff($matches[1], ExtensionManifest::RESERVED_ROOT_PREFIXES))->toBe([]);

    // Served by a core route whose `extensions.` name the route scan skips.
    expect(ExtensionManifest::RESERVED_ROOT_PREFIXES)->toContain(ExtensionSettingFiles::ROUTE_PREFIX);
});
