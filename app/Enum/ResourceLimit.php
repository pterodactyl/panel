<?php

namespace Pterodactyl\Enum;

use Illuminate\Http\Request;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Routing\Middleware\ThrottleRequests;

/**
 * A basic resource throttler for individual servers. This is applied in addition
 * to existing rate limits and allows the code to slow down speedy users that might
 * be creating resources a little too quickly for comfort. This throttle generally
 * only applies to creation flows, and not general view/edit/delete flows.
 */
enum ResourceLimit
{
    case Allocation;
    case Backup;
    case Database;
    case Schedule;
    case Subuser;
    case Websocket;
    case FilePull;

    public function throttleKey(): string
    {
        return mb_strtolower("api.client:server-resource:{$this->name}");
    }

    /**
     * Returns a middleware that will throttle the specific resource by server. This
     * throttle applies to any user making changes to that resource on the specific
     * server, it is NOT per-user.
     */
    public function middleware(): string
    {
        return ThrottleRequests::using($this->throttleKey());
    }

    public function limit(): Limit
    {
        return match($this) {
            self::Backup => Limit::perMinutes(15, 3),
            self::Database => Limit::perMinute(2),
            self::FilePull => Limit::perMinutes(10, 5),
            self::Subuser => Limit::perMinutes(15, 10),
            self::Websocket => Limit::perMinute(5),
            default => Limit::perMinute(2),
        };
    }

    public static function boot(): void
    {
        foreach (self::cases() as $case) {
            RateLimiter::for($case->throttleKey(), function (Request $request) use ($case) {
                Assert::isInstanceOf($server = $request->route()->parameter('server'), Server::class);

                return $case->limit()->by($server->uuid);
            });
        }
    }
}
