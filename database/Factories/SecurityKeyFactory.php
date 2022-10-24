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
            'name' => $this->faker->word,
            'type' => 'public-key',
            'transports' => [],
            'attestation_type' => 'none',
            'trust_path' => new EmptyTrustPath(),
            'counter' => 0,
        ];
    }

    public function withUser(User $user): self
    {
        return $this->state([
            'user_id' => $user->id,
            'user_handle' => $user->uuid,
        ]);
    }
}
