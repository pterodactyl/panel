<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Database\SubuserGrantUniquenessMigrationTest;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class);

beforeEach(function (): void {
    config()->set('database.connections.subuser-migration-tests', ['driver' => 'sqlite', 'database' => ':memory:']);
    DB::setDefaultConnection('subuser-migration-tests');
    Schema::create('subusers', function (Blueprint $table): void {
        $table->increments('id');
        $table->unsignedInteger('server_id');
        $table->unsignedInteger('user_id');
        $table->json('permissions')->nullable();
    });
    App::make(Migrator::class)->getRepository()->createRepository();
});

afterEach(function (): void {
    DB::setDefaultConnection('mysql');
    DB::purge('subuser-migration-tests');
});

function migrationPath(): string
{
    return database_path('migrations/2026_10_04_090113_add_unique_server_user_to_subusers_table.php');
}

test('existing duplicate grants prevent the unique index without changing permissions', function (): void {
    DB::table('subusers')->insert([
        ['server_id' => 11, 'user_id' => 22, 'permissions' => '["control.start"]'],
        ['server_id' => 11, 'user_id' => 22, 'permissions' => '["control.stop"]'],
    ]);

    expect(fn () => App::make(Migrator::class)->run([migrationPath()]))->toThrow(QueryException::class);
    expect(DB::table('subusers')->orderBy('id')->pluck('permissions')->all())->toBe(['["control.start"]', '["control.stop"]']);
    expect(Schema::hasIndex('subusers', 'subusers_server_id_user_id_unique'))->toBeFalse();
});

test('the database rejects duplicate grants while allowing a user on another server', function (): void {
    App::make(Migrator::class)->run([migrationPath()]);
    DB::table('subusers')->insert([
        ['server_id' => 11, 'user_id' => 22, 'permissions' => '[]'],
        ['server_id' => 12, 'user_id' => 22, 'permissions' => '[]'],
    ]);
    expect(fn () => DB::table('subusers')->insert(['server_id' => 11, 'user_id' => 22, 'permissions' => '[]']))
        ->toThrow(QueryException::class);
    expect(DB::table('subusers')->count())->toBe(2);
});

test('rolling back the unique invariant preserves the existing grant', function (): void {
    $migrator = App::make(Migrator::class);
    $migrator->run([migrationPath()]);
    DB::table('subusers')->insert(['server_id' => 11, 'user_id' => 22, 'permissions' => '["control.start"]']);
    $migrator->rollback([migrationPath()]);
    expect(DB::table('subusers')->sole()->permissions)->toBe('["control.start"]');
    expect(Schema::hasIndex('subusers', 'subusers_server_id_user_id_unique'))->toBeFalse();
});
