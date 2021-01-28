<?php

namespace Pterodactyl\Policies;

use Carbon\Carbon;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class ServerPolicy
{
    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    private $cache;

    /**
     * ServerPolicy constructor.
     */
    public function __construct(CacheRepository $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Checks if the user has the given permission on/for the server.
     *
     * @param string $permission
     *
     * @return bool
     */
    protected function checkPermission(User $user, Server $server, $permission)
    {
        $key = sprintf('ServerPolicy.%s.%s', $user->uuid, $server->uuid);

        $permissions = $this->cache->remember($key, Carbon::now()->addSeconds(5), function () use ($user, $server) {
            /** @var \Pterodactyl\Models\Subuser|null $subuser */
            $subuser = $server->subusers()->where('user_id', $user->id)->first();

            return $subuser ? $subuser->permissions : [];
        });

        return in_array($permission, $permissions);
    }

    /**
     * Runs before any of the functions are called. Used to determine if user is root admin, if so, ignore permissions.
     *
     * @param string $ability
     *
     * @return bool
     */
    public function before(User $user, $ability, Server $server)
    {
        if ($user->root_admin || $server->owner_id === $user->id) {
            return true;
        }

        return $this->checkPermission($user, $server, $ability);
    }

    /**
     * This is a horrendous hack to avoid Laravel's "smart" behavior that does
     * not call the before() function if there isn't a function matching the
     * policy permission.
     *
     * @param string $name
     * @param mixed $arguments
     */
    public function __call($name, $arguments)
    {
        // do nothing
    }
}
