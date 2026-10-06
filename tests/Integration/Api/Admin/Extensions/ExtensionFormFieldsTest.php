<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Extensions\ExtensionFormFieldsTest;

use Closure;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Pterodactyl\Models\Extension;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionFormFieldRegistry;
use Pterodactyl\Services\Extensions\ExtensionRegistration;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;
use RuntimeException;

use function pterodactylTestCase;

uses(AdminApiIntegrationTestCase::class);

beforeEach(function (): void {
    $this->extensionsDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-form-fields-'.uniqid();
    File::ensureDirectoryExists($this->extensionsDirectory);
    config(['extensions.enabled' => true, 'extensions.directory' => $this->extensionsDirectory]);
    $this->app->make(ExtensionRepository::class)->flushDiscovery();
});

afterEach(function (): void {
    $this->app->make(ExtensionRepository::class)->flushDiscovery();
    File::deleteDirectory($this->extensionsDirectory);
});

test('user form fields are validated, saved to user-scoped settings and loaded back', function (): void {
    installExtension('billing');
    registerFields('billing', 'admin.user', ['plan' => ['required', 'in:gold,silver'], 'notes' => ['nullable', 'string']]);
    $user = User::factory()->create();

    $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, ['billing' => ['plan' => 'gold', 'notes' => 'vip', 'ignored' => 'x']]))
        ->assertOk();

    $settings = $this->app->make(ExtensionRepository::class)->settings('billing')->forUser($user);
    expect($settings->all())->toBe(['notes' => 'vip', 'plan' => 'gold']);
    $this->getJson(route('api.admin.extensions.forms', ['form' => 'admin.user', 'id' => $user->id]))
        ->assertOk()
        ->assertExactJson(['data' => ['billing' => ['notes' => 'vip', 'plan' => 'gold']]]);
});

test('requests without extension values keep the stored values and skip extension rules', function (): void {
    installExtension('billing');
    registerFields('billing', 'admin.user', ['plan' => ['required', 'in:gold,silver']]);
    $user = User::factory()->create();
    $this->app->make(ExtensionRepository::class)->settings('billing')->forUser($user)->set('plan', 'silver');

    $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, null, 'renamed'))
        ->assertOk();

    expect($user->refresh()->username)->toBe('renamed');
    expect($this->app->make(ExtensionRepository::class)->settings('billing')->forUser($user)->all())->toBe(['plan' => 'silver']);
});

test('invalid extension values reject the whole request', function (): void {
    installExtension('billing');
    registerFields('billing', 'admin.user', ['plan' => ['required', 'in:gold,silver']]);
    $user = User::factory()->create();

    $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, ['billing' => ['plan' => 'bronze']], 'renamed'))
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonPath('errors.0.meta.source_field', 'extensions.billing.plan');

    expect($user->refresh()->username)->not->toBe('renamed');
});

test('field values must be scalars or lists of scalars', function (): void {
    installExtension('billing');
    registerFields('billing', 'admin.user', ['plan' => 'nullable', 'tags' => ['array'], 'tags.*' => ['string']]);
    $user = User::factory()->create();

    $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, ['billing' => ['plan' => ['nested' => 'object'], 'tags' => ['a', 'b']]]))
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonPath('errors.0.meta.source_field', 'extensions.billing.plan');

    $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, ['billing' => ['plan' => 'gold', 'tags' => ['a', 'b']]]))
        ->assertOk();

    expect($this->app->make(ExtensionRepository::class)->settings('billing')->forUser($user)->all())->toBe(['plan' => 'gold', 'tags' => ['a', 'b']]);
});

test('loaded values that are not field values are recorded as a failure', function (): void {
    installExtension('regions');
    registerFields('regions', 'admin.location', ['region' => ['string']], fn (): array => ['region' => ['nested' => 'object']], fn (): null => null);
    $location = Location::factory()->create();

    $this->getJson(route('api.admin.extensions.forms', ['form' => 'admin.location', 'id' => $location->id]))
        ->assertOk()
        ->assertExactJson(['data' => []]);

    $this->assertDatabaseHas('extensions', ['identifier' => 'regions']);
    expect(Extension::query()->where('identifier', 'regions')->value('error'))->toContain('region');
});

test('custom callbacks receive the saved resource and only declared fields', function (): void {
    installExtension('regions');
    $saved = [];
    registerFields('regions', 'admin.location', ['region' => ['required', 'string']], fn (Location $location): array => ['region' => 'eu-'.$location->short], function (Location $location, array $values) use (&$saved): void {
        $saved[] = [$location->exists, $location->short, $values];
    });

    $response = $this->postJson(route('api.admin.locations.store'), ['short' => 'lon', 'long' => 'London', 'extensions' => ['regions' => ['region' => 'eu-west', 'extra' => 'x']]]);

    $response->assertCreated();

    expect($saved)->toBe([[true, 'lon', ['region' => 'eu-west']]]);
    $this->getJson(route('api.admin.extensions.forms', ['form' => 'admin.location', 'id' => $response->json('attributes.id')]))
        ->assertOk()
        ->assertExactJson(['data' => ['regions' => ['region' => 'eu-lon']]]);
});

