<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Settings\LogoControllerTest;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Pterodactyl\Models\Setting;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;

uses(AdminApiIntegrationTestCase::class);

beforeEach(function (): void {
    Storage::fake(config()->string('extensions.files_disk'));
});

function uploadLogo(object $test, string $name = 'logo.png'): string
{
    $url = $test->post(route('api.admin.settings.logo.store'), ['logo' => UploadedFile::fake()->image($name)], ['Accept' => 'application/json'])->assertOk()->json('logo');
    expect($url)->toBeString()->toStartWith('/extension-files/panel-logo/');

    return $url;
}

test('an uploaded logo is stored by name and served as the panel logo', function (): void {
    $url = uploadLogo($this);

    $name = basename($url);
    expect(Setting::fetch('settings::app:logo'))->toBe($name);
    Storage::disk(config()->string('extensions.files_disk'))->assertExists("extension-files/panel-logo/{$name}");
    $this->getJson(route('api.admin.settings'))->assertOk()->assertJsonPath('general.app:logo', $url);
    $this->get($url)->assertOk();
});

test('a new logo replaces and deletes the previous one', function (): void {
    $first = uploadLogo($this);
    $second = uploadLogo($this, 'other.png');

    expect($second)->not->toBe($first);
    Storage::disk(config()->string('extensions.files_disk'))->assertMissing(mb_ltrim($first, '/'));
    Storage::disk(config()->string('extensions.files_disk'))->assertExists(mb_ltrim($second, '/'));
});

test('deleting the logo removes its file and restores the default branding', function (): void {
    uploadLogo($this);

    $this->deleteJson(route('api.admin.settings.logo.destroy'))->assertNoContent();

    expect(Setting::fetch('settings::app:logo'))->toBe('');
    expect(Storage::disk(config()->string('extensions.files_disk'))->allFiles())->toBe([]);
    $this->getJson(route('api.admin.settings'))->assertOk()->assertJsonPath('general.app:logo', null);
});

test('files that are not images are rejected and nothing is stored', function (): void {
    $this->post(route('api.admin.settings.logo.store'), ['logo' => UploadedFile::fake()->createWithContent('logo.png', '<?php echo 1;')], ['Accept' => 'application/json'])->assertUnprocessable();

    expect(Setting::fetch('settings::app:logo'))->toBeNull();
    expect(Storage::disk(config()->string('extensions.files_disk'))->allFiles())->toBe([]);
});

test('the logo is the favicon of every page', function (): void {
    $url = uploadLogo($this);
    auth()->forgetGuards();

    $this->get('/auth/login')->assertOk()
        ->assertSee('<link rel="icon" href="'.$url.'">', false)
        ->assertSee('<link rel="apple-touch-icon" sizes="180x180" href="'.$url.'">', false)
        ->assertDontSee('/favicons/favicon.svg', false);
});

test('pages keep the default favicons without a logo', function (): void {
    auth()->forgetGuards();

    $this->get('/auth/login')->assertOk()->assertSee('/favicons/favicon.svg', false);
});

test('only administrators who can update settings may change the logo', function (string $method, string $route): void {
    $this->actingAsNonAdmin();

    $this->assertAccessDeniedJson($this->{$method}(route($route)));
})->with([['postJson', 'api.admin.settings.logo.store'], ['deleteJson', 'api.admin.settings.logo.destroy']]);
