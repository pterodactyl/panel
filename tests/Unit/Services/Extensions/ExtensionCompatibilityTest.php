<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Extensions\ExtensionCompatibilityTest;

use Illuminate\Support\Facades\File;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Services\Extensions\ExtensionCompatibility;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config(['extensions.panel_version' => '2.0.0', 'extensions.sdk_version' => '2.0.0-beta.1']);
});

/** @param array{panel?: string, sdk?: string, php?: string, extensions?: array<string, string>} $requires */
function manifest(string $id, array $requires = [], string $version = '1.0.0'): ExtensionManifest
{
    return ExtensionManifest::fromValidatedData('', ['id' => $id, 'name' => $id, 'version' => $version, 'requires' => $requires], [], null, 'native', null);
}

test('orders enabled extensions after their dependencies', function (): void {
    $child = manifest('child', ['panel' => '^2.0', 'sdk' => '>=2.0.0-beta.1 <3.0', 'extensions' => ['base' => '^1.2']]);
    $base = manifest('base', version: '1.3.0');
    $enabled = collect(['child' => $child, 'base' => $base]);
    $result = resolve(ExtensionCompatibility::class)->resolve($enabled);

    expect($result['manifests']->keys()->all())->toBe(['base', 'child']);
    expect($result['errors'])->toBe([]);
    expect($enabled->keys()->all())->toBe(['child', 'base']);
});

test('isolates invalid dependencies while preserving independent extensions', function (): void {
    $enabled = collect([
        'healthy' => manifest('healthy'),
        'future' => manifest('future', ['panel' => '^3.0']),
        'dependent' => manifest('dependent', ['extensions' => ['future' => '*']]),
        'missing' => manifest('missing', ['extensions' => ['absent' => '*']]),
        'wrong-sdk' => manifest('wrong-sdk', ['sdk' => '^3.0']),
        'wrong-php' => manifest('wrong-php', ['php' => '>=99.0']),
        'wrong-version' => manifest('wrong-version', ['extensions' => ['healthy' => '^2.0']]),
        'cycle-a' => manifest('cycle-a', ['extensions' => ['cycle-b' => '*']]),
        'cycle-b' => manifest('cycle-b', ['extensions' => ['cycle-a' => '*']]),
    ]);
    $result = resolve(ExtensionCompatibility::class)->resolve($enabled);

    expect($result['manifests']->keys()->all())->toBe(['healthy']);
    expect($result['errors'])->toHaveKeys(['future', 'dependent', 'missing', 'wrong-sdk', 'wrong-php', 'wrong-version', 'cycle-a', 'cycle-b']);
    expect($result['errors']['dependent'])->toContain('unavailable extension "future"');
    expect($result['errors']['cycle-a'])->toContain('circular');
});

test('rejects upgrades and disabling that would break an enabled dependent', function (): void {
    $base = manifest('base');
    $child = manifest('child', ['extensions' => ['base' => '^1.0']]);
    $enabled = collect(['base' => $base, 'child' => $child]);
    $compatibility = resolve(ExtensionCompatibility::class);

    expect(fn () => $compatibility->assertCompatible(manifest('base', version: '2.0.0'), $enabled))->toThrow(InvalidExtensionException::class, 'child');
    expect($enabled->get('base'))->toBe($base);
    expect(fn () => $compatibility->assertCanDisable('base', $enabled))->toThrow(InvalidExtensionException::class, 'child');
    $compatibility->assertCanDisable('child', $enabled);
});

/** @param list<string> $components */
function presentation(string $id, array $components): ExtensionManifest
{
    return ExtensionManifest::fromValidatedData('', ['id' => $id, 'name' => $id, 'version' => '1.0.0', 'ui' => ['entry' => 'dist/client.js', 'components' => $components]], [], 'dist/client.js', 'native', null);
}

test('rejects conflicting component ownership while preserving unrelated extensions', function (): void {
    $enabled = collect([
        'first' => presentation('first', ['dashboard.serverCard']),
        'second' => presentation('second', ['dashboard.serverCard']),
        'healthy' => presentation('healthy', ['server.files.details']),
    ]);
    $compatibility = resolve(ExtensionCompatibility::class);
    $result = $compatibility->resolve($enabled);

    expect($result['manifests']->keys()->all())->toBe(['healthy']);
    expect($result['errors']['first'])->toContain('dashboard.serverCard', 'second');
    expect($result['errors']['second'])->toContain('dashboard.serverCard', 'first');
    expect(fn () => $compatibility->assertCompatible(presentation('third', ['dashboard.serverCard']), $enabled))->toThrow(InvalidExtensionException::class, 'dashboard.serverCard');
});

