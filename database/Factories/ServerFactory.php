<?php

namespace Database\Factories;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Pterodactyl\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'name' => $this->faker->firstName,
            'description' => implode(' ', $this->faker->sentences()),
            'skip_scripts' => 0,
            'status' => null,
            'memory' => 512,
            'swap' => 0,
            'disk' => 512,
            'io' => 500,
            'cpu' => 0,
            'threads' => null,
            'oom_killer' => true,
            'startup' => '/bin/bash echo "hello world"',
            'image' => 'foo/bar:latest',
            'allocation_limit' => null,
            'database_limit' => null,
            'backup_limit' => 0,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
