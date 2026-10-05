<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Pterodactyl\Models\Egg;
use Ramsey\Uuid\Uuid;

/**
 * @extends Factory<Egg>
 */
class EggFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Egg::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'uuid' => Uuid::uuid4()->toString(),
            'author' => 'support@pterodactyl.io',
            'name' => fake()->name(),
            'description' => implode(' ', fake()->sentences()),
            'docker_images' => ['Java' => 'ghcr.io/pterodactyl/yolks:java_23'],
            'features' => ['eula'],
            'file_denylist' => ['secret.txt'],
            'startup' => 'java -jar test.jar',
            'config_from' => null,
            'config_stop' => 'stop',
            'config_startup' => '{"done": ["Done"]}',
            'config_logs' => '{"custom": false, "location": "logs/latest.log"}',
            'config_files' => '{"server.properties": {"parser": "properties", "find": {"server-ip": "0.0.0.0", "server-port": "{{server.build.default.port}}"}}}',
        ];
    }
}
