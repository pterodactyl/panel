<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Extensions\ExtensionFieldsTest;

use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Pterodactyl\Contracts\Users\UpdatesUsers;
use Pterodactyl\Extensions\Fields;
use Pterodactyl\Http\Requests\Concerns\ValidatesExtensionFields;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Models\Extension;
use Pterodactyl\Models\ExtensionSetting;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionFieldRegistry;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ExtensionRegistration;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Support\JsonEmptyObject;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;
use Pterodactyl\Transformers\Api\Admin\UserTransformer;
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
        return ['tier' => ['required', 'string']];
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
    public function save(Model $model, array $values): void
    {
        // Never reached: reading the values fails first.
    }
}

final class NestedFields extends Fields
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['meta' => ['array']];
    }
}

/** Stores a credential the panel keeps for it. */
final class VaultFields extends Fields
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['api_key' => ['nullable', 'string', 'min:12'], 'region' => ['nullable', 'string']];
    }

    /** @return list<string> */
    public function secrets(): array
    {
        return ['api_key'];
    }
}

/** Keeps a credential in its own storage, here a static property standing in for a table. */
final class TokenFields extends Fields
{
    public static ?string $token = null;

    public static ?string $saved = null;

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['token' => ['required', 'string', 'min:12']];
    }

    /** @return list<string> */
    public function secrets(): array
    {
        return ['token'];
    }

    /** @return array<string, string|null> */
    public function values(Model $model): array
    {
        return ['token' => self::$token];
    }

    /** @param array<string, string> $values */
    public function save(Model $model, array $values): void
    {
        self::$saved = $values['token'];
        self::$token = $values['token'];
    }
}

/** Keeps an optional credential in its own storage, here a static property standing in for a table. */
final class OptionalTokenFields extends Fields
{
    public static ?string $token = null;

    /** @var array<string, mixed>|null */
    public static ?array $saved = null;

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['token' => ['nullable', 'string']];
    }

    /** @return list<string> */
    public function secrets(): array
    {
        return ['token'];
    }

    /** @return array<string, string|null> */
    public function values(Model $model): array
    {
        return ['token' => self::$token];
    }

    /** @param array<string, mixed> $values */
    public function save(Model $model, array $values): void
    {
        self::$saved = $values;
    }
}

/** Has a secret, so validating reads the stored values, which fails. */
final class BrokenSecretFields extends Fields
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['token' => ['nullable', 'string']];
    }

    /** @return list<string> */
    public function secrets(): array
    {
        return ['token'];
    }

    /** @return array<string, string> */
    public function values(Model $model): array
    {
        throw new RuntimeException('The vault is sealed: password=hunter2.');
    }

    /** @param array<string, string> $values */
    public function save(Model $model, array $values): void
    {
        // Never reached: validating fails first.
    }
}

/** Builds its own validator, which FormRequest uses in place of the default one. */
final class CustomValidatorRequest extends FormRequest
{
    use ValidatesExtensionFields;

    public function authorize(): bool
    {
        return true;
    }

    public function validator(ValidationFactory $factory): Validator
    {
        return $factory->make($this->all(), ['name' => ['required', 'string']]);
    }

    protected function extensionFieldsModel(): Model|string
    {
        return User::class;
    }
}

/** Means the signed-in user, but asks for the model being changed. */
final class ActorAsModelFields extends Fields
{
    public function authorize(User $admin): bool
    {
        return $admin->root_admin;
    }
}

