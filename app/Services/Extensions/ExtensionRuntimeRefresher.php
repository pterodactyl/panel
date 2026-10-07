<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Illuminate\Contracts\Console\Kernel;

/**
 * Runs what an administrator would after changing which extension code runs: route:clear,
 * since cached routes hold the routes of the extensions enabled when they were cached, and
 * queue:restart, so long-running workers boot with the new set.
 */
final readonly class ExtensionRuntimeRefresher
{
    public function __construct(private Kernel $artisan) {}

    public function refresh(): void
    {
        $this->artisan->call('route:clear');
        $this->artisan->call('queue:restart');
    }
}
