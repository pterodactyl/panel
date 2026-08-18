<?php

namespace Pterodactyl\Tests\Integration\Http\Controllers\Admin\ServersController;

use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Http\HttpTestCase;

class UpdateServerDetailsTest extends HttpTestCase
{
    public function testExternalIdMustBeUniqueWhenUpdatingServerDetails(): void
    {
        $server = $this->createServerModel(['external_id' => 'first-external-id']);
        $otherServer = $this->createServerModel(['external_id' => 'duplicate-external-id']);

        $this->actingAs(User::factory()->admin()->create())
            ->withHeaders(['Accept' => 'text/html'])
            ->patch(route('admin.servers.view.details', ['server' => $server]), [
                'external_id' => $otherServer->external_id,
                'owner_id' => $server->owner_id,
                'name' => $server->name,
                'description' => $server->description,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('external_id');

        $this->assertSame('first-external-id', $server->refresh()->external_id);
    }
}
