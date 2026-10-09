<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Extensions\ExtensionScopedSettingsTest;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use Pterodactyl\Models\ExtensionSetting;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionSettingDefinition;
use Pterodactyl\Services\Extensions\ExtensionSettings;
use Pterodactyl\Services\Extensions\ExtensionSettingsDefinition;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class);
uses(DatabaseTransactions::class);

test('global user and server settings remain isolated across reads batches and deletions', function (): void {
    $first = User::factory()->create();
    $second = User::factory()->create();
    $server = $this->createServerModel();
    $global = new ExtensionSettings('scoped-probe');
    $userSettings = $global->forUser($first);
    $otherUser = $global->forUser($second);
    $serverSettings = $global->forServer($server);
    $global->set('key', 'global');
    $userSettings->set('key', 'first');
    $otherUser->set('key', 'second');
    $serverSettings->set('key', 'server');
    $global->set('prefix.item', 'global-prefix');
    $userSettings->set('prefix.item', 'first-prefix');
    $fresh = [new ExtensionSettings('scoped-probe'), $global->forUser($first), $global->forUser($second), $global->forServer($server)];
    ExtensionSettings::preload($fresh);

    expect(array_map(fn (ExtensionSettings $settings): mixed => $settings->get('key'), $fresh))->toBe(['global', 'first', 'second', 'server']);
    expect($fresh[1]->getByPrefix('prefix.'))->toBe(['item' => 'first-prefix']);
    $fresh[1]->forgetByPrefix('prefix.');
    expect($global->getByPrefix('prefix.'))->toBe(['item' => 'global-prefix']);
    $first->delete();
    expect(ExtensionSetting::query()->where('user_id', $first->id)->exists())->toBeFalse();
    expect($otherUser->get('key'))->toBe('second');
});

test('secret definitions encrypt storage mask responses preserve blank updates and cannot reach frontend config', function (): void {
    $storage = new ExtensionSettings('secret-probe');
    $secret = ExtensionSettingDefinition::make('token', 'token', '', ['string'])->secret();
    $definition = new ExtensionSettingsDefinition($storage, [$secret, ExtensionSettingDefinition::make('label', 'label', 'name')->frontend()]);
    $definition->update(['token' => 'private-token']);

    $row = ExtensionSetting::query()->where('extension', 'secret-probe')->firstOrFail();

    expect($row->is_secret)->toBeTrue();
    expect($row->value)->not->toContain('private-token');
    expect($row->toArray())->not->toHaveKey('value');
    expect((new ExtensionSettings('secret-probe'))->get('token'))->toBe('private-token');
    expect($definition->publicSettings()['token'])->toBe('********');
    expect($definition->schema()[0]['value'])->toBe('********');
    expect($definition->frontendConfig())->toBe(['label' => 'name']);
    expect(fn (): ExtensionSettingDefinition => $secret->frontend())->toThrow(InvalidArgumentException::class);
    expect(fn (): ExtensionSettingDefinition => ExtensionSettingDefinition::make('unsafe', 'unsafe', '')->frontend()->secret())->toThrow(InvalidArgumentException::class);

    $definition->update(['token' => '']);
    expect($definition->get('token'))->toBe('private-token');
    $row->update(['value' => json_encode('tampered', JSON_THROW_ON_ERROR)]);
    expect(fn (): mixed => (new ExtensionSettings('secret-probe'))->get('token'))->toThrow(DecryptException::class);
});

test('secret values survive batch preload and scoped definitions', function (): void {
    $user = User::factory()->create();
    $global = new ExtensionSettings('secret-scope');
    $definition = new ExtensionSettingsDefinition($global, [ExtensionSettingDefinition::make('token', 'token', null)->secret()]);
    $definition->update(['token' => 'global-secret']);
    $definition->forUser($user)->update(['token' => ['nested' => ['value' => 'user-secret']]]);
    $settings = [new ExtensionSettings('secret-scope'), $global->forUser($user)];
    ExtensionSettings::preload($settings);
    expect($settings[0]->get('token'))->toBe('global-secret');
    expect($settings[1]->get('token'))->toBe(['nested' => ['value' => 'user-secret']]);
    expect($definition->forUser($user)->publicSettings()['token'])->toBe('********');
});

test('prefix lookups take a backslash in the prefix literally', function (): void {
    $settings = new ExtensionSettings('prefix-escape');
    $settings->set('path\\one', 'backslash');
    $settings->set('path%two', 'percent');

    expect($settings->getByPrefix('path\\'))->toBe(['one' => 'backslash']);
    $settings->forgetByPrefix('path\\');
    expect($settings->all())->toBe(['path%two' => 'percent']);
});

test('field values are a scope of their own and need a model', function (): void {
    $user = User::factory()->create();
    $settings = new ExtensionSettings('fields-scope');
    $settings->fields($user)->set('plan', 'pro');

    expect($settings->for($user)->all())->toBe([]);
    expect($settings->fields($user)->all())->toBe(['plan' => 'pro']);
    expect(ExtensionSetting::query()->where('extension', 'fields-scope')->value('scope'))->toBe('fields:user:'.$user->id);
    expect(fn (): ExtensionSettings => new ExtensionSettings('fields-scope', fields: true))->toThrow(InvalidArgumentException::class, 'Field values belong to a model.');
});
