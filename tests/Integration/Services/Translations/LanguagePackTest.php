<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Translations\LanguagePackTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Translation\Translator;
use Pterodactyl\Models\Extension;
use Pterodactyl\Models\User;
use Pterodactyl\Notifications\MailTested;
use Pterodactyl\Services\Extensions\ExtensionProviderLoader;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Translations\LanguageRegistry;
use Pterodactyl\Services\Translations\TranslationCoverage;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

use function pterodactylTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    $this->extensionDirectory = sys_get_temp_dir().'/ptero-languages-'.uniqid();
    File::ensureDirectoryExists($this->extensionDirectory);
    config([
        'extensions.enabled' => true,
        'extensions.directory' => $this->extensionDirectory,
        'extensions.assets_directory' => $this->extensionDirectory.'/assets',
    ]);
    $this->app->forgetInstance(ExtensionRepository::class);
    $this->app->forgetInstance(ExtensionProviderLoader::class);
    $this->app->forgetInstance(LanguageRegistry::class);

    expect(app(LanguageRegistry::class)->available())->toBe(['en' => 'English'])
        ->and(app(LanguageRegistry::class)->packs()->all())->toBe([]);
    app(ExtensionRepository::class)->flushDiscovery();
});

afterEach(function (): void {
    File::deleteDirectory($this->extensionDirectory);
});

test('a pack without a provider adds a locale for core translations that falls back to english', function (): void {
    writePack('german', ['de'], ['de/auth.php' => ['sign_in' => 'Anmelden']]);
    boot();

    expect(trans('auth.sign_in', [], 'de'))->toBe('Anmelden')
        ->and(trans('auth.go_to_login', [], 'de'))->toBe('Go to Login')
        ->and(app(LanguageRegistry::class)->available())->toBe(['de' => 'German', 'en' => 'English'])
        ->and(app(LanguageRegistry::class)->sources())->toBe(['de' => ['german'], 'en' => ['core']]);
});

test('a pack can override english keys', function (): void {
    writePack('wording', ['en'], ['en/auth.php' => ['sign_in' => 'Log In']]);
    boot();

    expect(trans('auth.sign_in', [], 'en'))->toBe('Log In')
        ->and(trans('auth.go_to_login', [], 'en'))->toBe('Go to Login')
        ->and(app(LanguageRegistry::class)->sources()['en'])->toBe(['core', 'wording']);
});

test('a pack translates another extension that has no provider', function (): void {
    writeExtension('notes', ['en/messages.php' => ['welcome' => 'Welcome, :name', 'bye' => 'Goodbye']]);
    writePack('german', ['de'], ['vendor/ext-notes/de/messages.php' => ['welcome' => 'Willkommen, :name']]);
    boot();

    expect(trans('ext-notes::messages.welcome', ['name' => 'Alex'], 'de'))->toBe('Willkommen, Alex')
        ->and(trans('ext-notes::messages.bye', [], 'de'))->toBe('Goodbye')
        ->and(trans('ext-notes::messages.welcome', ['name' => 'Alex'], 'en'))->toBe('Welcome, Alex');
});

test('overriding another extension changes the revision of its translations', function (): void {
    writeExtension('notes', ['en/messages.php' => ['welcome' => 'Welcome']], ui: true);
    $repository = app(ExtensionRepository::class);
    $before = $repository->frontendPayload(false)[0]['translations'];

    writePack('german', ['de'], ['vendor/ext-notes/de/messages.php' => ['welcome' => 'Willkommen']]);
    $repository->flushDiscovery();

    expect($before)->toMatch('/^[a-f0-9]{32}$/')
        ->and($repository->frontendPayload(false)[0]['translations'])->toMatch('/^[a-f0-9]{32}$/')->not->toBe($before);
});

