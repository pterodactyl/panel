<?php

namespace Pterodactyl\Facades;

use Illuminate\Support\Facades\Facade;
use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Services\Activity\ActivityLogService;

/**
 * @method static ActivityLogService anonymous()
 * @method static ActivityLogService event(string $action)
 * @method static ActivityLogService description(?string $description)
 * @method static ActivityLogService subject(Model $subject)
 * @method static ActivityLogService actor(Model $actor)
 * @method static ActivityLogService withProperties(\Illuminate\Support\Collection|array $properties)
 * @method static ActivityLogService withRequestMetadata()
 * @method static ActivityLogService property(string $key, mixed $value)
 * @method static \Pterodactyl\Models\ActivityLog log(string $description = null)
 * @method static ActivityLogService clone()
 * @method static mixed transaction(\Closure $callback)
 */
class Activity extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ActivityLogService::class;
    }
}
