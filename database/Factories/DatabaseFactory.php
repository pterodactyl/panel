<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Models\Server;

/**
 * @extends Factory<Database>
 */
class DatabaseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Database::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        static $password;

        return [
            'server_id' => Server::factory()->withRelationships(),
            'database_host_id' => DatabaseHost::factory(),
            'database' => Str::random(10),
            'username' => Str::random(10),
            'remote' => '%',
            'password' => $password ?: encrypt('test123'),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }

    public function withServerAndHost(): self
    {
        return $this->state(fn (): array => [
            'server_id' => Server::factory()->withRelationships()->create()->id,
            'database_host_id' => DatabaseHost::factory()->create()->id,
        ]);
    }
}
