<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Themes\ThemeManifestTest;

use Illuminate\Support\Facades\File;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionManifestValidator;
use Pterodactyl\Services\Themes\ThemeSetting;
use Pterodactyl\Tests\TestCase;

use function pterodactylTestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-theme-manifest-'.uniqid();
    File::ensureDirectoryExists($this->directory);
});

afterEach(function (): void {
    File::deleteDirectory($this->directory);
});

/** @param array<string, mixed> $overrides */
function theme(array $overrides = []): ExtensionManifest
{
    return (function () use ($overrides): ExtensionManifest {
        File::put($this->directory.'/extension.json', json_encode([
            'id' => 'aurora',
            'type' => 'theme',
            'name' => 'Aurora',
            'version' => '1.0.0',
            ...$overrides,
        ], JSON_THROW_ON_ERROR));

        return (new ExtensionManifestValidator)->fromDirectory($this->directory);
    })->call(pterodactylTestCase());
}

/** @param array<string, mixed> $setting */
function withSetting(array $setting): ExtensionManifest
{
    return theme(['theme' => ['settings' => ['probe' => ['label' => 'Probe', ...$setting]]]]);
}

test('parses a theme with its stylesheet, schemes and typed settings', function (): void {
    $manifest = theme([
        'ui' => ['entry' => 'dist/client.js', 'screens' => [['id' => 'gallery', 'area' => 'account', 'path' => 'gallery']]],
        'theme' => [
            'extends' => 'base',
            'stylesheet' => 'dist/theme.css',
            'schemes' => ['light', 'dark'],
            'settings' => [
                'accent' => ['type' => 'color', 'label' => 'Accent', 'default' => '#8B5CF6', 'token' => '--accent'],
                'navigation' => ['type' => 'select', 'label' => 'Navigation', 'options' => ['side' => 'Sidebar', 'top' => 'Top bar'], 'default' => 'side', 'attribute' => 'data-aurora-nav'],
                'radius' => ['type' => 'number', 'label' => 'Radius', 'default' => 0.5, 'min' => 0, 'max' => 2, 'step' => 0.25, 'unit' => 'rem', 'token' => '--radius'],
                'logo' => ['type' => 'image', 'label' => 'Logo', 'token' => '--brand-logo'],
            ],
        ],
    ]);

    expect($manifest->isTheme())->toBeTrue()
        ->and($manifest->hasAssets())->toBeTrue()
        ->and($manifest->screens)->toHaveCount(1)
        ->and($manifest->dependencies())->toBe(['base' => '*'])
        ->and($manifest->theme->schemes)->toBe(['light', 'dark'])
        ->and($manifest->theme->settings['navigation']->options)->toBe(['side' => 'Sidebar', 'top' => 'Top bar'])
        ->and($manifest->theme->settings['radius']->step)->toBe(0.25);
});

test('a theme needs no bundle, and classic is no parent to depend on', function (): void {
    $manifest = theme(['theme' => ['stylesheet' => 'dist/theme.css']]);

    expect($manifest->hasUi())->toBeFalse()->and($manifest->hasAssets())->toBeTrue()->and($manifest->dependencies())->toBe([]);
    expect(theme()->theme->extends)->toBe('classic');
    expect(theme()->hasAssets())->toBeFalse();
});

test('rejects theme manifests that break the theme rules', function (array $overrides, string $message): void {
    expect(fn () => theme($overrides))->toThrow(InvalidExtensionException::class, $message);
})->with([
    'a theme section on an extension' => [['type' => 'extension', 'theme' => ['extends' => 'base']], 'Only a package with "type": "theme"'],    'an unknown type' => [['type' => 'skin'], 'type'],
    'a tailwind prefix' => [['ui' => ['entry' => 'dist/client.js', 'prefix' => 'au']], 'cannot declare "ui.prefix"'],
    'view overrides' => [['requires' => ['sdk' => '*'], 'ui' => ['entry' => 'dist/client.js', 'views' => ['server.power']]], 'cannot declare "ui.views"'],
    'another stylesheet path' => [['theme' => ['stylesheet' => 'dist/style.css']], 'must be "dist/theme.css"'],
    'an unknown scheme' => [['theme' => ['schemes' => ['sepia']]], 'theme.schemes.0'],
    'no schemes' => [['theme' => ['schemes' => []]], 'theme.schemes'],
    'extending itself' => [['theme' => ['extends' => 'aurora']], 'cannot extend itself'],
    'a malformed parent' => [['theme' => ['extends' => 'Base']], 'theme.extends'],
    'an unknown key' => [['theme' => ['layout' => 'top']], 'theme'],
    'the built-in id' => [['id' => 'classic'], 'reserved'],
]);

