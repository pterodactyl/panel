<?php

namespace Pterodactyl\Facades;

use Illuminate\Support\Facades\Facade;
use Pterodactyl\Services\Activity\AcitvityLogBatchService;

class LogBatch extends Facade
{
    protected static function getFacadeAccessor()
    {
        return AcitvityLogBatchService::class;
    }
}
