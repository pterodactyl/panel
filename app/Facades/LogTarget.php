<?php

namespace Pterodactyl\Facades;

use Illuminate\Support\Facades\Facade;
use Pterodactyl\Services\Activity\ActivityLogTargetableService;

/**
 * @method static void setActor(\Illuminate\Database\Eloquent\Model $actor)
 * @method static void setSubject(\Illuminate\Database\Eloquent\Model $subject)
 * @method static \Illuminate\Database\Eloquent\Model|null actor()
 * @method static \Illuminate\Database\Eloquent\Model|null subject()
 * @method static void reset()
 */
class LogTarget extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ActivityLogTargetableService::class;
    }
}
