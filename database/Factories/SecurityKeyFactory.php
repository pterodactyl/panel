<?php

namespace Database\Factories;

use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\User;
use Pterodactyl\Models\SecurityKey;
use Webauthn\TrustPath\EmptyTrustPath;
use Illuminate\Database\Eloquent\Factories\Factory;

class SecurityKeyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SecurityKey::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'uuid' => Uuid::uuid4()->toString(),
            'user_id' => User::factory(),
            'name' => $this->faker->word,
            'type' => 'public-key',
            'transports' => [],
            'attestation_type' => 'none',
            'trust_path' => new EmptyTrustPath(),
            'user_handle' => function (array $attributes) {
                return User::find($attributes['user_id'])->uuid;
            },
            'counter' => 0,
        ];
    }
}
