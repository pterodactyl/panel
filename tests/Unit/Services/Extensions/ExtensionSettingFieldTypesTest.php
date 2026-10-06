<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Extensions\ExtensionSettingFieldTypesTest;

use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Pterodactyl\Services\Extensions\ExtensionSettingDefinition;
use Pterodactyl\Services\Extensions\ExtensionSettingFiles;
use Pterodactyl\Services\Extensions\ExtensionSettings;
use Pterodactyl\Services\Extensions\ExtensionSettingsDefinition;
use Pterodactyl\Tests\TestCase;

use function pterodactylTestCase;

uses(TestCase::class);

const STORED = '0123456789abcdef0123456789abcdef01234567.png';

test('public settings require frontend and exclude secrets', function (): void {
    expect(ExtensionSettingDefinition::make('a', 'a', true)->frontend()->public()->isPublic())->toBeTrue();
    expect(ExtensionSettingDefinition::make('a', 'a', true)->frontend()->isPublic())->toBeFalse();
    expect(fn () => ExtensionSettingDefinition::make('a', 'a', true)->public())->toThrow(InvalidArgumentException::class, 'call ->frontend() first');
    expect(fn () => ExtensionSettingDefinition::make('a', 'a', '')->secret()->public())->toThrow(InvalidArgumentException::class, 'cannot be public');
    expect(fn () => ExtensionSettingDefinition::make('a', 'a', '')->frontend()->public()->secret())->toThrow(InvalidArgumentException::class);
    expect(fn () => ExtensionSettingDefinition::make('a', 'a', true)->frontend()->public()->frontend(false))->toThrow(InvalidArgumentException::class, 'must stay exposed');
});

test('guests receive only public frontend settings', function (): void {
    $definition = new ExtensionSettingsDefinition(settings(['brand' => '#abcdef']), [
        ExtensionSettingDefinition::make('registration', 'registration', true)->frontend()->public()->frontendType('boolean'),
        ExtensionSettingDefinition::make('brand', 'brand', '#000000', public: 'brand_color')->color()->frontend()->public(),
        ExtensionSettingDefinition::make('page_size', 'page_size', 50)->frontend(),
        ExtensionSettingDefinition::make('token', 'token', 'private')->secret(),
    ]);

    expect($definition->frontendConfig(publicOnly: true))->toBe(['registration' => true, 'brand_color' => '#abcdef']);
    expect($definition->frontendConfig())->toBe(['registration' => true, 'brand_color' => '#abcdef', 'page_size' => 50]);
    expect($definition->publicConfigKeys())->toBe(['registration', 'brand_color']);
    expect(array_column($definition->schema(), 'visibility'))->toBe(['public', 'public', 'frontend', 'admin']);
});

test('fields can be grouped into tabs on the settings form', function (): void {
    $definition = new ExtensionSettingsDefinition(settings(), [
        ExtensionSettingDefinition::make('bucket', 'bucket', '')->tab('  Storage '),
        ExtensionSettingDefinition::make('enabled', 'enabled', false)->field('toggle'),
        ExtensionSettingDefinition::make('access_key', 'access_key', '')->secret()->tab('Storage'),
    ]);

    expect(array_column($definition->schema(), 'tab', 'input'))->toBe(['bucket' => 'Storage', 'enabled' => null, 'access_key' => 'Storage']);
    expect(fn () => ExtensionSettingDefinition::make('a', 'a', '')->tab(' '))->toThrow(InvalidArgumentException::class, '1 to 64 characters');
    expect(fn () => ExtensionSettingDefinition::make('a', 'a', '')->tab(str_repeat('a', 65)))->toThrow(InvalidArgumentException::class, '1 to 64 characters');
});

test('rich field types cannot be secret', function (string $field): void {
    $default = in_array($field, ['multiselect', 'list'], true) ? [] : ($field === 'textarea' ? '' : null);
    $options = [['value' => 'a', 'label' => 'A']];
    expect(fn () => ExtensionSettingDefinition::make('x', 'x', $default)->field($field, $options)->secret())->toThrow(InvalidArgumentException::class, 'cannot be secret');
    expect(fn () => ExtensionSettingDefinition::make('x', 'x', $default)->secret()->field($field, $options))->toThrow(InvalidArgumentException::class, 'secret extension setting cannot use');
})->with(['color', 'textarea', 'multiselect', 'list', 'file']);

