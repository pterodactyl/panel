<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\Subuser\CreateServerSubuserTest;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

uses(ClientApiIntegrationTestCase::class);
uses(WithFaker::class);
dataset('permissionsDataProvider', fn (): array => [[[]], [[Permissions::UserCreate->value]]]);
test('subuser can be created', function (array $permissions): void {
    [$user, $server] = $this->generateTestAccount($permissions);
    $response = $this->actingAs($user)->postJson($this->link($server).'/users', ['email' => $email = $this->faker->email, 'permissions' => [Permissions::UserCreate->value]]);
    $response->assertOk();
    /** @var User $subuser */
    $subuser = User::query()->where('email', $email)->firstOrFail();
    $response->assertJsonPath('object', Subuser::RESOURCE_NAME);
    $response->assertJsonPath('attributes.uuid', $subuser->uuid);
    $response->assertJsonPath('attributes.permissions', [Permissions::UserCreate->value, Permissions::WebsocketConnect->value]);

    expect($response->json('attributes'))->toBe([
        'uuid' => $subuser->uuid,
        'identifier' => $subuser->identifier,
        'username' => $subuser->username,
        'email' => $email,
        'image' => 'https://gravatar.com/avatar/'.md5(Str::lower($email)),
        '2fa_enabled' => false,
        'created_at' => $subuser->created_at->toAtomString(),
        'permissions' => [Permissions::UserCreate->value, Permissions::WebsocketConnect->value],
    ]);
})->with('permissionsDataProvider');
test('inviting a new email address logs the user it creates', function (): void {
    [$user, $server] = $this->generateTestAccount();
    $this->actingAs($user)->postJson($this->link($server).'/users', ['email' => $email = $this->faker->email, 'permissions' => [Permissions::UserCreate->value]])->assertOk();

    $created = User::query()->where('email', $email)->firstOrFail();
    $log = $created->activity()->where('event', 'user:user.create')->sole();
    expect($log->actor_id)->toBe($user->id)
        ->and($log->properties->get('email'))->toBe($email);
});
test('inviting an existing user does not log a user creation', function (): void {
    [$user, $server] = $this->generateTestAccount();
    $existing = User::factory()->create();
    $this->actingAs($user)->postJson($this->link($server).'/users', ['email' => $existing->email, 'permissions' => [Permissions::UserCreate->value]])->assertOk();

    expect($existing->activity()->where('event', 'user:user.create')->exists())->toBeFalse()
        ->and($existing->activity()->where('event', 'server:subuser.create')->exists())->toBeTrue();
});
test('error is returned if assigning permissions not assigned to self', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::UserCreate->value, Permissions::UserRead->value, Permissions::ControlConsole->value]);
    $response = $this->actingAs($user)->postJson($this->link($server).'/users', ['email' => $this->faker->email, 'permissions' => [Permissions::UserCreate->value, Permissions::UserUpdate->value]]);
    $response->assertForbidden();
    $response->assertJsonPath('errors.0.code', 'HttpForbiddenException');
    $response->assertJsonPath('errors.0.detail', 'Cannot assign permissions to a subuser that your account does not actively possess.');
});
test('subuser with excessively long email cannot be created', function (): void {
    [$user, $server] = $this->generateTestAccount();
    // RFCs limit the local part to 64 characters and each domain label to 63, so this
    // address is as long as the 191-character column allows while staying valid.
    $local = str_repeat(Str::random(10), 6).'1234';
    $label = str_repeat(Str::random(10), 6).'1';
    $email = "{$local}@{$label}.{$label}.au";
    expect(mb_strlen($email))->toBe(191);
    $this->actingAs($user)->postJson($this->link($server).'/users', ['email' => $email, 'permissions' => [Permissions::UserCreate->value]])->assertOk();
    $response = $this->actingAs($user)->postJson($this->link($server).'/users', ['email' => "{$local}@{$label}.{$label}.com", 'permissions' => [Permissions::UserCreate->value]]);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.detail', 'The email must be between 1 and 191 characters.');
    $response->assertJsonPath('errors.0.meta.source_field', 'email');
});
test('subuser email must be a strict address that does not start with a dash', function (string $email): void {
    [$user, $server] = $this->generateTestAccount();
    $this->actingAs($user)->postJson($this->link($server).'/users', ['email' => $email, 'permissions' => [Permissions::UserCreate->value]])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', 'email');
    $this->assertDatabaseMissing('users', ['email' => $email]);
})->with([
    'dash prefix' => ['-x@example.com'],
    'quoted dash prefix' => ['"-x"@example.com'],
    'local part over 64 characters' => [str_repeat('a', 65).'@example.com'],
]);
test('creating subuser with same email as existing user works', function (): void {
    [$user, $server] = $this->generateTestAccount();
    /** @var User $existing */
    $existing = User::factory()->create(['email' => $this->faker->email]);
    $response = $this->actingAs($user)->postJson($this->link($server).'/users', ['email' => $existing->email, 'permissions' => [Permissions::UserCreate->value]]);
    $response->assertOk();
    $response->assertJsonPath('object', Subuser::RESOURCE_NAME);
    $response->assertJsonPath('attributes.uuid', $existing->uuid);
});
test('adding subuser that already is assigned returns error', function (): void {
    [$user, $server] = $this->generateTestAccount();
    $response = $this->actingAs($user)->postJson($this->link($server).'/users', ['email' => $email = $this->faker->email, 'permissions' => [Permissions::UserCreate->value]]);
    $response->assertOk();
    $response = $this->actingAs($user)->postJson($this->link($server).'/users', ['email' => $email, 'permissions' => [Permissions::UserCreate->value]]);
    $response->assertStatus(Response::HTTP_BAD_REQUEST);
    $response->assertJsonPath('errors.0.code', 'ServerSubuserExistsException');
    $response->assertJsonPath('errors.0.detail', 'A user with that email address is already assigned as a subuser for this server.');
});