test('packs for the same locale merge key by key and report unrelated conflicts', function (): void {
    writePack('german-community', ['de'], ['de/auth.php' => ['sign_in' => 'Einloggen', 'go_to_login' => 'Zur Anmeldung']]);
    writePack('german-pro', ['de'], ['de/auth.php' => ['sign_in' => 'Anmelden', 'failed' => 'Falsche Zugangsdaten.']]);
    boot();

    expect(trans('auth.sign_in', [], 'de'))->toBe('Anmelden')
        ->and(trans('auth.go_to_login', [], 'de'))->toBe('Zur Anmeldung')
        ->and(trans('auth.failed', [], 'de'))->toBe('Falsche Zugangsdaten.')
        ->and(app(LanguageRegistry::class)->conflicts('de'))->toBe([
            ['locale' => 'de', 'key' => 'auth.sign_in', 'providers' => ['german-community', 'german-pro'], 'winner' => 'german-pro'],
        ])
        ->and(app(LanguageRegistry::class)->conflictSummary('german-community', app(LanguageRegistry::class)->conflicts()))->toBe([
            ['locale' => 'de', 'extension' => 'german-pro', 'winner' => 'german-pro', 'keys' => 1],
        ]);
});

test('a pack that requires another loads after it and is not reported as a conflict', function (): void {
    writePack('a-fixes', ['de'], ['de/auth.php' => ['sign_in' => 'Anmelden']], ['z-base' => '*']);
    writePack('z-base', ['de'], ['de/auth.php' => ['sign_in' => 'Einloggen']]);
    boot();

    expect(trans('auth.sign_in', [], 'de'))->toBe('Anmelden')
        ->and(app(LanguageRegistry::class)->conflicts('de'))->toBe([]);
});

test('the site revision follows the enabled packs', function (): void {
    $registry = app(LanguageRegistry::class);
    expect($registry->revision())->toBeNull();

    writePack('german', ['de'], ['de/auth.php' => ['sign_in' => 'Anmelden']]);
    app(ExtensionRepository::class)->flushDiscovery();
    $installed = $registry->revision();
    expect($installed)->toMatch('/^[a-f0-9]{32}$/');

    File::put($this->extensionDirectory.'/german/resources/languages/de/auth.php', '<?php return ["sign_in" => "Einloggen", "failed" => "Nein."];');
    clearstatcache();
    expect($registry->revision())->not->toBe($installed);

    Extension::query()->where('identifier', 'german')->update(['enabled' => false]);
    app(ExtensionRepository::class)->flushDiscovery();
    expect($registry->revision())->toBeNull()
        ->and($registry->available())->toBe(['en' => 'English']);
});

test('a user follows the panel default until they choose a language that is available', function (): void {
    writePack('german', ['de'], ['de/auth.php' => ['sign_in' => 'Anmelden']]);
    $registry = app(LanguageRegistry::class);

    expect($registry->resolveFor(User::factory()->make(['language' => null])))->toBe('en');

    config(['app.locale' => 'de']);
    expect($registry->resolveFor(User::factory()->make(['language' => null])))->toBe('de')
        ->and($registry->resolveFor(User::factory()->make(['language' => 'en'])))->toBe('en')
        ->and($registry->resolveFor(User::factory()->make(['language' => 'fr'])))->toBe('de')
        ->and($registry->resolveFor(null))->toBe('de');

    config(['app.locale' => 'fr']);
    expect($registry->resolveFor(null))->toBe('en');
});

test('coverage reports missing, stale and invalid keys and placeholder mistakes of a pack', function (): void {
    writePack('german', ['de'], ['de/auth.php' => [
        'sign_in' => 'Anmelden',
        'throttle' => 'Zu viele Versuche, warte :minutes Minuten.',
        'password_requirements' => 'Mindestens 8 Zeichen.',
        'removed' => 'Veraltet',
        'failed' => 5,
        'two_factor' => ['label' => ['nested' => 'Falsch']],
    ]]);
    boot();

    $report = app(TranslationCoverage::class)->report('de', app(LanguageRegistry::class)->packs()->get('german'));

    expect($report['missing'])->toContain('auth.go_to_login')->not->toContain('auth.sign_in')
        ->and($report['invalid'])->toBe(['auth.failed'])
        ->and($report['missing'])->toContain('auth.failed')
        ->and($report['stale'])->toContain('auth.removed', 'auth.two_factor.label.nested')
        ->and($report['unknown_placeholders'])->toBe(['auth.throttle' => ['minutes']])
        ->and($report['dropped_placeholders'])->toBe(['auth.throttle' => ['seconds']])
        ->and($report['translated'])->toBe(3)
        ->and($report['total'])->toBe(count($report['missing']) + 3);
});