test('field declarations reject defaults and limits that do not fit', function (callable $declare): void {
    expect($declare)->toThrow(InvalidArgumentException::class);
})->with([
    'color default' => [fn () => ExtensionSettingDefinition::make('x', 'x', 'red')->color()],
    'textarea default' => [fn () => ExtensionSettingDefinition::make('x', 'x', null)->textarea()],
    'textarea length' => [fn () => ExtensionSettingDefinition::make('x', 'x', '')->textarea(0)],
    'multiselect default' => [fn () => ExtensionSettingDefinition::make('x', 'x', 'a')->multiselect([['value' => 'a', 'label' => 'A']])],
    'multiselect without choices' => [fn () => ExtensionSettingDefinition::make('x', 'x', [])->multiselect([])],
    'multiselect boolean choice' => [fn () => ExtensionSettingDefinition::make('x', 'x', [])->multiselect([['value' => true, 'label' => 'Yes']])],
    'list default' => [fn () => ExtensionSettingDefinition::make('x', 'x', 'a | b')->list()],
    'list size' => [fn () => ExtensionSettingDefinition::make('x', 'x', [])->list(maxItems: 0)],
    'file default' => [fn () => ExtensionSettingDefinition::make('x', 'x', [])->file()],
    'file unknown type' => [fn () => ExtensionSettingDefinition::make('x', 'x', null)->file(['text/html'])],
    'file without types' => [fn () => ExtensionSettingDefinition::make('x', 'x', null)->file([])],
    'file size' => [fn () => ExtensionSettingDefinition::make('x', 'x', null)->file(maxKilobytes: ExtensionSettingFiles::MAX_KILOBYTES + 1)],
    'number default' => [fn () => ExtensionSettingDefinition::make('x', 'x', '10')->field('number')],
    'toggle default' => [fn () => ExtensionSettingDefinition::make('x', 'x', 1)->field('toggle')],
    'select default' => [fn () => ExtensionSettingDefinition::make('x', 'x', 'c')->field('select', [['value' => 'a', 'label' => 'A']])],
]);

test('password fields are always secret and never reach frontend bundles', function (): void {
    $definition = new ExtensionSettingsDefinition(settings(['token' => 'stored-token']), [
        ExtensionSettingDefinition::make('token', 'token', '')->field('password')->publicUsing(fn (mixed $value): mixed => $value),
    ]);

    expect($definition->schema()[0])->toMatchArray(['field' => 'password', 'value' => ExtensionSettingDefinition::MASK, 'visibility' => 'admin']);
    expect($definition->publicSettings())->toBe(['token' => ExtensionSettingDefinition::MASK]);
    expect($definition->frontendConfig())->toBe([]);
    expect(fn () => ExtensionSettingDefinition::make('a', 'a', '')->field('password')->frontend())->toThrow(InvalidArgumentException::class, 'cannot be exposed to frontend bundles');
    expect(fn () => ExtensionSettingDefinition::make('a', 'a', '')->frontend()->field('password'))->toThrow(InvalidArgumentException::class, 'cannot be exposed to frontend bundles');
    expect(fn () => ExtensionSettingDefinition::make('a', 'a', '')->secret()->field('text')->frontend())->toThrow(InvalidArgumentException::class, 'cannot be exposed to frontend bundles');
});

