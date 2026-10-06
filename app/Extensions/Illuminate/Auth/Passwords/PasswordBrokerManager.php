<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Illuminate\Auth\Passwords;

use Illuminate\Auth\AuthManager;
use Illuminate\Auth\Passwords\PasswordBrokerManager as IlluminatePasswordBrokerManager;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\DatabaseManager;
use InvalidArgumentException;
use Pterodactyl\Support\JsonValueGuard;

class PasswordBrokerManager extends IlluminatePasswordBrokerManager
{
    /**
     * {@inheritdoc}
     */
    protected function resolve($name): PasswordBroker
    {
        $config = $this->app->make(ConfigRepository::class);
        $prefix = "auth.passwords.{$name}";

        if (! $config->has($prefix)) {
            throw new InvalidArgumentException("Password resetter [{$name}] is not defined.");
        }

        $users = $this->app->make(AuthManager::class)->createUserProvider(
            JsonValueGuard::nullableString($config->get("{$prefix}.provider"))
        );

        if ($users === null) {
            throw new InvalidArgumentException("Password resetter [{$name}] has no user provider.");
        }

        $key = JsonValueGuard::string($config->get('app.key'));

        $tokens = new DatabaseTokenRepository(
            $this->app->make(DatabaseManager::class)->connection(JsonValueGuard::nullableString($config->get("{$prefix}.connection"))),
            $this->app->make(Hasher::class),
            JsonValueGuard::string($config->get("{$prefix}.table")),
            str_starts_with($key, 'base64:') ? base64_decode(mb_substr($key, 7)) : $key,
            JsonValueGuard::integer($config->get("{$prefix}.expire", 60)) * 60,
            JsonValueGuard::integer($config->get("{$prefix}.throttle", 0)),
        );

        return new PasswordBroker(
            $tokens,
            $users,
            $this->app->make(Dispatcher::class),
            timeboxDuration: JsonValueGuard::integer($config->get('auth.timebox_duration', 200000)),
        );
    }
}
