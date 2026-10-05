<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Extensions\ExtensionSettingFilesTest;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Actions\Extensions\ReplaceExtensionSettingFile;
use Pterodactyl\Services\Extensions\ExtensionSettingDefinition;
use Pterodactyl\Services\Extensions\ExtensionSettingFiles;
use Pterodactyl\Services\Extensions\ExtensionSettings;
use Pterodactyl\Services\Extensions\ExtensionSettingsDefinition;
use Pterodactyl\Services\Extensions\ExtensionSettingValueGuard;
use Pterodactyl\Tests\TestCase;

use function pterodactylTestCase;

uses(TestCase::class);

const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
const SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><rect width="1" height="1"/></svg>';

beforeEach(function (): void {
    config(['extensions.files_disk' => 'local']);
    Storage::fake('local');
});

test('stores an upload under a random name derived from its content', function (): void {
    $files = new ExtensionSettingFiles;
    // The client claims a script; only the bytes decide what the file is.
    $name = $files->store('probe', upload(base64_decode(PNG, true), 'shell.php', 'application/x-php'), ExtensionSettingFiles::IMAGES, 64);

    expect($name)->toMatch(ExtensionSettingFiles::NAME_PATTERN)->toEndWith('.png')->not->toContain('shell');
    expect(ExtensionSettingValueGuard::fileReference($name))->toBe($name);
    Storage::disk('local')->assertExists('extension-files/probe/'.$name);
    expect($files->read('probe', $name))->toBe(['contents' => base64_decode(PNG, true), 'mime' => 'image/png']);
    expect($files->store('probe', upload(base64_decode(PNG, true)), ExtensionSettingFiles::IMAGES, 64))->not->toBe($name);
    expect(ExtensionSettingFiles::url('probe', $name))->toBe('/extension-files/probe/'.$name);
});

