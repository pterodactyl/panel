<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Extensions\ExtensionSettingsRegistryTest;

use Pterodactyl\Services\Extensions\ExtensionSettingDefinition;
use Pterodactyl\Services\Extensions\ExtensionSettings;
use Pterodactyl\Services\Extensions\ExtensionSettingsDefinition;
use Pterodactyl\Services\Extensions\ExtensionSettingsRegistry;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);
test('registry stores and returns definitions by identifier', function () {
    $registry = new ExtensionSettingsRegistry();
    $definition = new ExtensionSettingsDefinition(new ExtensionSettings('demo'), [ExtensionSettingDefinition::make('flag', 'flag', true)]);
    expect($registry->has('demo'))->toBeFalse();
    expect($registry->get('demo'))->toBeNull();
    $registry->register('demo', $definition);
    expect($registry->has('demo'))->toBeTrue();
    expect($registry->get('demo'))->toBe($definition);
});