final class OtherModelFields extends Fields
{
    /** @return array<string, list<string>> */
    public function rules(?Server $server): array
    {
        return [];
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
    $this->app->make(ExtensionRepository::class)->settings('billing')->fields($user)->set('plan', 'pro');
    Extension::query()->where('identifier', 'billing')->update(['enabled' => false]);
    $this->app->make(ExtensionRepository::class)->flushDiscovery();

    $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, ['extensions' => ['billing' => ['plan' => 'gold'], 'unknown' => ['x' => 1]]]))
        ->assertOk()
        ->assertJsonPath('attributes.extensions', []);

    expect($this->app->make(ExtensionRepository::class)->settings('billing')->fields($user)->get('plan'))->toBe('pro');
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

    // The exception's message can carry credentials, so only the log has it.
    expect(Extension::query()->where('identifier', 'broken')->value('error'))->toBe('Reading its User fields failed with RuntimeException; the log has the details.');

    // Every read fails the same way, and the failure is written once.
    $recorded = Extension::query()->where('identifier', 'broken')->value('updated_at');
    $this->travel(5)->minutes();
    $this->getJson(route('api.admin.users.view', ['user' => $user->id]))->assertOk();
    expect(Extension::query()->where('identifier', 'broken')->value('updated_at'))->toEqual($recorded);
});

test('values the panel stores for a model are deleted with it', function (): void {
    install('regions', Location::class, BillingFields::class);
    $location = Location::factory()->create();
    $this->app->make(ExtensionRepository::class)->settings('regions')->fields($location)->set('plan', 'pro');

    $location->delete();

    expect(ExtensionSetting::query()->where('extension', 'regions')->exists())->toBeFalse();
});

test('the admin API lists the extensions each admin form shows, for root administrators only', function (): void {
    install('billing', User::class, BillingFields::class);
    install('roles', Location::class, NestedFields::class);

    // Pages never run extension field code, so their bootstrap does not carry the list.
    $this->get('/')->assertOk()->assertDontSee('extensionForms', false);

    $this->getJson(route('api.admin.extensions.forms'))
        ->assertOk()
        ->assertExactJson(['data' => ['admin.user' => [['id' => 'billing', 'name' => 'Billing']], 'admin.location' => [['id' => 'roles', 'name' => 'Roles']]]]);

    config()->set('extensions.enabled', false);
    expect($this->getJson(route('api.admin.extensions.forms'))->assertOk()->getContent())->toBe('{"data":{}}');

    $this->actingAs(User::factory()->create())->getJson(route('api.admin.extensions.forms'))->assertForbidden();
});

test('the admin API lists no extension forms when no extension adds fields', function (): void {
    expect($this->getJson(route('api.admin.extensions.forms'))->assertOk()->getContent())->toBe('{"data":{}}');
});

test('the registry refuses fields it cannot run', function (string $model, string $fields, string $message): void {
    expect(fn () => $this->app->make(ExtensionFieldRegistry::class)->register('billing', $model, $fields, $this->app->make(ExtensionRegistration::class)))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'a model without fields' => [ApiKey::class, BillingFields::class, 'only users, servers'],
    'a class that is not Fields' => [User::class, User::class, 'does not extend'],
    'values() without save()' => [User::class, ValuesOnlyFields::class, 'both values() and save()'],
    'the model where the signed-in user was meant' => [User::class, ActorAsModelFields::class, 'ActorAsModelFields::authorize() parameter $admin is null while the User is created'],
    'a parameter of another model' => [User::class, OtherModelFields::class, 'OtherModelFields::rules() parameter $server would receive an empty Server'],
]);

