<?php

namespace Database\Factories;

use Carbon\Carbon;
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
     *
     * @return array
     */
    public function definition(): array
    {
        static $token;

        return [
            'key_type' => ApiKey::TYPE_APPLICATION,
            'identifier' => str_random(\Pterodactyl\Models\ApiKey::IDENTIFIER_LENGTH),
            'token' => $token ?: $token = encrypt(str_random(\Pterodactyl\Models\ApiKey::KEY_LENGTH)),
            'allowed_ips' => null,
            'memo' => 'Test Function Key',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
