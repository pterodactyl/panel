<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Servers\ChangeServerEggTest;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Servers\ChangesServerEgg;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerVariable;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

use function pterodactylTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

/**
 * A copy of the server's egg whose variables share environment names with the
 * original but are distinct rows, as two related eggs would have.
 */
function targetEgg(Server $server): Egg
{
    return (function () use ($server): Egg {
        $egg = $this->cloneEggAndVariables($server->egg);
        $egg->forceFill(['startup' => 'java -jar new.jar', 'docker_images' => ['Java 21' => 'ghcr.io/example/java:21', 'Java 17' => 'ghcr.io/example/java:17']])->save();

        return $egg->refresh();
    })->call(pterodactylTestCase());
}

function store(Server $server, string $environment, string $value): void
{
    $variable = EggVariable::query()->where('egg_id', $server->egg_id)->where('env_variable', $environment)->firstOrFail();
    ServerVariable::query()->updateOrCreate(['server_id' => $server->id, 'variable_id' => $variable->id], ['variable_value' => $value]);
}

function changer(): ChangesServerEgg
{
    return (fn (): ChangesServerEgg => $this->app->make(ChangesServerEgg::class))->call(pterodactylTestCase());
}

test('egg, startup command and docker image are reset to the new egg defaults', function () {
    $server = $this->createServerModel(['startup' => 'custom startup', 'image' => 'custom/image']);
    $egg = targetEgg($server);
    store($server, 'BUNGEE_VERSION', '1234');
    store($server, 'SERVER_JARFILE', 'custom.jar');

    $result = changer()->change($server, $egg);

    expect($result)->toBeInstanceOf(Server::class)
        ->and($result->id)->toBe($server->id)
        ->and($result->egg_id)->toBe($egg->id)
        ->and($result->startup)->toBe('java -jar new.jar')
        ->and($result->image)->toBe('ghcr.io/example/java:21')
        ->and($result->isInstalled())->toBeTrue()
        ->and(ServerVariable::query()->where('server_id', $server->id)->count())->toBe(0);
    expect($result->variables)->toHaveCount(2);
    foreach ($result->variables as $variable) {
        expect($variable->server_value)->toBeNull();
    }
});

test('matching variable values are carried over when requested', function () {
    $server = $this->createServerModel();
    $egg = targetEgg($server);
    store($server, 'BUNGEE_VERSION', '1234');
    store($server, 'SERVER_JARFILE', 'custom.jar');

    $result = changer()->change($server, $egg, keepVariables: true);

    $values = $result->variables->pluck('server_value', 'env_variable')->all();
    expect($values)->toEqual(['BUNGEE_VERSION' => '1234', 'SERVER_JARFILE' => 'custom.jar']);
    // The values now belong to the new egg's variables, not the previous egg's.
    expect(ServerVariable::query()->where('server_id', $server->id)->pluck('variable_id')->sort()->values()->all())
        ->toBe($egg->variables()->pluck('id')->sort()->values()->all());
});

test('carried values must satisfy the new variable rules and exist on the new egg', function () {
    $server = $this->createServerModel();
    $egg = targetEgg($server);
    store($server, 'BUNGEE_VERSION', '1234');
    store($server, 'SERVER_JARFILE', 'custom.jar');
    $egg->variables()->where('env_variable', 'BUNGEE_VERSION')->update(['rules' => 'required|string|in:latest']);
    $egg->variables()->where('env_variable', 'SERVER_JARFILE')->update(['env_variable' => 'JARFILE']);

    $result = changer()->change($server, $egg, keepVariables: true);

    expect(ServerVariable::query()->where('server_id', $server->id)->count())->toBe(0);
    expect($result->variables->pluck('server_value', 'env_variable')->all())->toEqual(['BUNGEE_VERSION' => null, 'JARFILE' => null]);
});

test('current startup and image are kept when the new egg defines no defaults', function () {
    $server = $this->createServerModel(['startup' => 'custom startup', 'image' => 'custom/image']);
    $egg = targetEgg($server);
    $egg->forceFill(['startup' => null, 'docker_images' => []])->save();

    $result = changer()->change($server, $egg->refresh());

    expect($result->egg_id)->toBe($egg->id)
        ->and($result->startup)->toBe('custom startup')
        ->and($result->image)->toBe('custom/image');
});

test('changing to the current egg restores its defaults', function () {
    $server = $this->createServerModel(['startup' => 'custom startup']);
    store($server, 'BUNGEE_VERSION', '1234');

    $kept = changer()->change($server, $server->egg, keepVariables: true);
    expect($kept->startup)->toBe($server->egg->startup)
        ->and($kept->variables->firstWhere('env_variable', 'BUNGEE_VERSION')->server_value)->toBe('1234');

    $reset = changer()->change($kept, $server->egg);
    expect($reset->variables->firstWhere('env_variable', 'BUNGEE_VERSION')->server_value)->toBeNull();
});

test('other servers keep their variable values', function () {
    $server = $this->createServerModel();
    $other = $this->createServerModel();
    store($other, 'BUNGEE_VERSION', '5678');

    changer()->change($server, targetEgg($server));

    expect(ServerVariable::query()->where('server_id', $other->id)->value('variable_value'))->toBe('5678');
});

test('a failed change leaves the server untouched', function () {
    $server = $this->createServerModel(['startup' => 'custom startup']);
    store($server, 'BUNGEE_VERSION', '1234');
    // An egg that was deleted after it was loaded: the stored values are already
    // gone by the time the server row is rejected by its foreign key.
    $egg = targetEgg($server);
    $original = $server->egg_id;
    DB::table('egg_variables')->where('egg_id', $egg->id)->delete();
    DB::table('eggs')->where('id', $egg->id)->delete();

    expect(fn () => changer()->change($server, $egg))->toThrow(QueryException::class);

    $fresh = Server::query()->findOrFail($server->id);
    expect($fresh->egg_id)->toBe($original)
        ->and($fresh->startup)->toBe('custom startup')
        ->and(DB::table('server_variables')->where('server_id', $server->id)->value('variable_value'))->toBe('1234');
});
