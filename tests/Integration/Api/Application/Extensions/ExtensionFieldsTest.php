<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Application\Extensions\ExtensionFieldsTest;

use Illuminate\Support\Facades\File;
use Pterodactyl\Extensions\Attributes\ApplicationApi;
use Pterodactyl\Extensions\Fields;
use Pterodactyl\Models\Extension;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionFieldRegistry;
use Pterodactyl\Services\Extensions\ExtensionRegistration;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Tests\Integration\Api\Application\ApplicationApiIntegrationTestCase;

uses(ApplicationApiIntegrationTestCase::class);

#[ApplicationApi]
final class PlanFields extends Fields
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['plan' => ['required', 'in:free,pro']];
    }
}

final class NotesFields extends Fields
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['notes' => ['string']];
    }
}

beforeEach(function (): void {
    $this->extensionsDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-application-fields-'.uniqid();
    config(['extensions.enabled' => true, 'extensions.directory' => $this->extensionsDirectory]);
    foreach (['billing' => PlanFields::class, 'notes' => NotesFields::class] as $identifier => $fields) {
        File::ensureDirectoryExists($this->extensionsDirectory.DIRECTORY_SEPARATOR.$identifier);
        File::put($this->extensionsDirectory.DIRECTORY_SEPARATOR.$identifier.DIRECTORY_SEPARATOR.'extension.json', json_encode(['id' => $identifier, 'name' => $identifier, 'version' => '1.0.0'], JSON_THROW_ON_ERROR));
        Extension::query()->create(['identifier' => $identifier, 'version' => '1.0.0', 'enabled' => true]);
        $this->app->make(ExtensionFieldRegistry::class)->register($identifier, User::class, $fields, $this->app->make(ExtensionRegistration::class));
    }

    $this->app->make(ExtensionRepository::class)->flushDiscovery();
});

afterEach(function (): void {
    $this->app->make(ExtensionRepository::class)->flushDiscovery();
    File::deleteDirectory($this->extensionsDirectory);
});

test('the Application API accepts and returns only fields marked for it', function (): void {
    $response = $this->postJson('/api/application/users', [
        'username' => 'billed',
        'email' => 'billed@example.test',
        'first_name' => 'Billed',
        'last_name' => 'User',
        'extensions' => ['billing' => ['plan' => 'pro'], 'notes' => ['notes' => 'ignored']],
    ]);

    $response->assertCreated()->assertJsonPath('attributes.extensions', ['billing' => ['plan' => 'pro']]);
    $user = User::query()->where('username', 'billed')->firstOrFail();
    $repository = $this->app->make(ExtensionRepository::class);
    expect($repository->settings('billing')->for($user)->all())->toBe(['plan' => 'pro']);
    expect($repository->settings('notes')->for($user)->all())->toBe([]);

    $this->patchJson('/api/application/users/'.$user->id, ['username' => 'billed', 'email' => 'billed@example.test', 'first_name' => 'Billed', 'last_name' => 'User', 'extensions' => ['billing' => ['plan' => 'gold']]])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', 'extensions.billing.plan');
});