test('rejects settings that could not be stored or rendered safely', function (array $setting, string $message): void {
    expect(fn () => withSetting($setting))->toThrow(InvalidExtensionException::class, $message);
})->with([
    'an unknown type' => [['type' => 'font'], 'theme.settings.probe.type'],
    'a colour default that is not a colour' => [['type' => 'color', 'default' => 'red'], 'Theme setting "probe": A color setting defaults to a hex or oklch() colour'],
    'a token that is not a custom property' => [['type' => 'color', 'token' => 'color'], 'custom property names'],
    'a token with css in it' => [['type' => 'color', 'token' => '--a;}body{x:y'], 'custom property names'],
    'an attribute outside data-*' => [['type' => 'toggle', 'attribute' => 'onclick'], 'data attributes'],
    'the theme attribute' => [['type' => 'toggle', 'attribute' => 'data-theme'], 'cannot use "data-theme"'],
    'a select without options' => [['type' => 'select'], 'theme.settings.probe.options'],
    'options outside a select' => [['type' => 'toggle', 'options' => ['a' => 'A']], 'theme.settings.probe.options'],
    'an option key with markup' => [['type' => 'select', 'options' => ['"><b' => 'Bad']], 'keyed by identifiers'],
    'a select default outside its options' => [['type' => 'select', 'options' => ['side' => 'Sidebar'], 'default' => 'top'], 'A select setting defaults to one of its options'],
    'a toggle default that is not a boolean' => [['type' => 'toggle', 'default' => 'yes'], 'A toggle setting defaults to true, false or null'],
    'number limits on a colour' => [['type' => 'color', 'min' => 1], 'theme.settings.probe.min'],
    'a number limit given as text' => [['type' => 'number', 'min' => '1'], 'must give "min" as a number'],
    'a min above the max' => [['type' => 'number', 'min' => 2, 'max' => 1], 'has a "min" above its "max"'],
    'a default outside the range' => [['type' => 'number', 'max' => 1, 'default' => 2], 'default outside its "min" and "max"'],
    'a zero step' => [['type' => 'number', 'step' => 0], 'theme.settings.probe.step'],
    'a unit with css in it' => [['type' => 'number', 'unit' => 'px;'], 'theme.settings.probe.unit'],
    'a text default too long' => [['type' => 'text', 'maxLength' => 3, 'default' => 'four'], 'defaults to text of at most 3 characters'],
    'an image default' => [['type' => 'image', 'default' => '/logo.png'], 'cannot have a default'],
]);

test('rejects setting names that are not identifiers and two settings writing the same target', function (): void {
    expect(fn () => theme(['theme' => ['settings' => ['bad-name' => ['type' => 'toggle', 'label' => 'Bad']]]]))->toThrow(InvalidExtensionException::class, 'Theme setting names must match');
    expect(fn () => theme(['theme' => ['settings' => [['type' => 'toggle', 'label' => 'Listed']]]]))->toThrow(InvalidExtensionException::class, 'Theme setting names must match');
    expect(fn () => theme(['theme' => ['settings' => [
        'one' => ['type' => 'color', 'label' => 'One', 'token' => '--accent'],
        'two' => ['type' => 'color', 'label' => 'Two', 'token' => '--accent'],
    ]]]))->toThrow(InvalidExtensionException::class, 'Theme settings "one" and "two" both write "--accent".');
});

test('a child theme overrides a setting of its parent with only the fields it changes', function (): void {
    $manifest = theme(['theme' => ['extends' => 'base', 'settings' => [
        'accent' => ['default' => '#ff4fd8'],
        'stars' => ['type' => 'toggle', 'label' => 'Stars', 'default' => true],
    ]]]);

    expect($manifest->theme->overrides)->toBe(['accent' => ['default' => '#ff4fd8']])
        ->and(array_keys($manifest->theme->settings))->toBe(['stars']);
    expect(fn () => theme(['theme' => ['settings' => ['accent' => ['default' => '#ff4fd8']]]]))->toThrow(InvalidExtensionException::class, 'only a theme that extends another can override');
    // What does not depend on the type is checked before the override is merged.
    expect(fn () => theme(['theme' => ['extends' => 'base', 'settings' => ['accent' => ['token' => 'color']]]]))->toThrow(InvalidExtensionException::class, 'custom property names');
});

test('an override merged over the declaration it changes is checked as a declaration', function (): void {
    $validator = new ExtensionManifestValidator;
    $accent = ThemeSetting::fromValidatedData('accent', ['type' => 'color', 'label' => 'Accent', 'help' => 'Buttons.', 'default' => '#7c5cff', 'token' => '--aurora-accent']);
    $merged = $validator->themeSetting('accent', $accent->overriddenBy(['default' => '#ff4fd8']));

    expect([$merged->type, $merged->label, $merged->help, $merged->token, $merged->default])->toBe(['color', 'Accent', 'Buttons.', '--aurora-accent', '#ff4fd8']);
    expect(fn () => $validator->themeSetting('accent', $accent->overriddenBy(['default' => 'red'])))->toThrow(InvalidExtensionException::class, 'A color setting defaults to');
    expect(fn () => $validator->themeSetting('accent', $accent->overriddenBy(['min' => 1])))->toThrow(InvalidExtensionException::class, 'theme.settings.accent.min');
});