test('secret fields are encrypted and masked, kept when a save sends the mask or leaves them out, and cleared when sent empty', function (): void {
    install('vault', User::class, VaultFields::class);
    $user = User::factory()->create();
    $update = fn (array $values) => $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, ['extensions' => ['vault' => $values]]));
    $stored = fn (): array => $this->app->make(ExtensionRepository::class)->settings('vault')->fields($user)->all();

    $update(['api_key' => 'super-secret-key', 'region' => 'eu'])
        ->assertOk()
        ->assertJsonPath('attributes.extensions.vault', ['api_key' => '********', 'region' => 'eu']);
    $row = ExtensionSetting::query()->where('extension', 'vault')->where('key', 'api_key')->firstOrFail();
    expect($row->is_secret)->toBeTrue();
    expect($row->value)->not->toContain('super-secret-key');

    // The mask is shorter than the rule allows, so these only pass because the stored value is checked.
    $update(['api_key' => '********', 'region' => 'us'])->assertOk();
    $update(['region' => 'ap'])->assertOk();
    expect($stored())->toBe(['api_key' => 'super-secret-key', 'region' => 'ap']);

    $update(['api_key' => 'another-secret-key'])->assertOk();
    expect($stored()['api_key'])->toBe('another-secret-key');

    $update(['api_key' => '', 'region' => 'ap'])
        ->assertOk()
        ->assertJsonPath('attributes.extensions.vault.api_key', null);
    expect($stored()['api_key'])->toBeIn([null, '']);

    $update(['api_key' => 'third-secret-key'])->assertOk();
    $update(['api_key' => null])
        ->assertOk()
        ->assertJsonPath('attributes.extensions.vault.api_key', null);
    expect($stored()['api_key'])->toBeNull();

    // An empty secret reads back as null, so sending back what the form loaded clears nothing.
    $update(['api_key' => null])->assertOk();
    expect($stored()['api_key'])->toBeNull();

    // When creating there is nothing to keep, so the mask is not stored as a value.
    $this->postJson(route('api.admin.users.store'), ['email' => 'vault@example.test', 'username' => 'vault', 'name_first' => 'Vault', 'name_last' => 'User', 'extensions' => ['vault' => ['api_key' => '********']]])
        ->assertCreated()
        ->assertJsonPath('attributes.extensions.vault.api_key', null);
});

test('a secret an extension keeps itself is masked, and save() gets the stored value back', function (): void {
    install('tokens', User::class, TokenFields::class);
    TokenFields::$token = 'stored-token-value';
    TokenFields::$saved = null;
    $user = User::factory()->create();

    $this->getJson(route('api.admin.users.view', ['user' => $user->id]))->assertJsonPath('attributes.extensions.tokens.token', '********');
    $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, ['extensions' => ['tokens' => ['token' => '********']]]))->assertOk();
    expect(TokenFields::$saved)->toBe('stored-token-value');

    TokenFields::$saved = null;
    $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, ['extensions' => ['tokens' => []]]))->assertOk();
    expect(TokenFields::$saved)->toBe('stored-token-value');
});

test('clearing a required secret fails its rule', function (mixed $cleared): void {
    install('tokens', User::class, TokenFields::class);
    TokenFields::$token = 'stored-token-value';
    TokenFields::$saved = null;
    $user = User::factory()->create();

    $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, ['extensions' => ['tokens' => ['token' => $cleared]]]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', 'extensions.tokens.token');

    expect(TokenFields::$saved)->toBeNull();
    expect(TokenFields::$token)->toBe('stored-token-value');
})->with(['an empty string' => [''], 'null' => [null]]);

test('save() receives a secret sent as null or empty, so the extension can clear it', function (): void {
    install('tokens', User::class, OptionalTokenFields::class);
    OptionalTokenFields::$token = 'stored-token-value';
    OptionalTokenFields::$saved = null;
    $user = User::factory()->create();

    $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, ['extensions' => ['tokens' => ['token' => null]]]))->assertOk();

    expect(OptionalTokenFields::$saved)->toBe(['token' => null]);
});

test('an extension that throws while its values are validated refuses the save and is recorded as failing', function (): void {
    install('broken', User::class, BrokenSecretFields::class);
    $user = User::factory()->create();

    $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, ['username' => 'renamed', 'extensions' => ['broken' => ['token' => '********']]]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', 'extensions.broken')
        ->assertJsonPath('errors.0.detail', 'The broken fields could not be read, so they were not saved.');

    expect($user->refresh()->username)->not->toBe('renamed');
    expect(Extension::query()->where('identifier', 'broken')->value('error'))->toBe('Validating its User fields failed with RuntimeException; the log has the details.');
});

