<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Extensions\ExtensionScaffolderTest;

use Illuminate\Support\Facades\File;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionManifestValidator;
use Pterodactyl\Services\Extensions\Scaffolding\ExtensionScaffolder;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);
beforeEach(function (): void {
    $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-scaffolder-'.uniqid();
    $this->scaffolder = new ExtensionScaffolder(new ExtensionManifestValidator);
    File::ensureDirectoryExists($this->directory);
});
afterEach(function (): void {
    File::deleteDirectory($this->directory);
});
test('scaffolds an installable package', function (): void {
    $target = $this->scaffolder->scaffold(id: 'my-widget', description: 'A test widget.', author: 'Tester', outDir: $this->directory);
    $manifest = (new ExtensionManifestValidator)->fromDirectory($target);
    expect($manifest->id)->toBe('my-widget');
    expect($manifest->name)->toBe('My Widget');
    expect($manifest->description)->toBe('A test widget.');
    expect($manifest->author)->toBe('Tester');
    expect($manifest->provider)->toBe('MyWidget\MyWidgetProvider');
    expect($manifest->autoload)->toBe(['MyWidget\\' => 'src']);
    expect($manifest->hasUi())->toBeTrue();
    expect($manifest->screens)->toBe([['id' => 'main', 'area' => 'server', 'path' => 'my-widget', 'nav' => ['label' => 'My Widget']]]);

    $provider = (string) file_get_contents($target.'/src/MyWidgetProvider.php');
    $this->assertStringContainsString('namespace MyWidget;', $provider);
    $this->assertStringContainsString('class MyWidgetProvider extends ExtensionProvider', $provider);
    $this->assertStringContainsString('$this->registerApiRoutes();', $provider);
    $controller = (string) file_get_contents($target.'/src/Http/Controllers/StatusController.php');
    $this->assertStringContainsString('namespace MyWidget\Http\Controllers;', $controller);
    $this->assertStringContainsString("#[Endpoint('Get status'", $controller);
    $this->assertStringContainsString('use MyWidget\Http\Controllers\StatusController;', (string) file_get_contents($target.'/routes/client.php'));
    foreach (['src/client/index.tsx', 'src/client/screens/ServerScreen.tsx', 'vite.config.mjs', 'tsconfig.json', 'README.md', '.gitignore', 'vitest.config.mjs', 'src/client/screens/ServerScreen.spec.tsx', 'openapi-ts.config.ts', '.github/workflows/ci.yml', 'postcss.config.mjs', 'src/client/styles.css'] as $file) {
        expect($target.'/'.$file)->toBeFile();
    }

    // Utilities carry the extension's own prefix so they cannot collide with the panel's build or another extension's.
    expect($manifest->uiPrefix)->toBe('mw');
    expect(File::json($target.'/extension.json')['ui']['prefix'])->toBe('mw');
    expect(File::get($target.'/src/client/styles.css'))->toContain(
        "@import 'tailwindcss/theme.css' layer(theme) prefix(mw);",
        "@import 'tailwindcss/utilities.css' layer(utilities) prefix(mw);",
        "@import '@pterodactyl/sdk/theme.css';",
    );
    expect(File::get($target.'/src/client/screens/ServerScreen.tsx'))->toContain("className={'mw:text-sm mw:text-muted-foreground'}");
    expect(File::get($target.'/README.md'))->toContain('`mw:flex`', "prefix: 'mw'")->not->toContain('{{prefix}}', 'ext:');
    expect($target.'/database/migrations')->toBeDirectory();
    $package = File::json($target.'/package.json');
    expect($package['name'])->toBe('my-widget');
    expect($package['devDependencies'])->toHaveKey('@pterodactyl/sdk');
    // The SDK is a file: link here, so its peer types must resolve from this package.
    expect(File::json($target.'/tsconfig.json')['compilerOptions']['preserveSymlinks'])->toBeTrue();
});
test('the scaffolded readme does not describe an api manifest field', function (): void {
    $target = $this->scaffolder->scaffold(id: 'readme-check', outDir: $this->directory);

    expect((string) file_get_contents($target.'/README.md'))->not->toContain('`api`');
});
test('generated php has valid syntax', function (): void {
    $target = $this->scaffolder->scaffold(id: 'syntax-check', outDir: $this->directory);
    foreach (File::allFiles($target) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        exec(sprintf('%s -l %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($file->getPathname())), $output, $status);
        expect($status)->toBe(0, implode("\n", $output));
    }
});
test('no ui skips the frontend scaffold', function (): void {
    $target = $this->scaffolder->scaffold(id: 'backend-only', ui: false, outDir: $this->directory);
    expect((new ExtensionManifestValidator)->fromDirectory($target)->hasUi())->toBeFalse();
    $this->assertFileDoesNotExist($target.'/package.json');
    $this->assertFileDoesNotExist($target.'/vite.config.mjs');
    $this->assertDirectoryDoesNotExist($target.'/src/client');
    $this->assertStringNotContainsString('## Frontend', (string) file_get_contents($target.'/README.md'));
});
test('rejects invalid and reserved ids', function (): void {
    foreach (['Bad_Id', '9starts-with-digit', 'panel'] as $id) {
        try {
            $this->scaffolder->scaffold(id: $id, outDir: $this->directory);
            $this->fail("Expected id \"{$id}\" to be rejected.");
        } catch (InvalidExtensionException) {
            $this->addToAssertionCount(1);
        }
    }
});
test('derives a usable tailwind prefix from the id', function (string $id, string $prefix): void {
    expect(ExtensionManifest::defaultUiPrefix($id))->toBe($prefix);
    expect(ExtensionManifest::isUsableUiPrefix($prefix))->toBeTrue();
})->with([
    'initials' => ['hello-world', 'hw'],
    'three words' => ['server-importer-pro', 'sip'],
    'one word' => ['backups', 'backups'],
    'digits are dropped' => ['s3-backups-2', 'sb'],
    'long word is shortened' => ['internationalisation', 'internationa'],
    'reserved initials fall back to the letters' => ['small-maps', 'smallmaps'],
    'reserved word' => ['dark', 'darkui'],
    'reserved word that fills the length' => ['perspective', 'perspectivui'],
    'single letter' => ['a', 'aui'],
    'single letters' => ['x-9', 'xui'],
]);
test('writes a chosen tailwind prefix to the manifest, the stylesheet and the stubbed classes', function (): void {
    $target = $this->scaffolder->scaffold(id: 'my-widget', outDir: $this->directory, prefix: 'widget');

    expect((new ExtensionManifestValidator)->fromDirectory($target)->uiPrefix)->toBe('widget');
    expect(File::get($target.'/src/client/styles.css'))->toContain('layer(theme) prefix(widget);', 'layer(utilities) prefix(widget);');
    expect(File::get($target.'/src/client/screens/ServerScreen.tsx'))->toContain("className={'widget:text-sm widget:text-muted-foreground'}");

    $this->artisan('p:extension:make', ['id' => 'from-command', '--out' => $this->directory, '--prefix' => 'fc'])->assertSuccessful();
    expect(File::json($this->directory.'/from-command/extension.json')['ui']['prefix'])->toBe('fc');
});
test('rejects malformed and reserved tailwind prefixes before writing anything', function (string $prefix): void {
    expect(fn () => $this->scaffolder->scaffold(id: 'my-widget', outDir: $this->directory, prefix: $prefix))
        ->toThrow(InvalidExtensionException::class, "Tailwind prefix \"{$prefix}\" must match /^[a-z]{2,12}$/D");
    expect($this->directory.'/my-widget')->not->toBeDirectory();
    $this->artisan('p:extension:make', ['id' => 'my-widget', '--out' => $this->directory, '--prefix' => $prefix])->assertFailed();
})->with(['a', 'My', 'my-widget', 'w2', 'toolongtobeaprefix', 'sm', 'dark', 'hover', 'group', 'tw', 'color']);
test('refuses to overwrite without force', function (): void {
    $this->scaffolder->scaffold(id: 'twice', outDir: $this->directory);
    $this->expectException(InvalidExtensionException::class);
    $this->expectExceptionMessage('--force');
    $this->scaffolder->scaffold(id: 'twice', outDir: $this->directory);
});
test('force overwrites an existing scaffold', function (): void {
    $target = $this->scaffolder->scaffold(id: 'twice', outDir: $this->directory);
    File::put($target.'/leftover.txt', 'stale');
    $this->scaffolder->scaffold(id: 'twice', outDir: $this->directory, force: true);
    $this->assertFileDoesNotExist($target.'/leftover.txt');
    expect($target.'/extension.json')->toBeFile();
});