test('a secret keeps its stored value when the mask, a blank or nothing is submitted', function (string $field, ?string $default): void {
    $definition = new ExtensionSettingsDefinition(settings(['token' => 'stored-token']), [
        ExtensionSettingDefinition::make('token', 'token', $default)->secret()->field($field, [['value' => 'stored-token', 'label' => 'Stored']]),
        ExtensionSettingDefinition::make('note', 'note', ''),
    ]);
    $masked = $definition->schema()[0]['value'];
    expect($masked)->toBe(ExtensionSettingDefinition::MASK);
    expect($definition->withoutBlankSecrets(['token' => $masked, 'note' => 'a']))->toBe(['note' => 'a']);

    foreach ([['token' => $masked], ['token' => ''], ['token' => null], []] as $input) {
        $definition->update([...$input, 'note' => 'changed']);
        expect($definition->get('token'))->toBe('stored-token', json_encode($input));
    }

    $definition->update(['token' => 'stored-token-2']);
    expect($definition->get('token'))->toBe('stored-token-2');
})->with([
    'password' => ['password', ''],
    'text' => ['text', ''],
    'select' => ['select', null],
]);

test('text, number, toggle and select values must match their field type', function (): void {
    $definition = new ExtensionSettingsDefinition(settings(), [
        ExtensionSettingDefinition::make('name', 'name', 'Panel'),
        ExtensionSettingDefinition::make('limit', 'limit', 10)->field('number'),
        ExtensionSettingDefinition::make('enabled', 'enabled', false)->field('toggle'),
        ExtensionSettingDefinition::make('size', 'size', 25)->field('select', [['value' => 25, 'label' => '25'], ['value' => 'all', 'label' => 'All'], ['value' => false, 'label' => 'Off']]),
        ExtensionSettingDefinition::make('token', 'token', '')->secret(),
    ]);

    foreach ([['name' => 'x'], ['name' => 12], ['name' => null], ['limit' => 2.5], ['limit' => null], ['enabled' => true], ['size' => 'all'], ['size' => false], ['size' => 25], ['token' => 'abc']] as $input) {
        expect(passes($definition, $input))->toBeTrue(json_encode($input));
    }

    foreach ([['name' => ['x']], ['name' => true], ['name' => ['a' => 'b']], ['limit' => '10'], ['limit' => [1]], ['limit' => true], ['enabled' => 'yes'], ['enabled' => 1], ['enabled' => '1'], ['size' => '25'], ['size' => 50], ['size' => 0], ['token' => ['abc']], ['token' => false]] as $input) {
        expect(passes($definition, $input))->toBeFalse(json_encode($input));
    }
});

test('color accepts only hex and oklch colours and stores the canonical form', function (): void {
    $definition = richDefinition();
    foreach (['#FFF', '#ffff', '#1F2933', '#1f293380', 'oklch(0.62 0.19 259.8)', 'OKLCH( 62%  0.19  259.8deg / 50% )'] as $color) {
        expect(passes($definition, ['accent' => $color]))->toBeTrue($color);
    }

    foreach (['red', '#12', '#12345', '#gggggg', 'rgb(0,0,0)', 'var(--primary)', 'url(https://example.com/x)', '#fff; background: url(x)', 'oklch(0.6 0.1 20) }', 'oklch(calc(1) 0 0)', 12, ['#fff']] as $color) {
        expect(passes($definition, ['accent' => $color]))->toBeFalse(json_encode($color));
    }

    $definition->update(['accent' => ' OKLCH( 62%  0.19  259.8deg / 50% ) ']);
    expect($definition->get('accent'))->toBe('oklch(62% 0.19 259.8deg / 50%)');
    $definition->update(['accent' => '#ABCDEF']);
    expect($definition->get('accent'))->toBe('#abcdef');
    // Clearing a colour restores its default instead of storing an unusable value.
    expect(passes($definition, ['accent' => null]))->toBeTrue();
    $definition->update(['accent' => null]);
    expect($definition->get('accent'))->toBe('#3b82f6');
});

test('textarea enforces its maximum length and normalizes line endings', function (): void {
    $definition = richDefinition();
    expect(passes($definition, ['motd' => str_repeat('a', 20)]))->toBeTrue();
    expect(passes($definition, ['motd' => str_repeat('a', 21)]))->toBeFalse();
    expect(passes($definition, ['motd' => ['a']]))->toBeFalse();
    $definition->update(['motd' => "one\r\ntwo\rthree"]);
    expect($definition->get('motd'))->toBe("one\ntwo\nthree");
    $definition->update(['motd' => null]);
    expect($definition->get('motd'))->toBe('');
});