test('creating a model runs the rules of every extension the signed-in user may change, sent or not', function (): void {
    install('billing', User::class, BillingFields::class);
    install('locked', User::class, LockedFields::class);
    $payload = ['email' => 'billed@example.test', 'username' => 'billed', 'name_first' => 'Billed', 'name_last' => 'User'];

    $this->postJson(route('api.admin.users.store'), $payload)
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', 'extensions.billing.plan')
        ->assertJsonCount(1, 'errors');

    // locked's tier is required too, but this admin may not change it, so its rules do not run.
    $this->postJson(route('api.admin.users.store'), [...$payload, 'extensions' => ['billing' => ['plan' => 'free']]])
        ->assertCreated()
        ->assertJsonPath('attributes.extensions', ['billing' => ['plan' => 'free', 'invoice_email' => null]]);

    $this->actingAs(User::factory()->create(['root_admin' => true, 'email' => 'billing@example.test']));
    $this->postJson(route('api.admin.users.store'), [...$payload, 'email' => 'other@example.test', 'username' => 'other', 'extensions' => ['billing' => ['plan' => 'free']]])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', 'extensions.locked.tier');
});

test('extension values are validated for a request that builds its own validator', function (): void {
    install('billing', User::class, BillingFields::class);
    $resolve = function (array $input): CustomValidatorRequest {
        $request = CustomValidatorRequest::create('/', 'POST', $input);
        $request->setContainer($this->app)->setRedirector($this->app->make(Redirector::class));
        $request->validateResolved();

        return $request;
    };

    try {
        $resolve(['extensions' => ['billing' => ['plan' => 'gold']]]);
        $this->fail('Expected the request to fail validation.');
    } catch (ValidationException $exception) {
        expect(array_keys($exception->errors()))->toBe(['name', 'extensions.billing.plan']);
    }

    expect($resolve(['name' => 'ok', 'extensions' => ['billing' => ['plan' => 'free']]])->extensionValues()->all())->toBe(['billing' => ['plan' => 'free']]);
});

test("field values the panel stores stay apart from the extension's settings for the same model", function (): void {
    install('billing', User::class, BillingFields::class);
    $user = User::factory()->create();

    $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, ['extensions' => ['billing' => ['plan' => 'free']]]))->assertOk();

    $settings = $this->app->make(ExtensionRepository::class)->settings('billing');
    expect($settings->fields($user)->all())->toBe(['plan' => 'free']);
    expect($settings->for($user)->all())->toBe([]);
});

test('malformed values of a running extension are a validation error, and those of others are ignored', function (): void {
    install('billing', User::class, BillingFields::class);
    $user = User::factory()->create();
    $nested = ['plan'];
    for ($depth = 0; $depth < 12; $depth++) {
        $nested = [$nested];
    }

    $update = fn (array $extensions) => $this->putJson(route('api.admin.users.update', ['user' => $user->id]), userPayload($user, ['extensions' => $extensions]));

    $update(['billing' => [5 => 'pro']])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', 'extensions.billing')
        ->assertJsonPath('errors.0.detail', 'The billing values must be an object keyed by field.');
    $update(['billing' => ['plan' => $nested]])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.detail', 'The billing values are nested too deeply.');
    $update(['missing' => ['plan' => $nested]])->assertOk();
});

test('actions refuse extension values that did not come from a validated request', function (): void {
    install('billing', User::class, BillingFields::class);
    $user = User::factory()->create();

    expect(fn () => $this->app->make(UpdatesUsers::class)->update($user, ['extensions' => ['billing' => ['plan' => 'pro']]]))
        ->toThrow(InvalidArgumentException::class, 'must come from a request that validated them');
});

test('documentation examples carry the extensions attribute without running extension code', function (): void {
    install('broken', User::class, BrokenFields::class);
    $user = User::factory()->create();

    $transformer = $this->app->make(UserTransformer::class)->withExtensionFields(read: false);

    expect($transformer->transform($user)['extensions'])->toBeInstanceOf(JsonEmptyObject::class);
    expect(Extension::query()->where('identifier', 'broken')->value('error'))->toBeNull();
});

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