test('coverage treats plural forms of an english plural as translations', function (): void {
    writeExtension('notes', ['en/messages.php' => ['files_one' => ':count file', 'files_other' => ':count files']]);
    writePack('polish', ['pl'], ['vendor/ext-notes/pl/messages.php' => ['files_one' => ':count plik', 'files_few' => ':count pliki', 'files_many' => ':count plików', 'files_other' => ':count pliku']]);
    boot();

    $report = app(TranslationCoverage::class)->report('pl', app(LanguageRegistry::class)->packs()->get('polish'));

    expect($report['total'])->toBe(2)
        ->and($report['translated'])->toBe(2)
        ->and($report['stale'])->toBe([])
        ->and($report['unknown_placeholders'])->toBe([]);
});

test('the coverage command fails while keys are missing', function (): void {
    writePack('german', ['de'], ['de/auth.php' => ['sign_in' => 'Anmelden']]);
    boot();

    expect(Artisan::call('p:lang:coverage', ['locale' => 'de', '--extension' => 'german', '--json' => true]))->toBe(1);
    $report = json_decode(Artisan::output(), true);
    expect($report['extension'])->toBe('german')
        ->and($report['missing'])->toContain('auth.failed');

    expect(Artisan::call('p:lang:coverage', ['locale' => 'fr']))->toBe(1)
        ->and(Artisan::call('p:lang:coverage', ['locale' => 'en_gb']))->toBe(2);
});

test('the coverage command prints interface keys exactly', function (): void {
    writeExtension('notes', ['en.json' => ['Delete <bold>{{name}}</bold>?' => 'Delete <bold>{{name}}</bold>?', 'Note saved.' => 'Note saved.']]);
    writePack('german', ['de'], ['de.json' => ['Delete <bold>{{name}}</bold>?' => '<bold>{{note}}</bold> löschen?']]);
    boot();

    Artisan::call('p:lang:coverage', ['locale' => 'de', '--extension' => 'german']);

    expect(Artisan::output())->toContain('json:Note saved.')
        ->toContain('json:Delete <bold>{{name}}</bold>?: {{note}}');
});

test('the interface catalogue merges extensions under the packs that translate them', function (): void {
    writeExtension('notes', ['en.json' => ['Add note' => 'Add note', 'Notes' => 'Notes'], 'de.json' => ['Add note' => 'Notiz hinzufügen', 'Notes' => 'Notizen']]);
    writePack('german', ['de'], ['de.json' => ['Login to Continue' => 'Anmelden, um fortzufahren', 'Notes' => 'Vermerke', 'Welcome back, {{name}}' => 'Willkommen zurück, {{name}}']]);
    boot();

    $this->getJson('/locales/locale.json?locale=de+en&namespace=translation')
        ->assertOk()
        ->assertJsonPath('de.translation.Login to Continue', 'Anmelden, um fortzufahren')
        ->assertJsonPath('de.translation.Add note', 'Notiz hinzufügen')
        ->assertJsonPath('de.translation.Notes', 'Vermerke')
        ->assertJsonPath('de.translation.Welcome back, {{name}}', 'Willkommen zurück, {{name}}')
        ->assertJsonPath('en.translation.Login to Continue', 'Login to Continue')
        ->assertJsonPath('en.translation.Add note', 'Add note');
});

test('the site revision follows the interface text of enabled extensions', function (): void {
    writeExtension('notes', ['en.json' => ['Notes' => 'Notes']]);
    $registry = app(LanguageRegistry::class);
    $installed = $registry->revision();
    expect($installed)->toMatch('/^[a-f0-9]{32}$/');

    File::put($this->extensionDirectory.'/notes/resources/lang/en.json', '{"Notes": "Notes", "Add note": "Add note"}');
    clearstatcache();
    expect($registry->revision())->not->toBe($installed);
});

