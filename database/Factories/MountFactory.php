<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Pterodactyl\Models\Mount;
use Ramsey\Uuid\Uuid;

/**
 * @extends Factory<Mount>
 */
class MountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Mount::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'uuid' => Uuid::uuid4()->toString(),
            'name' => 'FactoryMount_'.Str::random(10),
            'description' => fake()->sentence(),
            'source' => '/mnt/'.Str::lower(Str::random(10)),
            'target' => '/mnt/'.Str::lower(Str::random(10)),
            'read_only' => false,
            'user_mountable' => false,
        ];
    }
}
