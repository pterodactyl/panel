<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\User;

/**
 * @extends Factory<Subuser>
 */
class SubuserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Subuser::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'server_id' => Server::factory()->withRelationships(),
            'permissions' => [
                Permissions::WebsocketConnect->value,
            ],
        ];
    }
}
