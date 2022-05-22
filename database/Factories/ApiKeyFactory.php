<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Pterodactyl\Models\ApiKey;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'key_type' => ApiKey::TYPE_APPLICATION,
            'identifier' => ApiKey::generateTokenIdentifier(ApiKey::TYPE_APPLICATION),
            'token' => $token ?: $token = encrypt(Str::random(ApiKey::KEY_LENGTH)),
            'allowed_ips' => null,
            'memo' => 'Test Function Key',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