test('multiselect accepts only declared choices', function (): void {
    $definition = richDefinition();
    expect(passes($definition, ['tags' => ['survival', 3]]))->toBeTrue();
    expect(passes($definition, ['tags' => []]))->toBeTrue();
    expect(passes($definition, ['tags' => ['survival', 'unknown']]))->toBeFalse();
    expect(passes($definition, ['tags' => ['survival', 'survival']]))->toBeFalse();
    expect(passes($definition, ['tags' => ['a' => 'survival']]))->toBeFalse();
    expect(passes($definition, ['tags' => 'survival']))->toBeFalse();
    $definition->update(['tags' => ['3', 'survival']]);
    expect($definition->get('tags'))->toBe(['survival', 3], 'stored in declaration order with declared types');
});

test('list enforces per item rules and its maximum size', function (): void {
    $definition = richDefinition();
    expect(passes($definition, ['links' => ['Docs | /docs', 'Status | https://status.example.com']]))->toBeTrue();
    expect(passes($definition, ['links' => ['one', 'two', 'three', 'four']]))->toBeFalse();
    expect(passes($definition, ['links' => ['no separator']]))->toBeFalse();
    expect(passes($definition, ['links' => [['nested']]]))->toBeFalse();
    expect(passes($definition, ['links' => [1]]))->toBeFalse();
    expect(passes($definition, ['links' => ['a' => 'Docs | /docs']]))->toBeFalse();
    $definition->update(['links' => ['Docs | /docs', 'Status | /status']]);
    expect($definition->get('links'))->toBe(['Docs | /docs', 'Status | /status']);
});

test('stored values that no longer fit their field fall back to the default', function (): void {
    $definition = richDefinition(['accent' => 'javascript:alert(1)', 'motd' => ['x'], 'tags' => 'survival', 'links' => 'a | b; c | d', 'logo' => '../../.env']);
    expect($definition->get('accent'))->toBe('#3b82f6');
    expect($definition->get('motd'))->toBe('hello');
    expect($definition->get('tags'))->toBe([]);
    expect($definition->get('links'))->toBe([]);
    expect($definition->get('logo'))->toBeNull();
});

test('schema describes the new fields with their constraints', function (): void {
    $schema = collect(richDefinition()->schema())->keyBy('input');
    expect($schema['accent'])->toMatchArray(['field' => 'color', 'value' => '#3b82f6']);
    expect($schema['motd'])->toMatchArray(['field' => 'textarea', 'constraints' => ['max_length' => 20, 'max_items' => null, 'max_kilobytes' => null, 'accept' => []]]);
    expect($schema['tags']['field'])->toBe('multiselect');
    expect($schema['tags']['options'])->toBe([['value' => 'survival', 'label' => 'Survival'], ['value' => 3, 'label' => 'Three']]);
    expect($schema['links'])->toMatchArray(['field' => 'list', 'constraints' => ['max_length' => null, 'max_items' => 3, 'max_kilobytes' => null, 'accept' => []]]);
    expect($schema['logo'])->toMatchArray(['field' => 'file', 'value' => null, 'constraints' => ['max_length' => null, 'max_items' => null, 'max_kilobytes' => 64, 'accept' => ['image/png', 'image/svg+xml']]]);
});

test('field types imply their frontend type', function (): void {
    $definition = new ExtensionSettingsDefinition(settings(), [
        ExtensionSettingDefinition::make('accent', 'accent', '#3b82f6')->color()->frontend(),
        ExtensionSettingDefinition::make('optional', 'optional', null)->color()->frontend(),
        ExtensionSettingDefinition::make('motd', 'motd', '')->textarea()->frontend(),
        ExtensionSettingDefinition::make('tags', 'tags', [])->multiselect([['value' => 'a', 'label' => 'A']])->frontend(),
        ExtensionSettingDefinition::make('links', 'links', [])->list()->frontend(),
        ExtensionSettingDefinition::make('logo', 'logo', '/favicons/favicon.ico')->file()->frontend(),
        ExtensionSettingDefinition::make('icon', 'icon', null)->file()->frontend(),
        ExtensionSettingDefinition::make('custom', 'custom', [])->list()->frontend()->frontendType('json'),
    ]);

    expect($definition->frontendConfigTypes())->toBe(['accent' => 'string', 'optional' => 'json', 'motd' => 'string', 'tags' => 'array', 'links' => 'array', 'logo' => 'string', 'icon' => 'json', 'custom' => 'json']);
    expect($definition->frontendConfig())->toBe(['accent' => '#3b82f6', 'optional' => null, 'motd' => '', 'tags' => [], 'links' => [], 'logo' => '/favicons/favicon.ico', 'icon' => null, 'custom' => []]);
});

