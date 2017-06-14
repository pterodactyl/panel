<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(Pterodactyl\Models\User::class, function (Faker\Generator $faker) {
    return [
        'external_id' => null,
        'uuid' => $faker->uuid,
        'username' => $faker->userName,
        'email' => $faker->safeEmail,
        'name_first' => $faker->firstName,
        'name_last' => $faker->lastName,
        'password' => bcrypt('password'),
        'language' => 'en',
        'root_admin' => false,
        'use_totp' => false,
    ];
});

$factory->state(Pterodactyl\Models\User::class, 'admin', function () {
    return [
       'root_admin' => true,
    ];
});
