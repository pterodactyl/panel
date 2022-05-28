<?php

namespace Pterodactyl\Facades;

use Illuminate\Support\Facades\Facade;
use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Services\Activity\ActivityLogService;

/**
 * @method static ActivityLogService anonymous()
 * @method static ActivityLogService event(string $action)
 * @method static ActivityLogService withDescription(?string $description)
 * @method static ActivityLogService withSubject(Model $subject)
 * @method static ActivityLogService withActor(Model $actor)
 * @method static ActivityLogService withProperties(\Illuminate\Support\Collection|array $properties)
 * @method static ActivityLogService withProperty(string $key, mixed $value)
 * @method static mixed transaction(\Closure $callback)
 */
class Activity extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ActivityLogService::class;
    }
}
