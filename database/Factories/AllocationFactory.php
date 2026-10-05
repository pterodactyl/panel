<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;

/**
 * @extends Factory<Allocation>
 */
class AllocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Allocation::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'node_id' => Node::factory()->withLocation(),
            'ip' => fake()->unique()->ipv4(),
            'port' => fake()->unique()->numberBetween(1024, 65535),
        ];
    }

    /**
     * Attaches the allocation to a specific server model.
     */
    public function forServer(Server $server): self
    {
        return $this->for($server)->for($server->node);
    }

    /**
     * Attaches the allocation to a freshly created server on the same node.
     */
    public function withServer(): self
    {
        return $this->state(function (): array {
            $server = Server::factory()->withRelationships()->create();

            return [
                'node_id' => $server->node_id,
                'server_id' => $server->id,
            ];
        });
    }
}
