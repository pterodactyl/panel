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
     * Creates a new user on the system and assigns them access to the provided server.
     * If the email address already belongs to a user on the system a new user will not
     * be created.
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
