<?php

declare(strict_types=1);

namespace Pterodactyl\Providers;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\ServiceProvider;
use Pterodactyl\Contracts\Extensions\HashidsInterface;
use Pterodactyl\Extensions\Hashids;
use Pterodactyl\Support\JsonValueGuard;

class HashidsServiceProvider extends ServiceProvider
{
    /**
     * Register the ability to use Hashids.
     */
    public function register(): void
    {
        $this->app->singleton(function (): HashidsInterface {
            $config = $this->app->make(Repository::class);
            $length = filter_var($config->get('hashids.length', 0), FILTER_VALIDATE_INT);

            return new Hashids(
                JsonValueGuard::string($config->get('hashids.salt', '')),
                $length === false ? 0 : $length,
                JsonValueGuard::string($config->get('hashids.alphabet', 'abcdefghijkmlnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'))
            );
        });

        $this->app->alias(HashidsInterface::class, 'hashids');
    }
}
