<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Pterodactyl\Models\User;
use Ramsey\Uuid\Uuid;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        static $password;

        return [
            'external_id' => null,
            'uuid' => Uuid::uuid4()->toString(),
            'username' => fake()->userName().'_'.Str::random(10),
            'email' => Str::random(32).'@example.com',
            'name_first' => fake()->firstName(),
            'name_last' => fake()->lastName(),
            'password' => $password ?: $password = bcrypt('password'),
            'remember_token' => Str::random(10),
            'language' => 'en',
            'root_admin' => false,
            'use_totp' => false,
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(['root_admin' => true]);
    }
}
