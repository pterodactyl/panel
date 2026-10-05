<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Eggs\EggImporterServiceTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Pterodactyl\Contracts\Eggs\ImportsEggs;
use Pterodactyl\Contracts\Eggs\UpdatesEggsFromImports;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class);
uses(DatabaseTransactions::class);
test('imports and updates eggs from typed share data', function () {
    $egg = $this->app->make(ImportsEggs::class)->import(fixture('minecraft/egg-paper.json'));
    expect($egg->name)->toBe('Paper');
    expect($egg->author)->toBe('parker@pterodactyl.io');
    expect($egg->docker_images['Java 21'])->toBe('ghcr.io/pterodactyl/yolks:java_21');
    expect($egg->variables()->count())->toBe(4);
    $updated = $this->app->make(UpdatesEggsFromImports::class)->update($egg, fixture('source-engine/egg-garrys-mod.json'));
    expect($updated->name)->toBe('Garrys Mod');
    expect($updated->docker_images)->toBe(['ghcr.io/pterodactyl/games:source' => 'ghcr.io/pterodactyl/games:source']);
    expect($updated->variables()->count())->toBe(8);
    expect($updated->variables()->where('env_variable', 'MINECRAFT_VERSION')->exists())->toBeFalse();
});
function fixture(string $path): UploadedFile
{
    $absolutePath = base_path('database/Seeders/eggs/'.$path);

    return new UploadedFile($absolutePath, basename($absolutePath), 'application/json', UPLOAD_ERR_OK, true);
}
