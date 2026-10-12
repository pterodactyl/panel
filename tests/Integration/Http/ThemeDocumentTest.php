<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Http\ThemeDocumentTest;

use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Pterodactyl\Models\Extension;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Themes\ThemeRepository;
use Pterodactyl\Services\Themes\ThemeResolver;
use Pterodactyl\Tests\Integration\Http\HttpTestCase;

use function pterodactylTestCase;

uses(HttpTestCase::class);

const CORE = 'f1x7ure0c0re0000';

beforeEach(function (): void {
    $this->baseDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-theme-document-'.uniqid();
    config([
        'extensions.enabled' => true,
        'extensions.directory' => $this->baseDirectory.'/extensions',
        'extensions.assets_directory' => $this->baseDirectory.'/assets',
    ]);
});

afterEach(function (): void {
    app(ExtensionRepository::class)->flushDiscovery();
    File::deleteDirectory($this->baseDirectory);
});

/**
 * @param  array<string, mixed>  $theme
 * @param  array<string, mixed>  $build
 */
function installTheme(string $id, array $theme = [], array $build = ['core' => CORE, 'sdk' => '2.0.0-beta.4']): void
{
    (function () use ($id, $theme, $build): void {
        $directory = $this->baseDirectory.'/extensions/'.$id;
        File::ensureDirectoryExists($directory.'/dist');
        File::put($directory.'/extension.json', json_encode([
            'id' => $id,
            'type' => 'theme',
            'name' => ucfirst($id),
            'version' => '1.0.0',
            'ui' => ['entry' => 'dist/client.js'],
            'theme' => ['stylesheet' => 'dist/theme.css', ...$theme],
        ], JSON_THROW_ON_ERROR));
        File::put($directory.'/dist/client.js', 'export default {};');
        File::put($directory.'/dist/theme.css', ':root{}');
        File::put($directory.'/dist/theme.json', json_encode($build, JSON_THROW_ON_ERROR));
        Extension::query()->create(['identifier' => $id, 'version' => '1.0.0', 'enabled' => true]);
        $this->app->make(ExtensionRepository::class)->flushDiscovery();
    })->call(pterodactylTestCase());
}

function aurora(): void
{
    installTheme('aurora', [
        'schemes' => ['dark', 'light'],
        'settings' => [
            'accent' => ['type' => 'color', 'label' => 'Accent', 'default' => '#8b5cf6', 'token' => '--accent'],
            'radius' => ['type' => 'number', 'label' => 'Radius', 'default' => 0.75, 'unit' => 'rem', 'token' => '--radius'],
            'navigation' => ['type' => 'select', 'label' => 'Navigation', 'options' => ['side' => 'Sidebar', 'top' => 'Top bar'], 'default' => 'side', 'attribute' => 'data-aurora-nav'],
            'compact' => ['type' => 'toggle', 'label' => 'Compact', 'default' => false, 'attribute' => 'data-density'],
            'tagline' => ['type' => 'text', 'label' => 'Tagline', 'token' => '--tagline'],
        ],
    ]);
    app(ThemeRepository::class)->setActive('aurora');
}

/** @return array<string, mixed> */
function themePayload(TestResponse $response): array
{
    preg_match('/window\.SiteConfiguration = (.+?);\s*<\/script>/s', (string) $response->getContent(), $matches);

    return json_decode($matches[1] ?? 'null', true, flags: JSON_THROW_ON_ERROR)['theme'];
}

test('guests get the classic theme with the panel stylesheet and a zoomable viewport', function (): void {
    $response = $this->get('/auth/login')->assertOk()
        ->assertSee('<html lang="en" class="dark" data-theme="classic" >', false)
        ->assertSee('<style id="theme-settings"></style>', false)
        ->assertSee('href="/assets/stylesheet.test.css"', false)
        ->assertSee('<meta content="width=device-width, initial-scale=1" name="viewport">', false)
        ->assertDontSee('user-scalable', false)
        ->assertDontSee('modulepreload', false);

    expect(themePayload($response))->toBe([
        'id' => 'classic',
        'chain' => [],
        'settings' => [],
        'scheme' => 'dark',
        'schemes' => ['dark'],
        'safeMode' => false,
        'preview' => false,
        'stylesheetFallback' => false,
        'inspect' => false,
    ]);

    // The import map moved into <head>, ahead of the theme entries it serves.
    $html = (string) $response->getContent();
    expect(mb_strpos($html, '<script type="importmap">'))->toBeLessThan(mb_strpos($html, '</head>'));
});

test('the active theme renders its settings, stylesheet and entry before any script runs', function (): void {
    aurora();
    app(ExtensionRepository::class)->settings('aurora')->setMany(['theme.accent' => '#112233', 'theme.navigation' => 'top', 'theme.compact' => true]);

    $response = $this->actingAs(User::factory()->create())->get('/')->assertOk()
        ->assertSee('class="dark" data-theme="aurora" data-aurora-nav="top" data-density="true" >', false)
        ->assertSee('<style id="theme-settings">:root:root{--accent:#112233;--radius:0.75rem;}</style>', false)
        ->assertSee('<link rel="stylesheet" href="/assets/extensions/aurora/theme.css?v=100">', false)
        ->assertSee('<link rel="modulepreload" href="/assets/extensions/aurora/client.js?v=100">', false)
        // Built against this panel's classes, the theme stylesheet replaces the panel's.
        ->assertDontSee('stylesheet.test.css', false);

    expect(themePayload($response))->toMatchArray([
        'id' => 'aurora',
        'chain' => [['id' => 'aurora', 'version' => '1.0.0', 'entry' => '/assets/extensions/aurora/client.js?v=100', 'development' => null]],
        'settings' => ['accent' => '#112233', 'radius' => 0.75, 'navigation' => 'top', 'compact' => true, 'tagline' => null],
        'stylesheetFallback' => false,
    ]);
});