test('coverage checks the interface catalogue against english', function (): void {
    writeExtension('notes', ['en.json' => ['Notes' => 'Notes', '{{count}} note_one' => '{{count}} note', '{{count}} note_other' => '{{count}} notes']]);
    writePack('german', ['de'], ['de.json' => [
        'Login to Continue' => 'Anmelden, um fortzufahren',
        'Notes' => 'Notizen {{user}}',
        '{{count}} note_one' => '{{count}} Notiz',
        '{{count}} note_other' => '{{count}} Notizen',
        'Removed text' => 'Entfernt',
    ]]);
    boot();

    $report = app(TranslationCoverage::class)->report('de', app(LanguageRegistry::class)->packs()->get('german'));

    expect($report['missing'])->toContain('json:Password')->not->toContain('json:Login to Continue', 'json:{{count}} note_one')
        ->and($report['stale'])->toBe(['json:Removed text'])
        ->and($report['unknown_placeholders'])->toBe(['json:Notes' => ['user']]);
});

test('api validation errors and notifications follow the language of the user', function (): void {
    writePack('german', ['de'], ['de/validation.php' => ['email' => ':attribute muss eine gültige E-Mail-Adresse sein.']]);
    boot();
    Notification::fake();
    $user = User::factory()->create(['language' => 'de']);

    $this->actingAs($user)->putJson('/api/client/account/email', ['email' => 'not-an-email', 'password' => 'password'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.detail', 'email muss eine gültige E-Mail-Adresse sein.');

    $user->notify(new MailTested($user));
    Notification::assertSentTo($user, MailTested::class, fn (MailTested $notification, array $channels, User $notifiable, ?string $locale): bool => $locale === 'de');
});

test('a user whose pack is disabled falls back to the panel default', function (): void {
    writePack('german', ['de'], ['de/auth.php' => ['sign_in' => 'Anmelden']]);
    boot();
    $user = User::factory()->create(['language' => 'de']);
    expect($user->preferredLocale())->toBe('de');

    Extension::query()->where('identifier', 'german')->update(['enabled' => false]);
    app(ExtensionRepository::class)->flushDiscovery();

    expect($user->fresh()->preferredLocale())->toBe('en')
        ->and($user->fresh()->language)->toBe('de');
});

function boot(): void
{
    $repository = app(ExtensionRepository::class);
    $repository->flushDiscovery();
    app(ExtensionProviderLoader::class)->registerProviders($repository->enabled());
}

/**
 * @param  list<string>  $languages
 * @param  array<string, array<mixed>>  $files
 * @param  array<string, string>  $dependencies
 */
function writePack(string $identifier, array $languages, array $files, array $dependencies = []): void
{
    $directory = pterodactylTestCase()->extensionDirectory.'/'.$identifier;
    writeFiles($directory.'/resources/languages', $files);
    File::put($directory.'/extension.json', json_encode(array_filter([
        'id' => $identifier,
        'name' => $identifier,
        'version' => '1.0.0',
        'languages' => $languages,
        'requires' => $dependencies === [] ? null : ['extensions' => $dependencies],
    ]), JSON_THROW_ON_ERROR));
    Extension::query()->create(['identifier' => $identifier, 'version' => '1.0.0', 'enabled' => true]);
}

/**
 * @param  array<string, array<mixed>>  $files
 */
function writeExtension(string $identifier, array $files, bool $ui = false): void
{
    $directory = pterodactylTestCase()->extensionDirectory.'/'.$identifier;
    writeFiles($directory.'/resources/lang', $files);
    File::put($directory.'/extension.json', json_encode(array_filter([
        'id' => $identifier,
        'name' => $identifier,
        'version' => '1.0.0',
        'ui' => $ui ? ['entry' => 'dist/client.js'] : null,
    ]), JSON_THROW_ON_ERROR));
    Extension::query()->create(['identifier' => $identifier, 'version' => '1.0.0', 'enabled' => true]);
}

/**
 * @param  array<string, array<mixed>>  $files
 */
function writeFiles(string $root, array $files): void
{
    foreach ($files as $path => $lines) {
        File::ensureDirectoryExists(dirname($root.'/'.$path));
        File::put($root.'/'.$path, str_ends_with($path, '.json') ? json_encode($lines, JSON_THROW_ON_ERROR) : '<?php return '.var_export($lines, true).';');
    }

    app(Translator::class)->setLoaded([]);
}
