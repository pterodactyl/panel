<?php

namespace Pterodactyl\Facades;

use Illuminate\Support\Facades\Facade;
use Pterodactyl\Services\Activity\ActivityLogBatchService;

class LogBatch extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ActivityLogBatchService::class;
    }
}
