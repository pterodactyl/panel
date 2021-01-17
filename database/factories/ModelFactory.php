<?php

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Cake\Chronos\Chronos;
use Illuminate\Support\Str;
use Pterodactyl\Models\Node;
use Faker\Generator as Faker;
use Pterodactyl\Models\ApiKey;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
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
        'uuid' => Uuid::uuid4()->toString(),
        'uuidShort' => str_random(8),
        'name' => $faker->firstName,
        'description' => implode(' ', $faker->sentences()),
        'skip_scripts' => 0,
        'status' => null,
        'memory' => 512,
        'swap' => 0,
        'disk' => 512,
        'io' => 500,
        'cpu' => 0,
        'oom_disabled' => 0,
        'database_limit' => null,
        'allocation_limit' => null,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ];
});

$factory->define(Pterodactyl\Models\User::class, function (Faker $faker) {
    static $password;

    return [
        'external_id' => $faker->unique()->isbn10,
        'uuid' => $faker->uuid,
        'username' => $faker->userName,
        'email' => $faker->safeEmail,
        'name_first' => $faker->firstName,
        'name_last' => $faker->lastName,
        'password' => $password ?: $password = bcrypt('password'),
        'language' => 'en',
        'root_admin' => false,
        'use_totp' => false,
        'created_at' => Chronos::now(),
        'updated_at' => Chronos::now(),
    ];
});

$factory->state(Pterodactyl\Models\User::class, 'admin', function () {
    return [
        'root_admin' => true,
    ];
});

$factory->define(Pterodactyl\Models\Location::class, function (Faker $faker) {
    return [
        'short' => Str::random(8),
        'long' => $faker->catchPhrase,
    ];
});

$factory->define(Pterodactyl\Models\Node::class, function (Faker $faker) {
    return [
        'uuid' => Uuid::uuid4()->toString(),
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
        'daemon_token_id' => Str::random(Node::DAEMON_TOKEN_ID_LENGTH),
        'daemon_token' => encrypt(Str::random(Node::DAEMON_TOKEN_LENGTH)),
        'daemonListen' => 8080,
        'daemonSFTP' => 2022,
        'daemonBase' => '/var/lib/pterodactyl/volumes',
    ];
});

$factory->define(Pterodactyl\Models\Nest::class, function (Faker $faker) {
    return [
        'uuid' => $faker->unique()->uuid,
        'author' => 'testauthor@example.com',
        'name' => $faker->word,
        'description' => null,
    ];
});

$factory->define(Pterodactyl\Models\Egg::class, function (Faker $faker) {
    return [
        'uuid' => $faker->unique()->uuid,
        'name' => $faker->name,
        'description' => implode(' ', $faker->sentences(3)),
        'startup' => 'java -jar test.jar',
    ];
});

$factory->define(Pterodactyl\Models\EggVariable::class, function (Faker $faker) {
    return [
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

$factory->define(Pterodactyl\Models\Subuser::class, function (Faker $faker) {
    return [];
});

$factory->define(Pterodactyl\Models\Allocation::class, function (Faker $faker) {
    return [
        'ip' => $faker->ipv4,
        'port' => $faker->randomNumber(5),
    ];
});

$factory->define(Pterodactyl\Models\DatabaseHost::class, function (Faker $faker) {
    return [
        'name' => $faker->colorName,
        'host' => $faker->unique()->ipv4,
        'port' => 3306,
        'username' => $faker->colorName,
        'password' => Crypt::encrypt($faker->word),
    ];
});

$factory->define(Pterodactyl\Models\Database::class, function (Faker $faker) {
    static $password;

    return [
        'database' => str_random(10),
        'username' => str_random(10),
        'remote' => '%',
        'password' => $password ?: bcrypt('test123'),
        'created_at' => Carbon::now()->toDateTimeString(),
        'updated_at' => Carbon::now()->toDateTimeString(),
    ];
});

$factory->define(Pterodactyl\Models\Schedule::class, function (Faker $faker) {
    return [
        'name' => $faker->firstName(),
    ];
});

$factory->define(Pterodactyl\Models\Task::class, function (Faker $faker) {
    return [
        'sequence_id' => $faker->randomNumber(1),
        'action' => 'command',
        'payload' => 'test command',
        'time_offset' => 120,
        'is_queued' => false,
    ];
});

$factory->define(Pterodactyl\Models\ApiKey::class, function (Faker $faker) {
    static $token;

    return [
        'key_type' => ApiKey::TYPE_APPLICATION,
        'identifier' => str_random(Pterodactyl\Models\ApiKey::IDENTIFIER_LENGTH),
        'token' => $token ?: $token = encrypt(str_random(Pterodactyl\Models\ApiKey::KEY_LENGTH)),
        'allowed_ips' => null,
        'memo' => 'Test Function Key',
        'created_at' => Carbon::now()->toDateTimeString(),
        'updated_at' => Carbon::now()->toDateTimeString(),
    ];
});