test('allows an owner to update itself and ownership to transfer after disabling', function (): void {
    $compatibility = resolve(ExtensionCompatibility::class);
    $first = presentation('first', ['dashboard.serverCard']);
    $enabled = collect(['first' => $first]);

    $compatibility->assertCompatible(presentation('first', ['dashboard.serverCard']), $enabled);
    expect($enabled->get('first'))->toBe($first);
    $compatibility->assertCompatible(presentation('second', ['dashboard.serverCard']), collect());
});

/** @param list<string> $prefixes */
function rooted(string $id, array $prefixes): ExtensionManifest
{
    return ExtensionManifest::fromValidatedData('', ['id' => $id, 'name' => $id, 'version' => '1.0.0', 'routes' => ['root' => $prefixes]], [], null, 'native', null);
}

test('rejects root path prefixes another enabled extension already claims', function (): void {
    $enabled = collect([
        'first' => rooted('first', ['go', 'links']),
        'second' => rooted('second', ['go']),
        'healthy' => rooted('healthy', ['status']),
    ]);
    $compatibility = resolve(ExtensionCompatibility::class);
    $result = $compatibility->resolve($enabled);

    expect($result['manifests']->keys()->all())->toBe(['healthy']);
    expect($result['errors']['first'])->toContain('root path "/go"', 'second');
    expect($result['errors']['second'])->toContain('root path "/go"', 'first');
    expect(fn () => $compatibility->assertCompatible(rooted('third', ['status']), collect(['healthy' => $enabled['healthy']])))->toThrow(InvalidExtensionException::class, 'root path "/status"');
    $compatibility->assertCompatible(rooted('healthy', ['status', 'uptime']), collect(['healthy' => $enabled['healthy']]));
    $compatibility->assertCompatible(rooted('third', ['status']), collect());
});

/** @param array<string, string> $autoload */
function autoloaded(string $id, array $autoload): ExtensionManifest
{
    return ExtensionManifest::fromValidatedData('', ['id' => $id, 'name' => $id, 'version' => '1.0.0'], $autoload, null, 'native', null);
}

test('rejects autoload namespaces that overlap another enabled extension in either direction', function (): void {
    $enabled = collect([
        'billing' => autoloaded('billing', ['Acme\\Billing\\' => 'src']),
        'invoices' => autoloaded('invoices', ['Acme\\Billing\\Invoices\\' => 'src']),
        'healthy' => autoloaded('healthy', ['Acme\\Status\\' => 'src']),
        'plain' => autoloaded('plain', []),
    ]);
    $compatibility = resolve(ExtensionCompatibility::class);
    $result = $compatibility->resolve($enabled);

    expect($result['manifests']->keys()->all())->toBe(['healthy', 'plain']);
    expect($result['errors']['billing'])->toBe('Extension "billing" cannot autoload "Acme\\Billing\\": enabled extension "invoices" autoloads "Acme\\Billing\\Invoices\\".');
    expect($result['errors']['invoices'])->toBe('Extension "invoices" cannot autoload "Acme\\Billing\\Invoices\\": enabled extension "billing" autoloads "Acme\\Billing\\".');

    $healthy = collect(['healthy' => $enabled['healthy']]);
    expect(fn () => $compatibility->assertCompatible(autoloaded('takeover', ['acme\\status\\' => 'src']), $healthy))->toThrow(InvalidExtensionException::class, 'enabled extension "healthy" autoloads "Acme\\Status\\"');
    expect(fn () => $compatibility->assertCompatible(autoloaded('parent', ['Acme\\' => 'src']), $healthy))->toThrow(InvalidExtensionException::class, 'cannot autoload "Acme\\"');
    // An upgrade keeps its own namespaces, a sibling namespace is free, and so is one whose owner is disabled.
    $compatibility->assertCompatible(autoloaded('healthy', ['Acme\\Status\\' => 'src', 'Acme\\Status\\Extra\\' => 'extra']), $healthy);
    $compatibility->assertCompatible(autoloaded('sibling', ['Acme\\StatusPage\\' => 'src']), $healthy);
    $compatibility->assertCompatible(autoloaded('takeover', ['Acme\\Status\\' => 'src']), collect());
});

