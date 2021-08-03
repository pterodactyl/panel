<?php

namespace Database\Factories;

use Pterodactyl\Models\Allocation;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'ip' => $this->faker->unique()->ipv4,
            'port' => $this->faker->unique()->randomNumber(5),
        ];
    }
}
