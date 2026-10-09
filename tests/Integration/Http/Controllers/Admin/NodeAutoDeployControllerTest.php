<?php

namespace Pterodactyl\Tests\Integration\Http\Controllers\Admin;

use Pterodactyl\Models\Node;
use Pterodactyl\Models\User;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Models\Location;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Tests\Integration\Http\HttpTestCase;

class NodeAutoDeployControllerTest extends HttpTestCase
{
    public function testGeneratedTokenHasNodeWritePermission(): void
    {
        $node = Node::factory()->for(Location::factory())->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('admin.nodes.view.configuration.token', ['node' => $node]));

        $response->assertOk();
        $response->assertJsonPath('node', $node->id);

        $key = ApiKey::query()
            ->where('identifier', substr($response->json('token'), 0, ApiKey::IDENTIFIER_LENGTH))
            ->firstOrFail();

        $this->assertSame(AdminAcl::READ | AdminAcl::WRITE, $key->r_nodes);
    }
}
