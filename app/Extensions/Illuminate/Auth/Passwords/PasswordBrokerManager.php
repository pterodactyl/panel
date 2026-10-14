<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Illuminate\Auth\Passwords;

use Illuminate\Auth\AuthManager;
use Illuminate\Auth\Passwords\PasswordBrokerManager as IlluminatePasswordBrokerManager;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\DatabaseManager;
use InvalidArgumentException;

class PasswordBrokerManager extends IlluminatePasswordBrokerManager
{
    /**
     * {@inheritdoc}
     */
    protected function resolve($name): PasswordBroker
    {
        $config = $this->app->make(ConfigRepository::class);
        $prefix = "auth.passwords.{$name}";

        throw_unless($config->has($prefix), InvalidArgumentException::class, "Password resetter [{$name}] is not defined.");

        $users = $this->app->make(AuthManager::class)->createUserProvider(
            $config->get("{$prefix}.provider") === null ? null : $config->string("{$prefix}.provider")
        );

        throw_if($users === null, InvalidArgumentException::class, "Password resetter [{$name}] has no user provider.");

        $key = $config->string('app.key');

        $tokens = new DatabaseTokenRepository(
            $this->app->make(DatabaseManager::class)->connection($config->get("{$prefix}.connection") === null ? null : $config->string("{$prefix}.connection")),
            $this->app->make(Hasher::class),
            $config->string("{$prefix}.table"),
            str_starts_with($key, 'base64:') ? base64_decode(mb_substr($key, 7)) : $key,
            $config->integer("{$prefix}.expire", 60) * 60,
            $config->integer("{$prefix}.throttle", 0),
        );

        return new PasswordBroker(
            $tokens,
            $users,
            $this->app->make(Dispatcher::class),
            timeboxDuration: $config->integer('auth.timebox_duration', 200000),
        );
    }
}