test('file settings read as a url and are never written by a settings update', function (): void {
    $definition = richDefinition(['logo' => STORED]);
    expect($definition->get('logo'))->toBe('/extension-files/test-fixture/'.STORED);
    expect($definition->frontendConfig()['logo'])->toBe('/extension-files/test-fixture/'.STORED);
    expect(passes($definition, ['logo' => 'ffffffffffffffffffffffffffffffffffffffff.png']))->toBeFalse();

    $definition->update(['logo' => 'ffffffffffffffffffffffffffffffffffffffff.png', 'motd' => 'kept']);
    expect($definition->get('logo'))->toBe('/extension-files/test-fixture/'.STORED);
    expect($definition->get('motd'))->toBe('kept');
});

test('replaceFile swaps the stored reference and returns the previous one', function (): void {
    $definition = richDefinition();
    expect($definition->fileField('logo'))->toBeInstanceOf(ExtensionSettingDefinition::class);
    expect($definition->fileField('motd'))->toBeNull();
    expect($definition->replaceFile('logo', STORED))->toBeNull();
    expect($definition->replaceFile('logo', null))->toBe(STORED);
    expect($definition->get('logo'))->toBeNull();
    expect(fn () => $definition->replaceFile('motd', STORED))->toThrow(InvalidArgumentException::class);
    expect(fn () => $definition->replaceFile('logo', '../secret.png'))->toThrow(InvalidArgumentException::class);
});

/** @param array<string, mixed> $input */
function passes(ExtensionSettingsDefinition $definition, array $input): bool
{
    return Validator::make($input, $definition->validationRules())->passes();
}

/** @param array<string, mixed> $initial */
function richDefinition(array $initial = []): ExtensionSettingsDefinition
{
    return new ExtensionSettingsDefinition(settings($initial), [
        ExtensionSettingDefinition::make('accent', 'accent', '#3b82f6')->color()->frontend(),
        ExtensionSettingDefinition::make('motd', 'motd', 'hello')->textarea(20),
        ExtensionSettingDefinition::make('tags', 'tags', [])->multiselect([['value' => 'survival', 'label' => 'Survival'], ['value' => 3, 'label' => 'Three']]),
        ExtensionSettingDefinition::make('links', 'links', [])->list(['max:80', 'regex:/^[^|]+ \| \S+$/'], 3),
        ExtensionSettingDefinition::make('logo', 'logo', null)->file(['image/png', 'image/svg+xml'], 64)->frontend(),
    ]);
}

/**
 * In-memory ExtensionSettings double - no database.
 *
 * @param  array<string, mixed>  $initial
 */
function settings(array $initial = []): ExtensionSettings
{
    return (fn (): ExtensionSettings => new class($initial) extends ExtensionSettings
    {
        public function __construct(private array $values)
        {
            parent::__construct('test-fixture');
        }

        public function get(string $key, mixed $default = null): mixed
        {
            return array_key_exists($key, $this->values) ? $this->values[$key] : $default;
        }

        public function all(): array
        {
            return $this->values;
        }

        public function setMany(array $values): void
        {
            $this->values = array_replace($this->values, $values);
        }

        public function setManySecrets(array $values, array $secretKeys): void
        {
            $this->setMany($values);
        }

        public function forget(string $key): void
        {
            unset($this->values[$key]);
        }
    })->call(pterodactylTestCase());
}