test('a theme stylesheet built against other core classes is laid over the panel stylesheet', function (array $build): void {
    installTheme('aurora', build: $build);
    app(ThemeRepository::class)->setActive('aurora');

    $html = (string) $this->get('/auth/login')->assertOk()->getContent();

    expect(mb_strpos($html, 'stylesheet.test.css'))->toBeLessThan(mb_strpos($html, '/assets/extensions/aurora/theme.css'));
    expect(themePayload($this->get('/auth/login'))['stylesheetFallback'])->toBeTrue();
})->with([
    'another hash' => [['core' => 'another0000hash0', 'sdk' => '2.0.0-beta.4']],
    'no build info' => [[]],
]);

test('setting values cannot leave their declaration or the style element', function (string $hostile): void {
    aurora();
    app(ExtensionRepository::class)->settings('aurora')->setMany(['theme.tagline' => $hostile, 'theme.accent' => 'red;}body{display:none', 'theme.navigation' => '"><script>']);

    $html = (string) $this->get('/auth/login')->assertOk()->getContent();
    preg_match('/<style id="theme-settings">(.*?)<\/style>/s', $html, $style);

    expect($style[1])->toStartWith(':root:root{--accent:#8b5cf6;--radius:0.75rem;--tagline:"')
        ->and($style[1])->not->toContain('<')->not->toContain(';}body')
        ->and(mb_substr_count($style[1], '}'))->toBe(1);
    // An option that no longer exists reads as the default.
    expect($html)->toContain('data-aurora-nav="side"')->not->toContain('"><script>');
})->with([
    'closing tag' => ['</style><script>alert(1)</script>'],
    'quotes and escapes' => ["\"\\'; } :root { --x: 1"],
    'new lines' => ["one\ntwo\r\nthree"],
]);

test('safe mode renders classic without extension bundles for the rest of the session', function (): void {
    aurora();

    $safe = $this->get('/auth/login?safe_mode=1')->assertOk()->assertSee('data-theme="classic"', false);
    expect(themePayload($safe))->toMatchArray(['id' => 'classic', 'safeMode' => true, 'chain' => []]);
    expect(session(ThemeResolver::SAFE_MODE))->toBeTrue();

    $this->get('/auth/login')->assertSee('data-theme="classic"', false)->assertSee('"extensions":[]', false);
    $this->get('/auth/login?safe_mode=0')->assertSee('data-theme="aurora"', false);
    expect(session()->has(ThemeResolver::SAFE_MODE))->toBeFalse();
});

test('a root admin previewing a theme sees it alone', function (): void {
    installTheme('aurora');
    installTheme('nebula');
    app(ThemeRepository::class)->setActive('aurora');
    $admin = User::factory()->create(['root_admin' => true]);

    $previewing = $this->actingAs($admin)->withSession([ThemeResolver::PREVIEW => 'nebula'])->get('/')->assertOk();
    expect(themePayload($previewing))->toMatchArray(['id' => 'nebula', 'preview' => true]);

    $this->actingAs(User::factory()->create())->withSession([ThemeResolver::PREVIEW => 'nebula'])->get('/')
        ->assertSee('data-theme="aurora"', false);
});

test('the inspector is on in debug mode, or for a root admin who asks for it', function (): void {
    config(['app.debug' => false]);
    $admin = User::factory()->create(['root_admin' => true]);

    expect(themePayload($this->actingAs($admin)->get('/'))['inspect'])->toBeFalse();
    expect(themePayload($this->actingAs($admin)->get('/?inspect=1'))['inspect'])->toBeTrue();
    expect(themePayload($this->actingAs(User::factory()->create())->get('/?inspect=1'))['inspect'])->toBeFalse();

    config(['app.debug' => true]);
    expect(themePayload($this->get('/'))['inspect'])->toBeTrue();
});

test('the scheme follows the preference cookie when the theme supports it', function (?string $cookie, array $schemes, string $scheme, bool $script): void {
    installTheme('aurora', ['schemes' => $schemes]);
    app(ThemeRepository::class)->setActive('aurora');

    $request = $cookie === null ? $this : $this->withUnencryptedCookie(ThemeResolver::SCHEME_COOKIE, $cookie);
    $response = $request->get('/auth/login')->assertOk()->assertSee("class=\"{$scheme}\"", false);

    expect(themePayload($response)['scheme'])->toBe($scheme);
    expect(str_contains((string) $response->getContent(), "matchMedia('(prefers-color-scheme: light)')"))->toBe($script);
})->with([
    'no preference' => [null, ['dark', 'light'], 'dark', false],
    'light' => ['light', ['dark', 'light'], 'light', false],
    'system' => ['system', ['dark', 'light'], 'dark', true],
    'unsupported scheme' => ['light', ['dark'], 'dark', false],
    'unknown value' => ['sepia', ['light', 'dark'], 'light', false],
    'system with one scheme' => ['system', ['light'], 'light', false],
]);

test('an active theme that cannot be used renders as classic', function (): void {
    aurora();
    Extension::query()->where('identifier', 'aurora')->update(['enabled' => false]);
    app(ExtensionRepository::class)->flushDiscovery();

    $this->get('/auth/login')->assertOk()->assertSee('data-theme="classic"', false)->assertSee('stylesheet.test.css', false);
});