test('rejects a bundled vendor autoloader whose Composer suffix the panel or an enabled extension already uses', function (): void {
    $directory = sys_get_temp_dir().'/ptero-suffix-'.uniqid();
    preg_match('/ComposerAutoloaderInit(\w+)::/', File::get(base_path('vendor/autoload.php')), $panel);
    $bundle = function (string $id, string $suffix) use ($directory): ExtensionManifest {
        File::ensureDirectoryExists($directory.'/'.$id.'/vendor');
        File::put($directory.'/'.$id.'/vendor/autoload.php', '<?php require_once __DIR__."/composer/autoload_real.php"; return ComposerAutoloaderInit'.$suffix.'::getLoader();');

        return ExtensionManifest::fromValidatedData($directory.'/'.$id, ['id' => $id, 'name' => $id, 'version' => '1.0.0'], [], null, 'native', null);
    };

    try {
        $compatibility = resolve(ExtensionCompatibility::class);
        $first = $bundle('first', 'Shared');
        $enabled = collect(['first' => $first]);

        expect(fn () => $compatibility->assertCompatible($bundle('second', 'Shared'), $enabled))->toThrow(InvalidExtensionException::class, 'Extension "second" cannot load its vendor/autoload.php: enabled extension "first" uses the same Composer autoloader (ComposerAutoloaderInitShared).');
        expect(fn () => $compatibility->assertCompatible($bundle('copy', $panel[1]), collect()))->toThrow(InvalidExtensionException::class, 'the panel uses the same Composer autoloader');
        // An upgrade keeps its own suffix, and a distinct suffix loads beside it.
        $compatibility->assertCompatible($first, $enabled);
        $compatibility->assertCompatible($bundle('third', 'Distinct'), $enabled);
    } finally {
        File::deleteDirectory($directory);
    }
});

test('rejects a migration named like one the panel or another installed extension has', function (): void {
    $directory = sys_get_temp_dir().'/ptero-migrations-'.uniqid();
    $core = basename((string) (glob(database_path('migrations').'/*_*.php') ?: [''])[0], '.php');
    $package = function (string $id, string ...$migrations) use ($directory): ExtensionManifest {
        File::ensureDirectoryExists($directory.'/'.$id.'/database/migrations');
        foreach ($migrations as $migration) {
            File::put($directory.'/'.$id.'/database/migrations/'.$migration.'.php', '<?php');
        }

        return ExtensionManifest::fromValidatedData($directory.'/'.$id, ['id' => $id, 'name' => $id, 'version' => '1.0.0'], [], null, 'native', null);
    };

    try {
        $compatibility = resolve(ExtensionCompatibility::class);
        $ledger = $package('ledger', '2026_01_01_000000_create_ledger_entries_table');
        $installed = collect(['ledger' => $ledger]);

        expect(fn () => $compatibility->assertMigrationsAreUnique($package('copy', $core), $installed))->toThrow(InvalidExtensionException::class, "Extension \"copy\" cannot run migration \"{$core}\": the panel has a migration with the same name");
        expect(fn () => $compatibility->assertMigrationsAreUnique($package('rival', '2026_01_01_000000_create_ledger_entries_table'), $installed))->toThrow(InvalidExtensionException::class, 'extension "ledger" has a migration with the same name');
        // An upgrade keeps its own migrations, and distinct names never clash.
        $compatibility->assertMigrationsAreUnique($ledger, $installed);
        $compatibility->assertMigrationsAreUnique($package('audit', '2026_01_01_000000_create_audit_ledger_entries_table'), $installed);
    } finally {
        File::deleteDirectory($directory);
    }
});

function styled(string $id, ?string $prefix): ExtensionManifest
{
    return ExtensionManifest::fromValidatedData('', ['id' => $id, 'name' => $id, 'version' => '1.0.0', 'ui' => array_filter(['entry' => 'dist/client.js', 'prefix' => $prefix])], [], 'dist/client.js', 'native', null);
}

test('rejects a tailwind prefix another enabled extension already uses', function (): void {
    $enabled = collect([
        'first' => styled('first', 'shared'),
        'second' => styled('second', 'shared'),
        'healthy' => styled('healthy', 'hl'),
        'plain' => styled('plain', null),
        'bare' => styled('bare', null),
    ]);
    $compatibility = resolve(ExtensionCompatibility::class);
    $result = $compatibility->resolve($enabled);

    expect($result['manifests']->keys()->all())->toBe(['healthy', 'plain', 'bare']);
    expect($result['errors']['first'])->toBe('Extension "first" cannot use Tailwind prefix "shared": enabled extension "second" also declares it.');
    expect($result['errors']['second'])->toBe('Extension "second" cannot use Tailwind prefix "shared": enabled extension "first" also declares it.');
    expect(fn () => $compatibility->assertCompatible(styled('third', 'hl'), collect(['healthy' => $enabled['healthy']])))->toThrow(InvalidExtensionException::class, 'Extension "third" cannot use Tailwind prefix "hl": enabled extension "healthy" also declares it.');
    // An upgrade keeps its own prefix, and a prefix is free again once its owner is disabled.
    $compatibility->assertCompatible(styled('healthy', 'hl'), collect(['healthy' => $enabled['healthy']]));
    $compatibility->assertCompatible(styled('third', 'hl'), collect());
    $compatibility->assertCompatible(styled('third', null), collect(['plain' => $enabled['plain']]));
});
