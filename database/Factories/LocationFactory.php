<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Pterodactyl\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Location::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'short' => Str::random(8),
            'long' => Str::random(32),
        ];
    }
}
