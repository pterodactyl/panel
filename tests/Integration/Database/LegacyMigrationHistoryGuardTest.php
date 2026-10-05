<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Database\LegacyMigrationHistoryGuardTest;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use RuntimeException;

uses(IntegrationTestCase::class);

function historyGuard(): Migration
{
    static $migration = null;

    return $migration ??= require database_path('migrations/2026_05_30_000000_require_complete_legacy_migration_history.php');
}

test('accepts a database recording every 1.x migration', function (): void {
    expect(fn () => historyGuard()->up())->not->toThrow(RuntimeException::class);
});

test('refuses a database from an older 1.x release', function (): void {
    DB::table('migrations')->where('migration', '2024_07_13_091852_clear_unused_allocation_notes')->delete();

    expect(fn () => historyGuard()->up())->toThrow(
        RuntimeException::class,
        'missing 1 Pterodactyl Panel 1.x migration(s), starting with 2024_07_13_091852_clear_unused_allocation_notes'
    );
});
