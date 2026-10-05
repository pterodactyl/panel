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
function service(): EggParserService
{
    return new EggParserService();
}
function fixture(string $path): UploadedFile
{
    $absolutePath = base_path('database/Seeders/eggs/'.$path);

    return new UploadedFile($absolutePath, basename($absolutePath), 'application/json', UPLOAD_ERR_OK, true);
}
