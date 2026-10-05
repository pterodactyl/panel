<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Settings\SettingsControllerTest;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;
use Pterodactyl\Models\Setting;
use Pterodactyl\Notifications\MailTested;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;

uses(AdminApiIntegrationTestCase::class);
/** Endpoints that should return a 403 error when accessed by a non-root-administrator. */
dataset('settingsEndpointsDataProvider', function () {
    return [['getJson', 'api.admin.languages'], ['getJson', 'api.admin.settings'], ['putJson', 'api.admin.settings.general'], ['putJson', 'api.admin.settings.mail'], ['postJson', 'api.admin.settings.mail.test'], ['putJson', 'api.admin.settings.advanced']];
});
test('get settings', function () {
    $response = $this->getJson(route('api.admin.settings'));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonStructure(['general' => ['app:name', 'pterodactyl:auth:2fa_required', 'app:locale'], 'mail' => ['mail:mailers:smtp:host', 'mail:mailers:smtp:port', 'mail:mailers:smtp:encryption', 'mail:mailers:smtp:username', 'mail:from:address', 'mail:from:name'], 'advanced' => ['recaptcha:enabled', 'recaptcha:secret_key', 'recaptcha:website_key', 'pterodactyl:guzzle:timeout', 'pterodactyl:guzzle:connect_timeout', 'pterodactyl:client_features:allocations:enabled'], 'meta' => ['load_environment_only', 'show_recaptcha_warning']]);
    // The SMTP password must always be redacted to an empty string.
    $response->assertJsonPath('mail.mail:mailers:smtp:password', '');
});
test('get languages', function () {
    $response = $this->getJson(route('api.admin.languages'));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonStructure(['en']);
});
test('update general', function () {
    $response = $this->putJson(route('api.admin.settings.general'), validGeneralPayload());
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseHas('settings', ['key' => 'settings::app:name', 'value' => 'Test Panel']);
    expect(Setting::fetch('settings::app:name'))->toBe('Test Panel');
    expect((string) Setting::fetch('settings::pterodactyl:auth:2fa_required'))->toBe('1');
    expect(Setting::fetch('settings::app:locale'))->toBe('en');
});
test('update mail', function () {
    // Mail settings only update when SMTP is the selected driver, mirroring the legacy MailController guard.
    config()->set('mail.default', 'smtp');
    $response = $this->putJson(route('api.admin.settings.mail'), validMailPayload());
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseHas('settings', ['key' => 'settings::mail:mailers:smtp:host']);
    $stored = Setting::fetch('settings::mail:mailers:smtp:password');
    expect($stored)->not->toBeNull();
    // The stored value must be encrypted, not the plaintext password.
    $this->assertNotSame('super-secret', $stored);
    expect($this->app->make(Encrypter::class)->decrypt($stored))->toBe('super-secret');
    // The plaintext password must not appear anywhere in the response body.
    $this->assertStringNotContainsString('super-secret', $response->getContent());
});
test('mail test', function () {
    Notification::fake();
    $response = $this->postJson(route('api.admin.settings.mail.test'));
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    Notification::assertSentOnDemand(MailTested::class);
});
test('update advanced', function () {
    $response = $this->putJson(route('api.admin.settings.advanced'), validAdvancedPayload());
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseHas('settings', ['key' => 'settings::recaptcha:secret_key', 'value' => 'secret-key']);
    expect(Setting::fetch('settings::recaptcha:secret_key'))->toBe('secret-key');
    expect((string) Setting::fetch('settings::pterodactyl:client_features:allocations:range_start'))->toBe('5000');
});
test('invalid payloads return validation errors', function () {
    $response = $this->putJson(route('api.admin.settings.general'), []);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonStructure(['errors' => [['code', 'detail', 'meta' => ['source_field', 'rule']]]]);
    $errors = collect($response->json('errors'));
    $error = $errors->firstWhere('meta.source_field', 'branding.name');
    expect($error)->not->toBeNull('Expected a validation error for the [branding.name] field.');
    expect($error['meta']['rule'])->toBe('required');
    expect($error['detail'])->not->toBeEmpty();
});
test('non admin forbidden', function (string $method, string $routeName) {
    $this->actingAsNonAdmin();
    $response = $this->{$method}(route($routeName));
    $this->assertAccessDeniedJson($response);
})->with('settingsEndpointsDataProvider');
/** Return a valid payload for updating the general Panel settings. */
function validGeneralPayload(): array
{
    return ['branding' => ['name' => 'Test Panel'], 'misc' => ['required_2fa' => 1], 'default_locale' => 'en'];
}
/** Return a valid payload for updating the SMTP mail settings (nested smtp shape). */
function validMailPayload(): array
{
    return ['smtp' => ['host' => 'smtp.example.com', 'port' => 587, 'encryption' => 'tls', 'username' => 'smtp-user', 'password' => 'super-secret', 'from_address' => 'noreply@example.com', 'from_name' => 'Test Panel']];
}
/** Return a valid payload for updating the advanced Panel settings. */
function validAdvancedPayload(): array
{
    return ['recaptcha:enabled' => 'true', 'recaptcha:secret_key' => 'secret-key', 'recaptcha:website_key' => 'website-key', 'pterodactyl:guzzle:timeout' => 15, 'pterodactyl:guzzle:connect_timeout' => 5, 'pterodactyl:client_features:allocations:enabled' => 'true', 'pterodactyl:client_features:allocations:range_start' => 5000, 'pterodactyl:client_features:allocations:range_end' => 6000];
}
