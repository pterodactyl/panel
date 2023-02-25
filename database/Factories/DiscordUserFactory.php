<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DiscordUserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id' => $this->faker->numberBetween(186902438396035072, 986902438396035072),
            'user_id' => function () {
                return User::factory()->create()->id;
            },
            'username' => $this->faker->userName(),
            'avatar' => $this->faker->uuid(),
            'discriminator' => $this->faker->randomNumber(4),
            'email' => $this->faker->safeEmail(),
            'verified' => $this->faker->boolean(),
            'public_flags' => $this->faker->randomNumber(1),
            'locale' => Str::random(2),
            'mfa_enabled' => $this->faker->boolean(),
            'premium_type' => $this->faker->randomNumber(1),
        ];
    }
}
