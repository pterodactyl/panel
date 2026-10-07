<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Extensions\ExtensionFieldsTest;

use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Pterodactyl\Extensions\Fields;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Models\Extension;
use Pterodactyl\Models\ExtensionSetting;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionFieldRegistry;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ExtensionRegistration;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;
use RuntimeException;

use function pterodactylTestCase;

uses(AdminApiIntegrationTestCase::class);

final class BillingFields extends Fields
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'plan' => ['required', 'in:free,pro'],
            'invoice_email' => ['nullable', 'required_if:plan,pro', 'email'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['invoice_email' => 'invoice email'];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['plan.in' => 'Pick a plan we sell.'];
    }
}

/** Keeps its values in its own storage, here a static array standing in for a table. */
final class RoleFields extends Fields
{
    /** @var array<int, string|null> */
    public static array $roles = [];

    public static int $transactionLevel = 0;

    public static ?int $validatedFor = null;

    /** @return array<string, list<string>> */
    public function rules(?User $user): array
    {
        self::$validatedFor = $user?->id;

        return ['role' => ['nullable', 'in:admin,support']];
    }

    /** @return array{role: string|null} */
    public function values(User $user): array
    {
        return ['role' => self::$roles[$user->id] ?? null];
    }

    /** @param array{role?: string|null} $values */
    public function save(User $user, array $values): void
    {
        self::$transactionLevel = DB::transactionLevel();
        self::$roles[$user->id] = $values['role'] ?? null;
    }
}

final class LockedFields extends Fields
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['tier' => ['string']];
    }

    public function authorize(#[CurrentUser] User $admin): bool
    {
        return $admin->email === 'billing@example.test';
    }
}

final class BrokenFields extends Fields
{
    /** @return array<string, string> */
    public function values(Model $model): array
    {
        throw new RuntimeException('The billing system is down.');
    }

    /** @param array<string, string> $values */
    public function save(Model $model, array $values): void {}
}

final class NestedFields extends Fields
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['meta' => ['array']];
    }
}

final class ValuesOnlyFields extends Fields
{
    /** @return array<string, string> */
    public function values(Model $model): array
    {
        return [];
    }
}

beforeEach(function (): void {
    $this->extensionsDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-fields-'.uniqid();
    File::ensureDirectoryExists($this->extensionsDirectory);
    config(['extensions.enabled' => true, 'extensions.directory' => $this->extensionsDirectory]);
    $this->app->make(ExtensionRepository::class)->flushDiscovery();
    RoleFields::$roles = [];
    RoleFields::$transactionLevel = 0;
    RoleFields::$validatedFor = null;
});

afterEach(function (): void {
    $this->app->make(ExtensionRepository::class)->flushDiscovery();
    File::deleteDirectory($this->extensionsDirectory);
});

test('an extension validates its values against its own rules, beside the core rules', function (): void {
    install('billing', User::class, BillingFields::class);
    $user = User::factory()->create();

    $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, ['email' => 'not-an-email', 'extensions' => ['billing' => ['plan' => 'gold']]]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', 'email')
        ->assertJsonPath('errors.1.meta.source_field', 'extensions.billing.plan')
        ->assertJsonPath('errors.1.detail', 'Pick a plan we sell.');

    $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, ['extensions' => ['billing' => ['plan' => 'pro']]]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', 'extensions.billing.invoice_email')
        ->assertJsonPath('errors.0.detail', 'The invoice email field is required when plan is pro.');

    $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, ['extensions' => ['billing' => ['plan' => 'pro', 'invoice_email' => 'bills@example.test']]]))
        ->assertOk()
        ->assertJsonPath('attributes.extensions.billing', ['plan' => 'pro', 'invoice_email' => 'bills@example.test']);
});

test('an extension that keeps its own values reads and saves them, saving inside the transaction', function (): void {
    install('roles', User::class, RoleFields::class);
    $user = User::factory()->create();

    $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, ['extensions' => ['roles' => ['role' => 'support']]]))
        ->assertOk()
        ->assertJsonPath('attributes.extensions.roles.role', 'support');

    expect(RoleFields::$validatedFor)->toBe($user->id);
    expect(RoleFields::$roles[$user->id])->toBe('support');
    expect(RoleFields::$transactionLevel)->toBeGreaterThan(0);
    expect(ExtensionSetting::query()->where('extension', 'roles')->exists())->toBeFalse();
    $this->getJson(route('api.admin.users.view', ['user' => $user->id]))->assertJsonPath('attributes.extensions.roles.role', 'support');
});

test('values that are not strings, numbers, booleans or lists of them are refused', function (): void {
    install('nested', User::class, NestedFields::class);
    $user = User::factory()->create();

    $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, ['extensions' => ['nested' => ['meta' => ['key' => 'value']]]]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', 'extensions.nested.meta');
});

test('extension input that is not keyed by extension and field is refused', function (mixed $extensions, string $field): void {
    install('billing', User::class, BillingFields::class);
    $user = User::factory()->create();

    $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, ['extensions' => $extensions]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', $field);
})->with([
    'a string' => ['billing', 'extensions'],
    'a list' => [[['plan' => 'pro']], 'extensions'],
    'values that are a string' => [['billing' => 'pro'], 'extensions.billing'],
]);

