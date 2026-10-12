<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Data\EggImportDataTest;

use Illuminate\Http\UploadedFile;
use Pterodactyl\Data\EggImportData;
use Pterodactyl\Exceptions\Service\InvalidFileUploadException;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);
test('parses a v2 egg into typed import data', function () {
    $parsed = EggImportData::fromFile(fixture('minecraft/egg-paper.json'));
    expect($parsed->name)->toBe('Paper');
    expect($parsed->dockerImages['Java 21'])->toBe('ghcr.io/pterodactyl/yolks:java_21');
    expect($parsed->features)->toBe(['eula', 'java_version', 'pid_limit']);
    expect($parsed->variables[0]->environmentVariable)->toBe('MINECRAFT_VERSION');
    expect($parsed->variables[0]->userViewable)->toBeTrue();
});
test('normalizes a v1 image list', function () {
    $parsed = EggImportData::fromFile(fixture('source-engine/egg-garrys-mod.json'));
    expect($parsed->dockerImages)->toBe(['ghcr.io/pterodactyl/games:source' => 'ghcr.io/pterodactyl/games:source']);
    expect($parsed->variables[0]->environmentVariable)->toBe('SRCDS_MAP');
});
test('reads tag slugs trimmed, without blanks or duplicates, and null when the key is absent', function (): void {
    $egg = json_decode((string) file_get_contents(base_path('database/Seeders/eggs/minecraft/egg-paper.json')), true);
    expect(EggImportData::fromJson((string) json_encode($egg))->tags)->toBeNull();

    $egg['tags'] = [' minecraft ', '', 'Custom Tag', 'minecraft', '7'];
    expect(EggImportData::fromJson((string) json_encode($egg))->tags)->toBe(['minecraft', 'Custom Tag', '7']);
});
test('rejects tags that are not short strings', function (mixed $tags, string $message): void {
    $egg = json_decode((string) file_get_contents(base_path('database/Seeders/eggs/minecraft/egg-paper.json')), true);
    $egg['tags'] = $tags;

    expect(fn () => EggImportData::fromJson((string) json_encode($egg)))->toThrow(InvalidFileUploadException::class, $message);
})->with([
    'a string' => ['minecraft', 'The egg field "tags" is missing or invalid.'],
    'a number' => [[1], 'The egg field "tags" is missing or invalid.'],
    'a long slug' => [[str_repeat('a', 192)], 'The egg field "tags.0" is invalid'],
]);
test('rejects unrecognized egg payloads', function () {
    $file = UploadedFile::fake()->createWithContent('invalid.json', '{"meta":{"version":"unknown"}}');
    $this->expectException(InvalidFileUploadException::class);
    $this->expectExceptionMessage('The egg field "meta.version" is missing or invalid.');
    EggImportData::fromFile($file);
});
test('every bundled egg passes import validation', function (string $path) {
    expect(EggImportData::fromFile(fixture($path))->name)->not->toBeEmpty();
})->with(fn (): array => array_map(
    // The dataset is built before the application boots, so base_path() is not available yet.
    fn (string $file): string => basename(dirname($file)).'/'.basename($file),
    glob(dirname(__DIR__, 3).'/database/Seeders/eggs/*/*.json') ?: [],
));
test('rejects egg contents an admin could not save', function (callable $corrupt, string $field) {
    $egg = json_decode((string) file_get_contents(base_path('database/Seeders/eggs/minecraft/egg-paper.json')), true);
    $corrupt($egg);
    $file = UploadedFile::fake()->createWithContent('egg.json', (string) json_encode($egg));

    expect(fn () => EggImportData::fromFile($file))->toThrow(InvalidFileUploadException::class, "The egg field \"{$field}\" is invalid");
})->with([
    'author that is not an email' => [function (array &$egg): void {
        $egg['author'] = 'not-an-email';
    }, 'author'],
    'no docker images' => [function (array &$egg): void {
        $egg['docker_images'] = [];
    }, 'docker_images'],
    'docker image with shell characters' => [function (array &$egg): void {
        $egg['docker_images']['Bad'] = 'ghcr.io/x;rm -rf /';
    }, 'docker_images.Bad'],
    'config that is not json' => [function (array &$egg): void {
        $egg['config']['files'] = '{not json';
    }, 'config_files'],
    'reserved variable name in any case' => [function (array &$egg): void {
        $egg['variables'][0]['env_variable'] = 'server_memory';
    }, 'variables.0.env_variable'],
    'dotted variable name' => [function (array &$egg): void {
        $egg['variables'][0]['env_variable'] = 'SERVER.PORT';
    }, 'variables.0.env_variable'],
    'duplicate variable names' => [function (array &$egg): void {
        $egg['variables'][1]['env_variable'] = $egg['variables'][0]['env_variable'];
    }, 'variables.0.env_variable'],
]);
function fixture(string $path): UploadedFile
{
    $absolutePath = base_path('database/Seeders/eggs/'.$path);

    return new UploadedFile($absolutePath, basename($absolutePath), 'application/json', UPLOAD_ERR_OK, true);
}
