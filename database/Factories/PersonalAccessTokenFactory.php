<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Pterodactyl\Models\PersonalAccessToken;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonalAccessTokenFactory extends Factory
{
    /**
     * @var string
     */
    protected $model = PersonalAccessToken::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'token_id' => PersonalAccessToken::generateTokenIdentifier(),
            'token' => hash('sha256', Str::random(PersonalAccessToken::TOKEN_LENGTH)),
            'description' => 'Generated test token',
            'abilities' => ['*'],
        ];
    }
}