test('settings validate saved values with the rules of the matching extension fields', function (string $type, array $extra, mixed $value, bool $valid): void {
    $setting = ThemeSetting::fromValidatedData('probe', ['type' => $type, 'label' => 'Probe', ...$extra]);
    $validator = validator(['probe' => $value], $setting->definition()->validationRules());

    expect($validator->passes())->toBe($valid);
})->with([
    'a hex colour' => ['color', [], '#abc', true],
    'an oklch colour' => ['color', [], 'oklch(0.7 0.1 250 / 50%)', true],
    'a named colour' => ['color', [], 'red', false],
    'css after a colour' => ['color', [], '#fff;}a{color:red', false],
    'an option' => ['select', ['options' => ['side' => 'Side']], 'side', true],
    'not an option' => ['select', ['options' => ['side' => 'Side']], 'top', false],
    'a boolean' => ['toggle', [], true, true],
    'a boolean as text' => ['toggle', [], 'true', false],
    'a number on step' => ['number', ['min' => 0.5, 'step' => 0.25], 1.25, true],
    'a number off step' => ['number', ['min' => 0.5, 'step' => 0.25], 1.3, false],
    'a number below min' => ['number', ['min' => 1], 0, false],
    'null restores the default' => ['number', ['min' => 1], null, true],
    'text within its length' => ['text', ['maxLength' => 5], 'hello', true],
    'text over its length' => ['text', ['maxLength' => 5], 'hello!', false],
    'an image through the form' => ['image', [], '/extension-files/aurora/x.png', false],
]);

test('settings render tokens and attributes as values that cannot break out', function (): void {
    $setting = fn (string $type, array $extra = []): ThemeSetting => ThemeSetting::fromValidatedData('probe', ['type' => $type, 'label' => 'Probe', ...$extra]);

    expect($setting('color')->tokenValue('#ABCDEF'))->toBe('#abcdef')
        ->and($setting('color')->tokenValue('red;}a{'))->toBeNull()
        ->and($setting('number', ['unit' => 'rem'])->tokenValue(0.75))->toBe('0.75rem')
        ->and($setting('number', ['unit' => '%'])->tokenValue(50.0))->toBe('50%')
        ->and($setting('number')->tokenValue('1;x'))->toBeNull()
        ->and($setting('toggle')->tokenValue(true))->toBe('1')
        ->and($setting('select', ['options' => ['top' => 'Top']])->tokenValue('top'))->toBe('top')
        ->and($setting('select', ['options' => ['top' => 'Top']])->tokenValue('bottom'))->toBeNull()
        ->and($setting('image')->tokenValue('/extension-files/aurora/a.png'))->toBe('url("/extension-files/aurora/a.png")')
        ->and($setting('text')->tokenValue('</style><script>"x"\\'))->toBe('"\\3c /style\\3e \\3c script\\3e \\22 x\\22 \\5c "')
        ->and($setting('text')->tokenValue("a\nb"))->toBe('"a\\a b"');

    expect($setting('toggle')->attributeValue(false))->toBe('false')
        ->and($setting('number')->attributeValue(3))->toBe('3')
        ->and($setting('text')->attributeValue(null))->toBeNull();
});

test('the manifests the SDK readme documents are valid', function (string $sample, string $id, bool $theme): void {
    $manifest = (new ExtensionManifestValidator)->fromDirectory(base_path('packages/sdk/docs-samples/'.$sample));

    expect($manifest->id)->toBe($id)->and($manifest->isTheme())->toBe($theme);
})->with([
    'the sample theme' => ['aurora', 'aurora', true],
    'a child of it' => ['child-theme', 'aurora-sunset', true],
    'an extension overriding views' => ['extension', 'billing', false],
]);

test('stored values that no longer fit the setting read as its default', function (): void {
    $select = ThemeSetting::fromValidatedData('probe', ['type' => 'select', 'label' => 'Probe', 'options' => ['side' => 'Side'], 'default' => 'side']);
    $number = ThemeSetting::fromValidatedData('probe', ['type' => 'number', 'label' => 'Probe', 'max' => 2, 'default' => 1]);

    expect($select->value('removed'))->toBe('side')
        ->and($select->value(null))->toBe('side')
        ->and($number->value(5))->toBe(1)
        ->and($number->value(1.5))->toBe(1.5);
});
