<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Ramsey\Uuid\Uuid;

/**
 * @extends Factory<Server>
 */
class ServerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Server::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'uuid' => Uuid::uuid4()->toString(),
            'uuidShort' => Str::lower(Str::random(8)),
            'name' => fake()->firstName(),
            'description' => implode(' ', fake()->sentences()),
            'skip_scripts' => 0,
            'status' => null,
            'memory' => 512,
            'swap' => 0,
            'disk' => 512,
            'io' => 500,
            'cpu' => 0,
            'threads' => null,
            'oom_disabled' => 0,
            'startup' => '/bin/bash echo "hello world"',
            'image' => 'foo/bar:latest',
            'allocation_limit' => null,
            'database_limit' => null,
            'backup_limit' => 0,
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }

    public function withRelationships(): self
    {
        return $this->state(function (): array {
            $node = Node::factory()->withLocation()->create();
            $allocation = Allocation::factory()->for($node)->create();
            $egg = Egg::factory()->create();

            EggVariable::factory()->create(['egg_id' => $egg->id, 'user_viewable' => 1, 'user_editable' => 1]);

            return [
                'owner_id' => User::factory()->create()->id,
                'node_id' => $node->id,
                'allocation_id' => $allocation->id,
                'egg_id' => $egg->id,
                'allocation_limit' => 5,
                'database_limit' => 5,
            ];
        })->afterCreating(function (Server $server): void {
            $server->allocation()->update(['server_id' => $server->id]);
        });
    }
}
