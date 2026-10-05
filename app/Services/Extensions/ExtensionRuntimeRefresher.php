<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

final readonly class ExtensionRuntimeRefresher
{
    public function __construct(private Application $application) {}

    public function refresh(): void
    {
        File::delete($this->application->getCachedRoutesPath());
        Cache::forever('illuminate:queue:restart', now()->getTimestamp());
    }
}
