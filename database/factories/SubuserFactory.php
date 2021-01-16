<?php

namespace Database\Factories;

use Pterodactyl\Models\Subuser;
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
     *
     * @return array
     */
    public function definition(): array
    {
        return [];
    }
}