test('values for an extension that is not running are ignored and keep what was stored', function (): void {
    install('billing', User::class, BillingFields::class);
    $user = User::factory()->create();
    $this->app->make(ExtensionRepository::class)->settings('billing')->for($user)->set('plan', 'pro');
    Extension::query()->where('identifier', 'billing')->update(['enabled' => false]);
    $this->app->make(ExtensionRepository::class)->flushDiscovery();

    $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, ['extensions' => ['billing' => ['plan' => 'gold'], 'unknown' => ['x' => 1]]]))
        ->assertOk()
        ->assertJsonPath('attributes.extensions', []);

    expect($this->app->make(ExtensionRepository::class)->settings('billing')->for($user)->get('plan'))->toBe('pro');
});

test('a user an extension does not authorize can neither send nor see its values', function (): void {
    install('locked', User::class, LockedFields::class);
    $user = User::factory()->create();

    $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, ['extensions' => ['locked' => ['tier' => 'gold']]]))
        ->assertForbidden();
    $this->getJson(route('api.admin.users.view', ['user' => $user->id]))
        ->assertOk()
        ->assertJsonMissingPath('attributes.extensions.locked');
    expect($this->app->make(ExtensionFields::class)->forms())->toBe([]);

    $this->actingAs(User::factory()->create(['root_admin' => true, 'email' => 'billing@example.test']));
    $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, ['extensions' => ['locked' => ['tier' => 'gold']]]))
        ->assertOk()
        ->assertJsonPath('attributes.extensions.locked.tier', 'gold');
    expect($this->app->make(ExtensionFields::class)->forms())->toBe(['admin.user' => [['id' => 'locked', 'name' => 'Locked']]]);
});

test('an extension that cannot read its values is left out and recorded as failing', function (): void {
    install('broken', User::class, BrokenFields::class);
    install('billing', User::class, BillingFields::class);
    $user = User::factory()->create();

    $this->getJson(route('api.admin.users.view', ['user' => $user->id]))
        ->assertOk()
        ->assertJsonPath('attributes.extensions', ['billing' => ['plan' => null, 'invoice_email' => null]]);

    expect(Extension::query()->where('identifier', 'broken')->value('error'))->toContain('The billing system is down.');
});

test('values the panel stores for a model are deleted with it', function (): void {
    install('regions', Location::class, BillingFields::class);
    $location = Location::factory()->create();
    $this->app->make(ExtensionRepository::class)->settings('regions')->for($location)->set('plan', 'pro');

    $location->delete();

    expect(ExtensionSetting::query()->where('extension', 'regions')->exists())->toBeFalse();
});

test('the page bootstrap lists the extensions each admin form shows, for root administrators only', function (): void {
    install('billing', User::class, BillingFields::class);
    install('roles', Location::class, RoleFields::class);

    $this->get('/')->assertOk()->assertSee('"extensionForms":{"admin.user":[{"id":"billing","name":"Billing"}],"admin.location":[{"id":"roles","name":"Roles"}]}', false);
    $this->actingAs(User::factory()->create())->get('/')->assertOk()->assertSee('"extensionForms":{}', false);
});

test('the registry refuses fields it cannot run', function (string $model, string $fields, string $message): void {
    expect(fn () => $this->app->make(ExtensionFieldRegistry::class)->register('billing', $model, $fields, $this->app->make(ExtensionRegistration::class)))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'a model without fields' => [ApiKey::class, BillingFields::class, 'only users, servers'],
    'a class that is not Fields' => [User::class, User::class, 'does not extend'],
    'values() without save()' => [User::class, ValuesOnlyFields::class, 'both values() and save()'],
]);

test('an extension registers one Fields class per model', function (): void {
    $registry = $this->app->make(ExtensionFieldRegistry::class);
    $registry->register('billing', User::class, BillingFields::class, $this->app->make(ExtensionRegistration::class));

    expect(fn () => $registry->register('billing', User::class, RoleFields::class, $this->app->make(ExtensionRegistration::class)))
        ->toThrow(InvalidArgumentException::class, 'more than once');
});

test('fields of a provider that failed to boot are not active', function (): void {
    $registry = $this->app->make(ExtensionFieldRegistry::class);
    $failed = $this->app->make(ExtensionRegistration::class);
    $failed->discard();

    $registry->register('billing', User::class, BillingFields::class, $this->app->make(ExtensionRegistration::class));
    $registry->register('pending', User::class, BillingFields::class, $failed);

    expect(array_keys($registry->for(User::class)))->toBe(['billing']);
});

/**
 * @param  class-string<Model>  $model
 * @param  class-string<Fields>  $fields
 */
function install(string $identifier, string $model, string $fields): void
{
    (function () use ($identifier, $model, $fields): void {
        $path = $this->extensionsDirectory.DIRECTORY_SEPARATOR.$identifier;
        File::ensureDirectoryExists($path);
        File::put($path.DIRECTORY_SEPARATOR.'extension.json', json_encode(['id' => $identifier, 'name' => ucfirst($identifier), 'version' => '1.0.0'], JSON_THROW_ON_ERROR));
        Extension::query()->create(['identifier' => $identifier, 'version' => '1.0.0', 'enabled' => true]);
        $this->app->make(ExtensionRepository::class)->flushDiscovery();
        $this->app->make(ExtensionFieldRegistry::class)->register($identifier, $model, $fields, $this->app->make(ExtensionRegistration::class));
    })->call(pterodactylTestCase());
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function userPayload(User $user, array $overrides = []): array
{
    return [...['username' => $user->username, 'email' => $user->email, 'name_first' => $user->name_first, 'name_last' => $user->name_last], ...$overrides];
}
