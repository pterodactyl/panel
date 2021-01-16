<?php

namespace Database\Factories;

use Carbon\Carbon;
use Pterodactyl\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        static $password;

        return [
            'external_id' => $this->faker->unique()->isbn10,
            'uuid' => $this->faker->unique()->uuid,
            'username' => $this->faker->userName,
            'email' => $this->faker->safeEmail,
            'name_first' => $this->faker->firstName,
            'name_last' => $this->faker->lastName,
            'password' => $password ?: $password = bcrypt('password'),
            'language' => 'en',
            'root_admin' => false,
            'use_totp' => false,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }

    /**
     * Indicate that the user is an admin.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function admin(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'root_admin' => true,
            ];
        });
    }
}
