<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Eggs\EggParserServiceTest;

use Illuminate\Http\UploadedFile;
use Pterodactyl\Exceptions\Service\InvalidFileUploadException;
use Pterodactyl\Services\Eggs\EggParserService;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);
test('parses a v2 egg into typed import data', function () {
    $parsed = service()->handle(fixture('minecraft/egg-paper.json'));
    expect($parsed->name)->toBe('Paper');
    expect($parsed->dockerImages['Java 21'])->toBe('ghcr.io/pterodactyl/yolks:java_21');
    expect($parsed->features)->toBe(['eula', 'java_version', 'pid_limit']);
    expect($parsed->variables[0]->environmentVariable)->toBe('MINECRAFT_VERSION');
    expect($parsed->variables[0]->userViewable)->toBeTrue();
});
test('normalizes a v1 image list', function () {
    $parsed = service()->handle(fixture('source-engine/egg-garrys-mod.json'));
    expect($parsed->dockerImages)->toBe(['ghcr.io/pterodactyl/games:source' => 'ghcr.io/pterodactyl/games:source']);
    expect($parsed->variables[0]->environmentVariable)->toBe('SRCDS_MAP');
});
test('rejects unrecognized egg payloads', function () {
    $file = UploadedFile::fake()->createWithContent('invalid.json', '{"meta":{"version":"unknown"}}');
    $this->expectException(InvalidFileUploadException::class);
    $this->expectExceptionMessage('The egg field "meta.version" is missing or invalid.');
    service()->handle($file);
});
test('every bundled egg passes import validation', function (string $path) {
    expect(service()->handle(fixture($path))->name)->not->toBeEmpty();
})->with(fn (): array => array_map(
    // The dataset is built before the application boots, so base_path() is not available yet.
    fn (string $file): string => basename(dirname($file)).'/'.basename($file),
    glob(dirname(__DIR__, 4).'/database/Seeders/eggs/*/*.json') ?: [],
));
test('rejects egg contents an admin could not save', function (callable $corrupt, string $field) {
    $egg = json_decode((string) file_get_contents(base_path('database/Seeders/eggs/minecraft/egg-paper.json')), true);
    $corrupt($egg);
    $file = UploadedFile::fake()->createWithContent('egg.json', (string) json_encode($egg));

    expect(fn () => service()->handle($file))->toThrow(InvalidFileUploadException::class, "The egg field \"{$field}\" is invalid");
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
function service(): EggParserService
{
    return new EggParserService();
}
function fixture(string $path): UploadedFile
{
    $absolutePath = base_path('database/Seeders/eggs/'.$path);

    return new UploadedFile($absolutePath, basename($absolutePath), 'application/json', UPLOAD_ERR_OK, true);
}
