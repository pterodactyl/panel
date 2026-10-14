<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Subusers;

use Pterodactyl\Exceptions\Service\Subuser\ServerSubuserExistsException;
use Pterodactyl\Exceptions\Service\Subuser\UserIsServerOwnerException;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Throwable;

interface CreatesSubusers
{
    /**
     * Give the user with the email address access to the server, inviting a new user when no
     * account has it; the returned subuser's user reports wasRecentlyCreated when one was. The
     * email address and the permissions are validated here: each permission must be one the
     * panel or an enabled extension defines. Whether the caller may grant them is left to the
     * caller.
     *
     * @param  list<string>  $permissions  The permission keys to grant, deduplicated
     *                                     before they are stored.
     *
     * @throws ServerSubuserExistsException
     * @throws UserIsServerOwnerException
     * @throws Throwable
     */
    public function create(Server $server, string $email, array $permissions): Subuser;
}
