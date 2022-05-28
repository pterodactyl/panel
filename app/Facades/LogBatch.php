<?php

namespace Pterodactyl\Facades;

use Illuminate\Support\Facades\Facade;
use Pterodactyl\Services\Activity\AcitvityLogBatchService;

/**
 * @method static ?string uuid()
 * @method static void start()
 * @method static void end()
 * @method static mixed transaction(\Closure $callback)
 */
class LogBatch extends Facade
{
    protected static function getFacadeAccessor()
    {
        return AcitvityLogBatchService::class;
    }
}
