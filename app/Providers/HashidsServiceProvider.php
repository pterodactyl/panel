<?php

declare(strict_types=1);

namespace Pterodactyl\Providers;

use Illuminate\Config\Repository;
use Illuminate\Support\ServiceProvider;
use Pterodactyl\Contracts\Extensions\HashidsInterface;
use Pterodactyl\Extensions\Hashids;

class HashidsServiceProvider extends ServiceProvider
{
    /**
     * Register the ability to use Hashids.
     */
    public function register(): void
    {
        $this->app->singleton(function (): HashidsInterface {
            $config = $this->app->make(Repository::class);

            return new Hashids(
                $config->string('hashids.salt', ''),
                (int) $config->get('hashids.length', 0),
                $config->string('hashids.alphabet', 'abcdefghijkmlnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890')
            );
        });

        $this->app->alias(HashidsInterface::class, 'hashids');
    }
}
