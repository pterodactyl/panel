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
