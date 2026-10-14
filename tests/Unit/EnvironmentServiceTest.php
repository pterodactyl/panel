<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\EnvironmentServiceTest;

use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerVariable;
use Pterodactyl\Services\Servers\EnvironmentService;
use Pterodactyl\Tests\TestCase;
use stdClass;

uses(TestCase::class);
test('builds scalar environment values from every supported source', function () {
    config()->set('pterodactyl.environment_variables', ['P_SERVER_ALLOCATION_LIMIT' => 'allocation_limit', 'CONFIGURED_CALLBACK' => static fn (Server $server): string => $server->uuid]);
    $variable = new EggVariable();
    $variable->forceFill(['id' => 1, 'env_variable' => 'GAME_MODE', 'default_value' => 'survival']);
    $override = new ServerVariable();
    $override->forceFill(['variable_id' => 1, 'variable_value' => 'creative']);
    $service = new EnvironmentService();
    $service->setEnvironmentKey('CUSTOM_FLAG', static fn (Server $server): bool => $server->oom_disabled);
    expect($service->forServer(server([$variable], [$override])))->toBe(['GAME_MODE' => 'creative', 'STARTUP' => 'java -jar server.jar', 'P_SERVER_LOCATION' => 'nyc', 'P_SERVER_UUID' => 'server-uuid', 'P_SERVER_ALLOCATION_LIMIT' => 4, 'CONFIGURED_CALLBACK' => 'server-uuid', 'CUSTOM_FLAG' => true]);
});
test('rejects non scalar environment values', function () {
    config()->set('pterodactyl.environment_variables', []);
    $service = new EnvironmentService();
    $service->setEnvironmentKey('INVALID', static fn (Server $server): stdClass => new stdClass());
    $this->expectException(InvalidArgumentException::class);
    $service->forServer(server());
});
test('rejects invalid configured environment mappings', function (array $configured) {
    config()->set('pterodactyl.environment_variables', $configured);
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Each configured server environment variable must map a string key to an attribute path or callable.');

    (new EnvironmentService())->forServer(server());
})->with([
    'numeric key' => [[0 => 'uuid']],
    'non-string source' => [['INVALID' => ['uuid']]],
]);
/**
 * @param  list<EggVariable>  $variables
 * @param  list<ServerVariable>  $overrides
 */
function server(array $variables = [], array $overrides = []): Server
{
    $location = new Location();
    $location->forceFill(['short' => 'nyc']);
    $egg = new Egg();
    $egg->setRelation('variables', new Collection($variables));
    $server = new Server();
    $server->forceFill(['startup' => 'java -jar server.jar', 'uuid' => 'server-uuid', 'allocation_limit' => 4, 'oom_disabled' => true]);
    $server->setRelation('location', $location);
    $server->setRelation('egg', $egg);
    $server->setRelation('serverVariables', new Collection($overrides));

    return $server;
}
