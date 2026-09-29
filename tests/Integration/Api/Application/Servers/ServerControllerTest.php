<?php

namespace Pterodactyl\Tests\Integration\Api\Application\Servers;

use Pterodactyl\Tests\Integration\Api\Application\ApplicationApiIntegrationTestCase;

class ServerControllerTest extends ApplicationApiIntegrationTestCase
{
    /**
     * Test that the "skip scripts" state is returned for a server.
     */
    public function testSkipScriptsStateIsReturned()
    {
        $server = $this->createServerModel(['skip_scripts' => true]);

        $this->getJson('/api/application/servers/' . $server->id)
            ->assertOk()
            ->assertJsonPath('attributes.container.skip_scripts', true);

        $server->update(['skip_scripts' => false]);

        $this->getJson('/api/application/servers/' . $server->id)
            ->assertOk()
            ->assertJsonPath('attributes.container.skip_scripts', false);
    }
}
