<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerTransfer;

/**
 * @extends Factory<ServerTransfer>
 */
class ServerTransferFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ServerTransfer::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'server_id' => Server::factory()->withRelationships(),
            'old_node' => fn (array $attributes) => Server::query()->findOrFail($attributes['server_id'])->node_id,
            'old_allocation' => fn (array $attributes) => Server::query()->findOrFail($attributes['server_id'])->allocation_id,
            'new_node' => Node::factory()->withLocation(),
            'new_allocation' => fn (array $attributes) => Allocation::factory()->create(['node_id' => $attributes['new_node']])->id,
            'old_additional_allocations' => [],
            'new_additional_allocations' => [],
            'successful' => null,
            'archived' => false,
        ];
    }
}