test('rejects uploads whose content is not an allowed type', function (string $bytes, array $mimes, string $message): void {
    expect(fn () => (new ExtensionSettingFiles)->store('probe', upload($bytes, 'logo.png', 'image/png'), $mimes, 64))
        ->toThrow(ValidationException::class, $message);
    expect(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    'script named like an image' => ['<?php echo 1;', ExtensionSettingFiles::IMAGES, 'Unsupported file type'],
    'html' => ['<html><script>alert(1)</script></html>', ExtensionSettingFiles::IMAGES, 'Unsupported file type'],
    'svg when not listed' => [SVG, ExtensionSettingFiles::IMAGES, 'Unsupported file type'],
    'type outside the allow-list' => [fn (): string => base64_decode(PNG, true), ['image/jpeg'], 'Unsupported file type'],
    'truncated image' => ["\x89PNG\r\n\x1a\nnot really", ExtensionSettingFiles::IMAGES, 'corrupt or unreadable'],
    'svg with script' => ['<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', ['image/svg+xml'], 'active or external content'],
    'svg with handler' => ['<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>', ['image/svg+xml'], 'active or external content'],
    'svg with script link' => ['<svg xmlns="http://www.w3.org/2000/svg"><a href="javascript:alert(1)"><rect/></a></svg>', ['image/svg+xml'], 'active or external content'],
    'svg with doctype entity' => ['<?xml version="1.0"?><!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]><svg>&x;</svg>', ['image/svg+xml'], 'Unsupported file type'],
    'malformed svg' => ['<svg xmlns="http://www.w3.org/2000/svg"><g></svg>', ['image/svg+xml'], 'not valid XML'],
    'empty' => ['', ExtensionSettingFiles::IMAGES, 'empty'],
]);

test('enforces the size limit of the definition', function (): void {
    $bytes = base64_decode(PNG, true).str_repeat("\0", 2048);
    expect(fn () => (new ExtensionSettingFiles)->store('probe', upload($bytes), ExtensionSettingFiles::IMAGES, 1))
        ->toThrow(ValidationException::class, 'larger than the allowed 1 KB');
});

test('svg is stored only when the definition lists it', function (): void {
    $files = new ExtensionSettingFiles;
    $name = $files->store('probe', upload(SVG, 'logo.svg', 'image/svg+xml'), ['image/png', 'image/svg+xml'], 64);
    expect($name)->toEndWith('.svg');
    expect($files->read('probe', $name)['mime'] ?? null)->toBe('image/svg+xml');
});

test('read and delete only touch well formed names inside the extension directory', function (): void {
    $files = new ExtensionSettingFiles;
    Storage::disk('local')->put('extension-files/probe/notes.txt', 'private');
    Storage::disk('local')->put('secret.png', 'private');
    $name = $files->store('probe', upload(base64_decode(PNG, true)), ExtensionSettingFiles::IMAGES, 64);

    expect($files->read('probe', 'notes.txt'))->toBeNull();
    expect($files->read('probe', '../../secret.png'))->toBeNull();
    expect($files->read('Bad/../probe', $name))->toBeNull();
    expect($files->read('other', $name))->toBeNull();
    $files->delete('probe', '../../secret.png');
    Storage::disk('local')->assertExists('secret.png');
    $files->delete('probe', $name);
    Storage::disk('local')->assertMissing('extension-files/probe/'.$name);
});

test('replacing or clearing a file setting deletes the previous file', function (): void {
    $definition = new ExtensionSettingsDefinition(settings(), [
        ExtensionSettingDefinition::make('logo', 'logo', '/favicons/favicon.ico')->file(maxKilobytes: 64),
        ExtensionSettingDefinition::make('title', 'title', 'Panel'),
    ]);
    $action = new ReplaceExtensionSettingFile(new ExtensionSettingFiles);

    $first = $action->replace($definition, 'logo', upload(base64_decode(PNG, true)))[0]['value'];
    expect($first)->toStartWith('/extension-files/test-fixture/');
    Storage::disk('local')->assertExists(mb_ltrim((string) $first, '/'));

    $second = $action->replace($definition, 'logo', upload(base64_decode(PNG, true)))[0]['value'];
    expect($second)->not->toBe($first);
    Storage::disk('local')->assertMissing(mb_ltrim((string) $first, '/'));
    Storage::disk('local')->assertExists(mb_ltrim((string) $second, '/'));

    expect($action->replace($definition, 'logo', null)[0]['value'])->toBe('/favicons/favicon.ico');
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

test('a rejected upload keeps the current file and non file settings refuse uploads', function (): void {
    $definition = new ExtensionSettingsDefinition(settings(), [
        ExtensionSettingDefinition::make('logo', 'logo', null)->file(maxKilobytes: 64),
        ExtensionSettingDefinition::make('title', 'title', 'Panel'),
    ]);
    $action = new ReplaceExtensionSettingFile(new ExtensionSettingFiles);
    $current = $action->replace($definition, 'logo', upload(base64_decode(PNG, true)))[0]['value'];

    expect(fn () => $action->replace($definition, 'logo', upload('<?php echo 1;')))->toThrow(ValidationException::class);
    expect(fn () => $action->replace($definition, 'title', upload(base64_decode(PNG, true))))->toThrow(ValidationException::class, 'does not accept a file');
    expect($definition->get('logo'))->toBe($current);
    expect(Storage::disk('local')->allFiles())->toHaveCount(1);
});

function upload(string $bytes, string $name = 'logo.png', string $mime = 'image/png'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'ptero-setting-file-');
    file_put_contents($path, $bytes);

    return new UploadedFile($path, $name, $mime, null, true);
}

function settings(): ExtensionSettings
{
    return (fn (): ExtensionSettings => new class extends ExtensionSettings
    {
        /** @var array<string, mixed> */
        private array $values = [];

        public function __construct()
        {
            parent::__construct('test-fixture');
        }

        public function get(string $key, mixed $default = null): mixed
        {
            return array_key_exists($key, $this->values) ? $this->values[$key] : $default;
        }

        public function setMany(array $values): void
        {
            $this->values = array_replace($this->values, $values);
        }

        public function forget(string $key): void
        {
            unset($this->values[$key]);
        }
    })->call(pterodactylTestCase());
}
