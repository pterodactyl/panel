<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Models\User;

/**
 * @extends Factory<ApiKey>
 */
class ApiKeyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ApiKey::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        static $token;

        return [
            'user_id' => User::factory(),
            'key_type' => ApiKey::TYPE_APPLICATION,
            'identifier' => ApiKey::generateTokenIdentifier(ApiKey::TYPE_APPLICATION),
            'token' => $token ?: $token = encrypt(Str::random(ApiKey::KEY_LENGTH)),
            'allowed_ips' => null,
            'memo' => 'Test Function Key',
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }

    /**
     * Restrict the key to an example IP range for documentation purposes.
     */
    public function withAllowedIps(): self
    {
        return $this->state([
            'allowed_ips' => ['127.0.0.1'],
        ]);
    }
}
