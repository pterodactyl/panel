<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Extensions\ExtensionSettingsDefinitionTest;

use InvalidArgumentException;
use Pterodactyl\Rules\ValidExtensionSettingType;
use Pterodactyl\Services\Extensions\ExtensionSettingDefinition;
use Pterodactyl\Services\Extensions\ExtensionSettings;
use Pterodactyl\Services\Extensions\ExtensionSettingsDefinition;
use Pterodactyl\Tests\TestCase;
use UnexpectedValueException;

use function pterodactylTestCase;

uses(TestCase::class);
test('schema describes fields with current public values', function (): void {
    $schema = definition(['api_key' => 'secret-xy'])->schema();
    expect($schema)->toHaveCount(3);
    [$enabled, $apiKey, $pageSize] = $schema;
    expect($enabled['input'])->toBe('enabled');
    expect($enabled['label'])->toBe('Enabled');
    expect($enabled['field'])->toBe('toggle');
    expect($enabled['value'])->toBeTrue();
    expect($apiKey['field'])->toBe('password');
    expect($apiKey['help'])->toBe('Secret value.');
    expect($apiKey['value'])->toBe(ExtensionSettingDefinition::MASK, 'a password field is secret, so publicUsing cannot reveal it');
    expect($pageSize['field'])->toBe('select');
    expect($pageSize['options'])->toBe([['value' => 25, 'label' => '25'], ['value' => 50, 'label' => '50']]);
    expect($pageSize['value'])->toBe(50);
});
test('labels default to headline of the input name', function (): void {
    $definition = new ExtensionSettingsDefinition(settings(), [ExtensionSettingDefinition::make('some_flag', 'some_flag', false)]);
    expect($definition->schema()[0]['label'])->toBe('Some Flag');
});
test('frontend config only contains frontend marked definitions', function (): void {
    $config = definition(['api_key' => 'secret-xy'])->frontendConfig();
    expect($config)->toBe(['enabled' => true, 'page_size' => 50]);
    $this->assertArrayNotHasKey('api_key', $config);
    $this->assertArrayNotHasKey('api_key_mask', $config);
});
test('update only writes submitted inputs', function (): void {
    $settings = settings(['api_key' => 'keep-me']);
    $definition = new ExtensionSettingsDefinition($settings, [ExtensionSettingDefinition::make('enabled', 'enabled', true)->normalizeUsing(fn ($value): bool => (bool) $value), ExtensionSettingDefinition::make('api_key', 'api_key', '')]);
    $definition->update(['enabled' => '0']);

    expect($definition->get('enabled'))->toBeFalse();
    expect($definition->get('api_key'))->toBe('keep-me');
});
test('validation rules make every input optional and check the field type', function (): void {
    $definition = new ExtensionSettingsDefinition(settings(), [
        ExtensionSettingDefinition::make('limit', 'limit', 10, ['required', 'integer']),
        ExtensionSettingDefinition::make('enabled', 'enabled', true, ['sometimes', 'boolean']),
        ExtensionSettingDefinition::make('greeting', 'greeting', 'hello'),
    ]);
    $rules = $definition->validationRules();

    expect(array_map(fn (array $set): array => array_filter($set, is_string(...)), $rules))->toBe([
        'limit' => ['sometimes', 'required', 'integer'],
        'enabled' => ['sometimes', 'boolean'],
        'greeting' => ['sometimes'],
    ]);
    expect(array_map(fn (array $set): mixed => end($set), $rules))->each->toBeInstanceOf(ValidExtensionSettingType::class);
});
test('field rejects unknown types', function (): void {
    $this->expectException(InvalidArgumentException::class);
    ExtensionSettingDefinition::make('x', 'x', null)->field('markdown');
});
/** In-memory ExtensionSettings double — no database. */
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
    })->call(pterodactylTestCase());
}

function definition(array $initial = []): ExtensionSettingsDefinition
{
    return new ExtensionSettingsDefinition(settings($initial), [ExtensionSettingDefinition::make('enabled', 'enabled', true, ['sometimes', 'boolean'])->label('Enabled')->field('toggle')->frontend()->normalizeUsing(fn ($value): bool => (bool) $value), ExtensionSettingDefinition::make('api_key', 'api_key', '', ['sometimes', 'string'], 'api_key_mask')->label('API Key')->help('Secret value.')->field('password')->publicUsing(fn ($value): ?string => $value === '' ? null : 'masked-'.mb_substr((string) $value, -2)), ExtensionSettingDefinition::make('page_size', 'page_size', 50, ['sometimes', 'integer'])->field('select', [['value' => 25, 'label' => '25'], ['value' => 50, 'label' => '50']])->frontend()->normalizeUsing(fn ($value): int => (int) $value)]);
}

test('frontend type declarations exclude secrets and reject serialization drift', function (): void {
    $typed = new ExtensionSettingsDefinition(settings(['flag' => true]), [
        ExtensionSettingDefinition::make('flag', 'flag', true)->frontend()->frontendType('boolean'),
        ExtensionSettingDefinition::make('token', 'token', 'private')->secret(),
    ]);
    expect($typed->frontendConfigTypes())->toBe(['flag' => 'boolean']);
    expect($typed->frontendConfig())->toBe(['flag' => true]);
    $bad = new ExtensionSettingsDefinition(settings(['flag' => 'yes']), [
        ExtensionSettingDefinition::make('flag', 'flag', true)->frontend()->frontendType('boolean'),
    ]);
    expect(fn (): array => $bad->frontendConfig())->toThrow(UnexpectedValueException::class, 'declared boolean type');
});