test('a failing save rolls back the core update', function (): void {
    installExtension('regions');
    registerFields('regions', 'admin.location', ['region' => ['required', 'string']], fn (): array => [], function (): void {
        throw new RuntimeException('region store unavailable');
    });
    $location = Location::factory()->create(['short' => 'old']);

    $this->putJson(route('api.admin.locations.update', ['location' => $location->id]), ['short' => 'new', 'extensions' => ['regions' => ['region' => 'eu']]])
        ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);

    expect($location->refresh()->short)->toBe('old');
});

test('a failing load is recorded and leaves the other extensions intact', function (): void {
    installExtension('broken');
    installExtension('regions');
    registerFields('broken', 'admin.location', ['value' => ['string']], function (): array {
        throw new RuntimeException('load failed');
    }, fn (): null => null);
    registerFields('regions', 'admin.location', ['region' => ['string']], fn (): array => ['region' => 'eu'], fn (): null => null);
    $location = Location::factory()->create();

    $this->getJson(route('api.admin.extensions.forms', ['form' => 'admin.location', 'id' => $location->id]))
        ->assertOk()
        ->assertExactJson(['data' => ['regions' => ['region' => 'eu']]]);

    $this->assertDatabaseHas('extensions', ['identifier' => 'broken', 'error' => 'load failed']);
});

test('fields of disabled extensions are neither validated, saved nor loaded', function (): void {
    installExtension('billing');
    registerFields('billing', 'admin.user', ['plan' => ['required', 'in:gold,silver']]);
    Extension::query()->where('identifier', 'billing')->update(['enabled' => false]);
    $this->app->make(ExtensionRepository::class)->flushDiscovery();
    $user = User::factory()->create();

    $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, ['billing' => ['plan' => 'bronze']]))
        ->assertOk();

    expect($this->app->make(ExtensionRepository::class)->settings('billing')->forUser($user)->all())->toBe([]);
    $this->getJson(route('api.admin.extensions.forms', ['form' => 'admin.user', 'id' => $user->id]))
        ->assertOk()
        ->assertExactJson(['data' => []]);
});

test('form values require the read permission of the resource and an existing resource', function (): void {
    installExtension('billing');
    registerFields('billing', 'admin.user', ['plan' => ['string']]);
    $user = User::factory()->create();

    $this->getJson('/api/admin/extensions/forms/admin.unknown/'.$user->id)->assertNotFound();
    $this->getJson(route('api.admin.extensions.forms', ['form' => 'admin.user', 'id' => 0]))->assertNotFound();
    $this->actingAsNonAdmin();
    $this->getJson(route('api.admin.extensions.forms', ['form' => 'admin.user', 'id' => $user->id]))->assertForbidden();
});

test('the registry lists the forms of active registrations only', function (): void {
    $registry = $this->app->make(ExtensionFormFieldRegistry::class);
    $active = $this->app->make(ExtensionRegistration::class);
    $inactive = $this->app->make(ExtensionRegistration::class);
    $inactive->discard();

    $registry->register('billing', 'admin.user', ['plan' => ['string']], null, null, $active);
    $registry->register('billing', 'admin.server', ['plan' => ['string']], null, null, $active);
    $registry->register('pending', 'admin.user', ['plan' => ['string']], null, null, $inactive);

    expect($registry->forms('billing'))->toBe(['admin.user', 'admin.server']);
    expect($registry->forms('pending'))->toBe([]);
});

test('the registry rejects invalid registrations', function (string $form, array $rules, ?Closure $load, ?Closure $save, string $message): void {
    $registry = $this->app->make(ExtensionFormFieldRegistry::class);

    expect(fn () => $registry->register('billing', $form, $rules, $load, $save, $this->app->make(ExtensionRegistration::class)))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'unknown form' => ['admin.unknown', ['plan' => ['string']], null, null, 'unknown form'],
    'no fields' => ['admin.user', [], null, null, 'at least one field'],
    'one callback' => ['admin.location', ['plan' => ['string']], fn (): array => [], null, 'both a load and a save callback'],
    'no default storage' => ['admin.location', ['plan' => ['string']], null, null, 'has no default storage'],
    'invalid key' => ['admin.user', ['Plan Name' => ['string']], null, null, 'must match'],
]);

function installExtension(string $identifier): void
{
    (function () use ($identifier): void {
        $path = $this->extensionsDirectory.DIRECTORY_SEPARATOR.$identifier;
        File::ensureDirectoryExists($path);
        File::put($path.DIRECTORY_SEPARATOR.'extension.json', json_encode(['id' => $identifier, 'name' => $identifier, 'version' => '1.0.0'], JSON_THROW_ON_ERROR));
        Extension::query()->create(['identifier' => $identifier, 'version' => '1.0.0', 'enabled' => true]);
        $this->app->make(ExtensionRepository::class)->flushDiscovery();
    })->call(pterodactylTestCase());
}

function registerFields(string $identifier, string $form, array $rules, ?Closure $load = null, ?Closure $save = null): void
{
    (function () use ($identifier, $form, $rules, $load, $save): void {
        $this->app->make(ExtensionFormFieldRegistry::class)->register($identifier, $form, $rules, $load, $save, $this->app->make(ExtensionRegistration::class));
    })->call(pterodactylTestCase());
}

function userPayload(User $user, ?array $extensions, ?string $username = null): array
{
    $payload = ['username' => $username ?? $user->username, 'email' => $user->email, 'name_first' => $user->name_first, 'name_last' => $user->name_last];

    return $extensions === null ? $payload : [...$payload, 'extensions' => $extensions];
}
