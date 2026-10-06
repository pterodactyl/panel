<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Http\Controllers\Base\LocaleControllerTest;

use Pterodactyl\Tests\TestCase;

uses(TestCase::class);
test('returns nested translations with frontend placeholders', function () {
    $this->getJson('/locales/locale.json?locale=en&namespace=auth')->assertOk()->assertJsonPath('en.auth.throttle', 'Too many login attempts. Please try again in {{seconds}} seconds.')->assertJsonPath('en.auth.forgot_password.label', 'Forgot Password?');
});

test('serves namespaced extension translations and rejects path traversal', function (): void {
    $directory = sys_get_temp_dir().'/ptero-locale-'.uniqid();
    \Illuminate\Support\Facades\File::ensureDirectoryExists($directory.'/en');
    \Illuminate\Support\Facades\File::put($directory.'/en/messages.php', '<?php return ["welcome" => "Welcome, :name"];');
    $this->app->make(\Illuminate\Translation\Translator::class)->addNamespace('ext-probe', $directory);
    try {
        $this->getJson('/locales/locale.json?locale=en&namespace=ext-probe%3A%3Amessages')
            ->assertOk()->assertJsonPath('en.ext-probe::messages.welcome', 'Welcome, {{name}}');
        $this->getJson('/locales/locale.json?locale=en&namespace=ext-probe%3A%3A..%2Fprivate')->assertUnprocessable();
    } finally {
        \Illuminate\Support\Facades\File::deleteDirectory($directory);
    }
});

test('caches translations whose URL changes with them and makes the browser revalidate the rest', function (): void {
    $directory = sys_get_temp_dir().'/ptero-locale-'.uniqid();
    \Illuminate\Support\Facades\File::ensureDirectoryExists($directory.'/en');
    \Illuminate\Support\Facades\File::put($directory.'/en/messages.php', '<?php return ["welcome" => "Welcome"];');
    $this->app->make(\Illuminate\Translation\Translator::class)->addNamespace('ext-probe', $directory);
    try {
        $core = $this->getJson('/locales/locale.json?locale=en&namespace=auth')->assertOk();
        expect($core->headers->get('Cache-Control'))->toContain('max-age=3600')->toContain('public');

        // The frontend sends the revision of the extension's translations, so upgrading
        // the extension changes the URL.
        $revision = str_repeat('a1', 16);
        $extension = $this->getJson("/locales/locale.json?locale=en&namespace=ext-probe%3A%3Amessages&revision={$revision}")->assertOk();
        expect($extension->headers->get('Cache-Control'))->toContain('max-age=3600')->toContain('public');

        // Without it an upgrade leaves the URL as it was, so a cached copy must never be
        // used without asking the server first.
        $bare = $this->getJson('/locales/locale.json?locale=en&namespace=ext-probe%3A%3Amessages')->assertOk();
        expect($bare->headers->get('Cache-Control'))->toContain('no-cache')->not->toContain('max-age');

        $this->getJson('/locales/locale.json?locale=en&namespace=ext-probe%3A%3Amessages&revision=..%2F')->assertUnprocessable();
    } finally {
        \Illuminate\Support\Facades\File::deleteDirectory($directory);
    }
});

test('answers an unchanged translation set with 304 and a changed one with the new strings', function (): void {
    $directory = sys_get_temp_dir().'/ptero-locale-'.uniqid();
    $url = '/locales/locale.json?locale=en&namespace=ext-probe%3A%3Amessages';
    \Illuminate\Support\Facades\File::ensureDirectoryExists($directory.'/en');
    \Illuminate\Support\Facades\File::put($directory.'/en/messages.php', '<?php return ["welcome" => "Welcome"];');
    $this->app->make(\Illuminate\Translation\Translator::class)->addNamespace('ext-probe', $directory);
    try {
        $etag = $this->getJson($url)->assertOk()->headers->get('ETag');
        expect($etag)->toBeString()->toStartWith('"');

        $this->getJson($url, ['If-None-Match' => $etag])->assertStatus(304);

        // What an extension upgrade does: the same URL now holds different strings.
        \Illuminate\Support\Facades\File::put($directory.'/en/messages.php', '<?php return ["welcome" => "Welcome", "summary" => "Summary"];');
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($directory.'/en/messages.php', true);
        }
        $this->refreshApplication();
        $this->app->make(\Illuminate\Translation\Translator::class)->addNamespace('ext-probe', $directory);

        $this->getJson($url, ['If-None-Match' => $etag])
            ->assertOk()
            ->assertJsonPath('en.ext-probe::messages.summary', 'Summary');
    } finally {
        \Illuminate\Support\Facades\File::deleteDirectory($directory);
    }
});
