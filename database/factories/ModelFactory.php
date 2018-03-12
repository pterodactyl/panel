<?php

use Faker\Generator as Faker;

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

$factory->define(Pterodactyl\Models\Server::class, function (Faker $faker) {
    return [
        'id' => $faker->unique()->randomNumber(),
        'node_id' => $faker->randomNumber(),
        'uuid' => $faker->unique()->uuid,
        'uuidShort' => str_random(8),
        'name' => $faker->firstName,
        'description' => implode(' ', $faker->sentences()),
        'skip_scripts' => 0,
        'suspended' => 0,
        'memory' => 512,
        'swap' => 0,
        'disk' => 512,
        'io' => 500,
        'cpu' => 0,
        'oom_disabled' => 0,
        'allocation_id' => $faker->randomNumber(),
        'nest_id' => $faker->randomNumber(),
        'egg_id' => $faker->randomNumber(),
        'pack_id' => null,
        'installed' => 1,
        'created_at' => \Carbon\Carbon::now(),
        'updated_at' => \Carbon\Carbon::now(),
    ];
});

$factory->define(Pterodactyl\Models\User::class, function (Faker $faker) {
    static $password;

    return [
        'id' => $faker->unique()->randomNumber(),
        'external_id' => null,
        'uuid' => $faker->uuid,
        'username' => $faker->userName,
        'email' => $faker->safeEmail,
        'name_first' => $faker->firstName,
        'name_last' => $faker->lastName,
        'password' => $password ?: $password = bcrypt('password'),
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

$factory->define(Pterodactyl\Models\Location::class, function (Faker $faker) {
    return [
        'id' => $faker->unique()->randomNumber(),
        'short' => $faker->domainWord,
        'long' => $faker->catchPhrase,
    ];
});

$factory->define(Pterodactyl\Models\Node::class, function (Faker $faker) {
    return [
        'id' => $faker->unique()->randomNumber(),
        'uuid' => $faker->unique()->uuid,
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

$factory->define(Pterodactyl\Models\Nest::class, function (Faker $faker) {
    return [
        'id' => $faker->unique()->randomNumber(),
        'uuid' => $faker->unique()->uuid,
        'author' => 'testauthor@example.com',
        'name' => $faker->word,
        'description' => null,
    ];
});

$factory->define(Pterodactyl\Models\Egg::class, function (Faker $faker) {
    return [
        'id' => $faker->unique()->randomNumber(),
        'uuid' => $faker->unique()->uuid,
        'nest_id' => $faker->unique()->randomNumber(),
        'name' => $faker->name,
        'description' => implode(' ', $faker->sentences(3)),
        'startup' => 'java -jar test.jar',
    ];
});

$factory->define(Pterodactyl\Models\EggVariable::class, function (Faker $faker) {
    return [
        'id' => $faker->unique()->randomNumber(),
        'name' => $faker->firstName,
        'description' => $faker->sentence(),
        'env_variable' => strtoupper(str_replace(' ', '_', $faker->words(2, true))),
        'default_value' => $faker->colorName,
        'user_viewable' => 0,
        'user_editable' => 0,
        'rules' => 'required|string',
    ];
});

$factory->state(Pterodactyl\Models\EggVariable::class, 'viewable', function () {
    return ['user_viewable' => 1];
});

$factory->state(Pterodactyl\Models\EggVariable::class, 'editable', function () {
    return ['user_editable' => 1];
});

$factory->define(Pterodactyl\Models\Pack::class, function (Faker $faker) {
    return [
        'id' => $faker->unique()->randomNumber(),
        'egg_id' => $faker->randomNumber(),
        'uuid' => $faker->uuid,
        'name' => $faker->word,
        'description' => null,
        'version' => $faker->randomNumber(),
        'selectable' => 1,
        'visible' => 1,
        'locked' => 0,
    ];
});

$factory->define(Pterodactyl\Models\Subuser::class, function (Faker $faker) {
    return [
        'id' => $faker->unique()->randomNumber(),
        'user_id' => $faker->randomNumber(),
        'server_id' => $faker->randomNumber(),
    ];
});

$factory->define(Pterodactyl\Models\Allocation::class, function (Faker $faker) {
    return [
        'id' => $faker->unique()->randomNumber(),
        'node_id' => $faker->randomNumber(),
        'ip' => $faker->ipv4,
        'port' => $faker->randomNumber(5),
    ];
});

$factory->define(Pterodactyl\Models\DatabaseHost::class, function (Faker $faker) {
    return [
        'id' => $faker->unique()->randomNumber(),
        'name' => $faker->colorName,
        'host' => $faker->unique()->ipv4,
        'port' => 3306,
        'username' => $faker->colorName,
        'password' => Crypt::encrypt($faker->word),
        'node_id' => $faker->randomNumber(),
    ];
});

$factory->define(Pterodactyl\Models\Database::class, function (Faker $faker) {
    static $password;

    return [
        'id' => $faker->unique()->randomNumber(),
        'server_id' => $faker->randomNumber(),
        'database_host_id' => $faker->randomNumber(),
        'database' => str_random(10),
        'username' => str_random(10),
        'remote' => '%',
        'password' => $password ?: bcrypt('test123'),
        'created_at' => \Carbon\Carbon::now()->toDateTimeString(),
        'updated_at' => \Carbon\Carbon::now()->toDateTimeString(),
    ];
});

$factory->define(Pterodactyl\Models\Schedule::class, function (Faker $faker) {
    return [
        'id' => $faker->unique()->randomNumber(),
        'server_id' => $faker->randomNumber(),
        'name' => $faker->firstName(),
    ];
});

$factory->define(Pterodactyl\Models\Task::class, function (Faker $faker) {
    return [
        'id' => $faker->unique()->randomNumber(),
        'schedule_id' => $faker->randomNumber(),
        'sequence_id' => $faker->randomNumber(1),
        'action' => 'command',
        'payload' => 'test command',
        'time_offset' => 120,
        'is_queued' => false,
    ];
});

$factory->define(Pterodactyl\Models\DaemonKey::class, function (Faker $faker) {
    return [
        'id' => $faker->unique()->randomNumber(),
        'server_id' => $faker->randomNumber(),
        'user_id' => $faker->randomNumber(),
        'secret' => 'i_' . str_random(40),
        'expires_at' => \Carbon\Carbon::now()->addMinutes(10)->toDateTimeString(),
    ];
});

$factory->define(Pterodactyl\Models\ApiKey::class, function (Faker $faker) {
    return [
        'id' => $faker->unique()->randomNumber(),
        'user_id' => $faker->randomNumber(),
        'identifier' => str_random(Pterodactyl\Models\ApiKey::IDENTIFIER_LENGTH),
        'token' => 'encrypted_string',
        'memo' => 'Test Function Key',
        'created_at' => \Carbon\Carbon::now()->toDateTimeString(),
        'updated_at' => \Carbon\Carbon::now()->toDateTimeString(),
    ];
});

$factory->define(Pterodactyl\Models\APIPermission::class, function (Faker $faker) {
    return [
        'id' => $faker->unique()->randomNumber(),
        'key_id' => $faker->randomNumber(),
        'permission' => mb_strtolower($faker->word),
    ];
});
