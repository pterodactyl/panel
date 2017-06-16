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

$factory->define(Pterodactyl\Models\Location::class, function (Faker\Generator $faker) {
    return [
       'short' => $faker->domainWord,
       'long' => $faker->catchPhrase,
   ];
});

$factory->define(Pterodactyl\Models\Node::class, function (Faker\Generator $faker) {
    return [
        'public' => true,
        'name' => $faker->firstName,
        'fqdn' => $faker->ipv4,
        'scheme' => 'http',
        'behind_proxy' => false,
        'memory' => 1024,
        'memory_overallocate' => 0,
        'disk' => 10240,
        'disk_overallocate' => 0,
        'upload_size' => 100,
        'daemonSecret' => $faker->uuid,
        'daemonListen' => 8080,
        'daemonSFTP' => 2022,
        'daemonBase' => '/srv/daemon',
    ];
});
