<?php

namespace Pterodactyl\Tests\Integration\Services\Servers;

use Exception;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerVariable;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Services\Servers\StartupModificationService;

class StartupModificationServiceTest extends IntegrationTestCase
{
    /**
     * Test that a non-admin request to modify the server startup parameters does
     * not perform any egg or nest updates. This also attempts to pass through an
     * egg_id variable which should have no impact if the request is coming from
     * a non-admin entity.
     */
    public function testNonAdminCanModifyServerVariables()
    {
        // Theoretically lines up with the Bungeecord Minecraft egg.
        $server = $this->createServerModel(['egg_id' => 1]);

        try {
            $this->app->make(StartupModificationService::class)->handle($server, [
                'egg_id' => $server->egg_id + 1,
                'environment' => [
                    'BUNGEE_VERSION' => '$$',
                    'SERVER_JARFILE' => 'server.jar',
                ],
            ]);

            $this->assertTrue(false, 'This assertion should not be called.');
        } catch (Exception $exception) {
            $this->assertInstanceOf(ValidationException::class, $exception);

            /** @var \Illuminate\Validation\ValidationException $exception */
            $errors = $exception->validator->errors()->toArray();

            $this->assertCount(1, $errors);
            $this->assertArrayHasKey('environment.BUNGEE_VERSION', $errors);
            $this->assertCount(1, $errors['environment.BUNGEE_VERSION']);
            $this->assertSame('The Bungeecord Version variable may only contain letters and numbers.', $errors['environment.BUNGEE_VERSION'][0]);
        }

        ServerVariable::query()->where('variable_id', $server->variables[1]->id)->delete();

        /** @var \Pterodactyl\Models\Server $result */
        $result = $this->app->make(StartupModificationService::class)->handle($server, [
            'egg_id' => $server->egg_id + 1,
            'startup' => 'random gibberish',
            'environment' => [
                'BUNGEE_VERSION' => '1234',
                'SERVER_JARFILE' => 'test.jar',
            ],
        ]);

        $this->assertInstanceOf(Server::class, $result);
        $this->assertCount(2, $result->variables);
        $this->assertSame($server->startup, $result->startup);
        $this->assertSame('1234', $result->variables[0]->server_value);
        $this->assertSame('test.jar', $result->variables[1]->server_value);
    }
}
