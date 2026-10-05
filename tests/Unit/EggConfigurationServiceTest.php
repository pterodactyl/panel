<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\EggConfigurationServiceTest;

use PHPUnit\Framework\Assert;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Eggs\EggConfigurationService;
use Pterodactyl\Services\Servers\ServerConfigurationStructureService;
use Pterodactyl\Support\JsonEmptyObject;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);
test('maps legacy placeholders into the wings configuration', function () {
    $server = server('{"scalar":"invalid","missing-find":{"parser":"json"},"server.properties":{"parser":"properties","find":{"server-port":"{{server.build.default.port}}","enabled":true,"conditional":{"old":"{{env.PORT}}","new":"{{server.build.default.port}}"},"docker-interface":"{{config.docker.interface}}","mixed-placeholders":"{{config.docker.interface}} {{server.build.default.port}} {{env.PORT}}","missing-server":"{{server.missing}}","missing-env":"{{env.MISSING}}","empty-object":"{{env.EMPTY_OBJECT}}"}}}');
    $service = new EggConfigurationService(configurationStructure($server));
    expect($service->handle($server))->toBe(['startup' => ['done' => ['Server started'], 'user_interaction' => [], 'strip_ansi' => false], 'stop' => ['type' => 'command', 'value' => 'stop'], 'configs' => [['parser' => 'properties', 'file' => 'server.properties', 'replace' => [['match' => 'server-port', 'replace_with' => '25565'], ['match' => 'enabled', 'replace_with' => true], ['match' => 'conditional', 'if_value' => 'old', 'replace_with' => '3000'], ['match' => 'conditional', 'if_value' => 'new', 'replace_with' => '25565'], ['match' => 'docker-interface', 'replace_with' => '{{config.docker.network.interface}}'], ['match' => 'mixed-placeholders', 'replace_with' => '{{config.docker.network.interface}} 25565 3000'], ['match' => 'missing-server', 'replace_with' => ''], ['match' => 'missing-env', 'replace_with' => ''], ['match' => 'empty-object', 'replace_with' => 'Array']]]]]);
});
test('defaults missing startup flags and stop commands', function () {
    $server = server('{}', '{"done":[]}', null);
    $service = new EggConfigurationService(configurationStructure($server));
    expect($service->handle($server))->toBe(['startup' => ['done' => [], 'user_interaction' => [], 'strip_ansi' => false], 'stop' => ['type' => 'command', 'value' => ''], 'configs' => []]);
});
test('preserves an enabled strip ansi flag', function () {
    $server = server('{}', '{"done":[],"strip_ansi":true}');
    $service = new EggConfigurationService(configurationStructure($server));

    expect($service->handle($server)['startup']['strip_ansi'])->toBeTrue();
});
function server(string $configurationFiles, ?string $configurationStartup = '{"done":"Server started","strip_ansi":false}', ?string $configurationStop = 'stop'): Server
{
    $egg = new Egg();
    $egg->forceFill(['config_files' => $configurationFiles, 'config_startup' => $configurationStartup, 'config_stop' => $configurationStop]);
    $server = new Server();
    $server->setRelation('egg', $egg);

    return $server;
}
function configurationStructure(Server $server): ServerConfigurationStructureService
{
    return new class($server) extends ServerConfigurationStructureService
    {
        public function __construct(private readonly Server $expected) {}

        public function handle(Server $server, array $override = [], bool $legacy = false): array
        {
            Assert::assertSame($this->expected, $server);
            Assert::assertSame([], $override);
            Assert::assertTrue($legacy);

            return ['build' => ['default' => ['port' => 25565], 'env' => ['PORT' => 3000, 'EMPTY_OBJECT' => new JsonEmptyObject()]]];
        }
    };
}
