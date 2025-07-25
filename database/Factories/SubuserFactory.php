<?php

namespace Database\Factories;

use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'permissions' => [
                Permission::ACTION_WEBSOCKET_CONNECT,
            ],
        ];
    }
}
