<?php

namespace Database\Factories;

use Pterodactyl\Models\WebauthnKey;
use Illuminate\Database\Eloquent\Factories\Factory;

class WebauthnKeyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = WebauthnKey::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
        ];
    }
}
