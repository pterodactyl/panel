<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Database\SchemaDumpTest;

use Illuminate\Support\Facades\File;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);
test('every mysql family connection loads the same schema dump on a fresh install', function (string $connection): void {
    expect(database_path("schema/{$connection}-schema.sql"))->toBeFile()
        ->and(File::get(database_path("schema/{$connection}-schema.sql")))->toBe(File::get(database_path('schema/mysql-schema.sql')));
})->with(fn (): array => collect((require dirname(__DIR__, 3).'/config/database.php')['connections'])
    ->filter(fn (array $connection): bool => in_array($connection['driver'], ['mysql', 'mariadb'], true))
    ->keys()
    ->all());
