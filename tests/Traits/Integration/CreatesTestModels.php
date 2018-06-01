<?php

namespace Tests\Traits\Integration;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Nest;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Allocation;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;

trait CreatesTestModels
{
    /**
     * Creates a server model in the databases for the purpose of testing. If an attribute
     * is passed in that normally requires this function to create a model no model will be
     * created and that attribute's value will be used.
     *
     * The returned server model will have all of the relationships loaded onto it.
     *
     * @param array $attributes
     * @return \Pterodactyl\Models\Server
     */
    public function createServerModel(array $attributes = []): Server
    {
        /** @var \Illuminate\Database\Eloquent\Factory $factory */
        $factory = $this->app->make(EloquentFactory::class);

        if (isset($attributes['user_id'])) {
            $attributes['owner_id'] = $attributes['user_id'];
        }

        if (! isset($attributes['owner_id'])) {
            $user = $factory->of(User::class)->create();
            $attributes['owner_id'] = $user->id;
        }

        if (! isset($attributes['node_id'])) {
            if (! isset($attributes['location_id'])) {
                $location = $factory->of(Location::class)->create();
                $attributes['location_id'] = $location->id;
            }

            $node = $factory->of(Node::class)->create(['location_id' => $attributes['location_id']]);
            $attributes['node_id'] = $node->id;
        }

        if (! isset($attributes['allocation_id'])) {
            $allocation = $factory->of(Allocation::class)->create(['node_id' => $attributes['node_id']]);
            $attributes['allocation_id'] = $allocation->id;
        }

        if (! isset($attributes['nest_id'])) {
            $nest = Nest::with('eggs')->first();
            $attributes['nest_id'] = $nest->id;

            if (! isset($attributes['egg_id'])) {
                $attributes['egg_id'] = $nest->getRelation('eggs')->first()->id;
            }
        }

        if (! isset($attributes['egg_id'])) {
            $egg = Egg::where('nest_id', $attributes['nest_id'])->first();
            $attributes['egg_id'] = $egg->id;
        }

        unset($attributes['user_id'], $attributes['location_id']);

        $server = $factory->of(Server::class)->create($attributes);

        return Server::with([
            'location', 'user', 'node', 'allocation', 'nest', 'egg',
        ])->findOrFail($server->id);
    }
}
