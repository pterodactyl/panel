<?php

namespace Pterodactyl\Tests\Traits\Integration;

use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Allocation;

trait CreatesTestModels
{
    /**
     * Creates a server model in the databases for the purpose of testing. If an attribute
     * is passed in that normally requires this function to create a model no model will be
     * created and that attribute's value will be used.
     *
     * The returned server model will have all the relationships loaded onto it.
     */
    public function createServerModel(array $attributes = []): Server
    {
        if (isset($attributes['user_id'])) {
            $attributes['owner_id'] = $attributes['user_id'];
        }

        if (!isset($attributes['owner_id'])) {
            /** @var \Pterodactyl\Models\User $user */
            $user = User::factory()->create();
            $attributes['owner_id'] = $user->id;
        }

        if (!isset($attributes['node_id'])) {
            if (!isset($attributes['location_id'])) {
                /** @var \Pterodactyl\Models\Location $location */
                $location = Location::factory()->create();
                $attributes['location_id'] = $location->id;
            }

            /** @var \Pterodactyl\Models\Node $node */
            $node = Node::factory()->create(['location_id' => $attributes['location_id']]);
            $attributes['node_id'] = $node->id;
        }

        if (!isset($attributes['allocation_id'])) {
            /** @var \Pterodactyl\Models\Allocation $allocation */
            $allocation = Allocation::factory()->create(['node_id' => $attributes['node_id']]);
            $attributes['allocation_id'] = $allocation->id;
        }

        if (empty($attributes['egg_id'])) {
            $egg = !empty($attributes['nest_id'])
                ? Egg::query()->where('nest_id', $attributes['nest_id'])->firstOrFail()
                : $this->getBungeecordEgg();

            $attributes['egg_id'] = $egg->id;
            $attributes['nest_id'] = $egg->nest_id;
        }

        if (empty($attributes['nest_id'])) {
            $attributes['nest_id'] = Egg::query()->findOrFail($attributes['egg_id'])->nest_id;
        }

        unset($attributes['user_id'], $attributes['location_id']);

        /** @var \Pterodactyl\Models\Server $server */
        $server = Server::factory()->create($attributes);

        Allocation::query()->where('id', $server->allocation_id)->update(['server_id' => $server->id]);

        return $server->fresh([
            'location', 'user', 'node', 'allocation', 'nest', 'egg',
        ]);
    }

    /**
     * Generates a user and a server for that user. If an array of permissions is passed it
     * is assumed that the user is actually a subuser of the server.
     *
     * @param string[] $permissions
     *
     * @return array{\Pterodactyl\Models\User, \Pterodactyl\Models\Server}
     */
    public function generateTestAccount(array $permissions = []): array
    {
        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()->create();

        if (empty($permissions)) {
            return [$user, $this->createServerModel(['user_id' => $user->id])];
        }

        $server = $this->createServerModel();

        Subuser::query()->create([
            'user_id' => $user->id,
            'server_id' => $server->id,
            'permissions' => $permissions,
        ]);

        return [$user, $server];
    }

    /**
     * Clones a given egg allowing us to make modifications that don't affect other
     * tests that rely on the egg existing in the correct state.
     */
    protected function cloneEggAndVariables(Egg $egg): Egg
    {
        $model = $egg->replicate(['id', 'uuid']);
        $model->uuid = Uuid::uuid4()->toString();
        $model->push();

        /** @var \Pterodactyl\Models\Egg $model */
        $model = $model->fresh();

        foreach ($egg->variables as $variable) {
            $variable->replicate(['id', 'egg_id'])->forceFill(['egg_id' => $model->id])->push();
        }

        return $model->fresh();
    }

    /**
     * Almost every test just assumes it is using BungeeCord — this is the critical
     * egg model for all tests unless specified otherwise.
     */
    private function getBungeecordEgg(): Egg
    {
        /** @var \Pterodactyl\Models\Egg $egg */
        $egg = Egg::query()->where('author', 'support@pterodactyl.io')->where('name', 'Bungeecord')->firstOrFail();

        return $egg;
    }
}
