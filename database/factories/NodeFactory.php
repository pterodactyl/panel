<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Pterodactyl\Models\Node;
use Illuminate\Database\Eloquent\Factories\Factory;

class NodeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Node::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->unique()->uuid,
            'public' => true,
            'name' => $this->faker->firstName,
            'fqdn' => $this->faker->ipv4,
            'scheme' => 'http',
            'behind_proxy' => false,
            'memory' => 1024,
            'memory_overallocate' => 0,
            'disk' => 10240,
            'disk_overallocate' => 0,
            'upload_size' => 100,
            'daemon_token_id' => Str::random(Node::DAEMON_TOKEN_ID_LENGTH),
            'daemon_token' => encrypt(Str::random(Node::DAEMON_TOKEN_LENGTH)),
            'daemonListen' => 8080,
            'daemonSFTP' => 2022,
            'daemonBase' => '/var/lib/pterodactyl/volumes',
        ];
    }
}
