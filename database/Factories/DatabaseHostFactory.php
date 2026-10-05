<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Models\Node;

/**
 * @extends Factory<DatabaseHost>
 */
class DatabaseHostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DatabaseHost::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'node_id' => Node::factory()->withLocation(),
            'name' => fake()->colorName(),
            'host' => fake()->unique()->ipv4(),
            'port' => 3306,
            'username' => fake()->colorName(),
            'password' => Crypt::encrypt(fake()->word()),
        ];
    }
}
